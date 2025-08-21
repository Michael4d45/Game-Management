-- BizHawk Lua: IPC command runner over TCP from local Go client
-- Commands (one per line):
--   SWAP|<epoch>|<game_file>
--   START|<epoch>|<game_file>
--   SAVE|<save_path>
--   PAUSE[|<epoch>]
--   RESUME[|<epoch>]
--   MSG|<text>               -- shows for default 3s
--   MSG|<seconds>|<text>     -- shows for given seconds

local socket = require("socket.core") -- BizHawk has LuaSocket
local HOST = "127.0.0.1"
local PORT = 55355

local ROM_DIR = "../roms"
local SAVE_DIR = "../saves"

console.log("Script starting...")
console.log("BIZHAWK_IPC_PORT: " .. tostring(PORT))
console.log("BIZHAWK_ROM_DIR: " .. ROM_DIR)
console.log("BIZHAWK_SAVE_DIR: " .. SAVE_DIR)

local function now()
  return socket.gettime()
end

local function write_to_screen(text, x, y, fontsize, fg, bg)
  gui.use_surface("client")
  gui.drawText(
    x or 10,
    y or 10,
    text,
    fg or 0xFFFFFFFF, -- default white text
    bg or 0xFF000000, -- default black background
    fontsize or 12
  )
end

-- Active on-screen messages (persisted by re-drawing each frame)
local messages = {} -- list of { text=..., expires=..., x=..., y=..., fontsize=..., fg=..., bg=... }

local function show_message(text, duration, x, y, fontsize, fg, bg)
  duration = tonumber(duration) or 3.0
  table.insert(messages, {
    text = text or "",
    expires = now() + duration,
    x = x or 10,
    y = y or 10,
    fontsize = fontsize or 12,
    fg = fg or 0xFFFFFFFF,
    bg = bg or 0xFF000000
  })
end

local function draw_messages()
  gui.clearGraphics()
  if #messages == 0 then
    return
  end
  gui.use_surface("client")
  local t = now()
  local keep = {}
  local yoff = 0
  for _, m in ipairs(messages) do
    if t < m.expires then
      gui.drawText(m.x, m.y + yoff, m.text, m.fg, m.bg, m.fontsize)
      table.insert(keep, m)
      yoff = yoff + (m.fontsize + 4) -- stack messages vertically
    end
  end
  messages = keep
end

local function file_exists(name)
  local f = io.open(name, "r")
  if f ~= nil then
    io.close(f)
    return true
  else
    return false
  end
end

local function save_state(path)
  savestate.save(path)
end

local function load_state_if_exists(path)
  if file_exists(path) then
    savestate.load(path)
  end
end

local function load_rom(path)
  if file_exists(path) then
    client.openrom(path)
  else
    console.log("ROM not found: " .. path .. ", cannot load.")
  end
end

-- Helpers for ROM naming
local function get_rom_display_name()
  if client and client.getromname then
    return client.getromname()
  end
  if gameinfo and gameinfo.getromname then
    return gameinfo.getromname()
  end
  if emu and emu.getromname then
    return emu.getromname()
  end
  return nil
end

local function sanitize_filename(name)
  if not name then
    return nil
  end
  name = name:gsub("[/\\:*?\"<>|]", "_")
  name = name:gsub("%s+$", "")
  return name
end

local function strip_extension(filename)
  return (filename:gsub("%.[^%.]+$", ""))
end

-- Scheduler
local pending = {} -- list of { at = <epoch>, fn = function() end }

local function schedule(at_epoch, fn)
  table.insert(pending, { at = at_epoch, fn = fn })
end

local function execute_due()
  local t = now()
  local keep = {}
  for _, job in ipairs(pending) do
    if job.at <= t then
      local ok, err = pcall(job.fn)
      if not ok then
        console.log("[ERROR] Scheduled task failed: " .. tostring(err))
      end
    else
      table.insert(keep, job)
    end
  end
  pending = keep
end

local function schedule_or_now(at_epoch, fn)
  if at_epoch and at_epoch > (now() + 0.0005) then
    schedule(at_epoch, fn)
  else
    local ok, err = pcall(fn)
    if not ok then
      console.log("[ERROR] Immediate command failed: " .. tostring(err))
    end
  end
end

-- Save current ROM state if valid
local function save_current_if_any()
  local cur = get_rom_display_name()
  cur = sanitize_filename(cur)
  if not cur or cur == "" or cur:lower() == "null" then
    return
  end
  local path = SAVE_DIR .. "/" .. cur .. ".state"
  local ok, err = pcall(function()
    save_state(path)
  end)
  if not ok then
    console.log("[ERROR] Failed to save state for '" .. tostring(cur) .. "': " .. tostring(err))
  end
end

-- Command handlers
local function do_swap(target_game)
  save_current_if_any()

  local rom_path = ROM_DIR .. "/" .. target_game
  load_rom(rom_path)

  local disp = sanitize_filename(get_rom_display_name())
  if not disp or disp == "" or disp:lower() == "null" then
    disp = sanitize_filename(strip_extension(target_game))
  end

  local target_save_path = SAVE_DIR .. "/" .. disp .. ".state"
  load_state_if_exists(target_save_path)
end

local function do_start(game)
  local rom_path = ROM_DIR .. "/" .. game
  load_rom(rom_path)

  local disp = sanitize_filename(get_rom_display_name())
  if not disp or disp == "" or disp:lower() == "null" then
    disp = sanitize_filename(strip_extension(game))
  end

  local save_path = SAVE_DIR .. "/" .. disp .. ".state"
  load_state_if_exists(save_path)
end

local function do_save(path)
  save_state(path)
end

local function do_pause()
  if client and client.pause then
    client.pause()
  elseif emu and emu.pause then
    emu.pause()
  end
end

local function do_resume()
  if client and client.unpause then
    client.unpause()
  elseif emu and emu.unpause then
    emu.unpause()
  end
end

-- Socket client (connects to Go server)
local client_socket = nil
local last_attempt = 0

local function ensure_connected()
  if client_socket ~= nil then
    return
  end
  local t = now()
  if t - last_attempt < 1.0 then
    return
  end
  last_attempt = t
  local c, err = socket.tcp()
  if not c then
    return
  end
  c:settimeout(0)
  local ok, err2 = c:connect(HOST, PORT)
  client_socket = c
  if not ok and err2 ~= "timeout" then
    client_socket:close()
    client_socket = nil
  end
end

local function split_pipe(s)
  local parts = {}
  for part in string.gmatch(s, "([^|]+)") do
    table.insert(parts, part)
  end
  return parts
end

local function join_from(parts, start_index)
  if not parts[start_index] then
    return ""
  end
  local s = parts[start_index]
  for i = start_index + 1, #parts do
    s = s .. "|" .. parts[i]
  end
  return s
end

local function read_lines()
  if not client_socket then
    return
  end
  while true do
    local line, err, partial = client_socket:receive("*l")
    if line then
      local parts = split_pipe(line)
      console.log(parts)
      local cmd = parts[1]
      if cmd == "SWAP" and #parts >= 3 then
        local at = tonumber(parts[2])
        local game = parts[3]
        schedule_or_now(at, function()
          do_swap(game)
        end)
      elseif cmd == "START" and #parts >= 3 then
        local game = parts[3]
        schedule_or_now(nil, function()
          do_start(game)
        end)
      elseif cmd == "SAVE" and #parts >= 2 then
        local path = parts[2]
        schedule_or_now(nil, function()
          do_save(path)
        end)
      elseif cmd == "PAUSE" then
        local at = tonumber(parts[2] or "")
        schedule_or_now(at, function()
          do_pause()
        end)
      elseif cmd == "RESUME" then
        local at = tonumber(parts[2] or "")
        schedule_or_now(at, function()
          do_resume()
        end)
      elseif cmd == "MSG" and #parts >= 2 then
        -- Support either: MSG|<text>
        -- or: MSG|<seconds>|<text>
        local dur = tonumber(parts[2])
        local text = nil
        if dur and #parts >= 3 then
          text = join_from(parts, 3)
        else
          dur = 3.0
          text = join_from(parts, 2)
        end
        console.log("[SERVER MESSAGE] " .. tostring(text))
        show_message(text, dur)
      end
    else
      if err == "timeout" then
        break
      elseif err == "closed" then
        client_socket:close()
        client_socket = nil
        break
      else
        break
      end
    end
  end
end

-- Auto-save every 10 seconds
local AUTO_SAVE_INTERVAL = 10.0
local next_auto_save = now() + AUTO_SAVE_INTERVAL

local function auto_save_tick()
  local t = now()
  if t >= next_auto_save then
    save_current_if_any()
    next_auto_save = t + AUTO_SAVE_INTERVAL
  end
end

-- Main loop
while true do
  ensure_connected()
  read_lines()
  execute_due()
  auto_save_tick()
  draw_messages() -- draw persistent messages this frame
  emu.frameadvance()
end
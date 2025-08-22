-- shuffler.lua
-- BizHawk Lua: IPC command runner with ACK/NACK, heartbeat, and SYNC
-- Commands (one per line):
--   CMD|<id>|SWAP|<epoch>|<game_file>
--   CMD|<id>|START|<epoch>|<game_file>
--   CMD|<id>|SAVE|<save_path>
--   CMD|<id>|PAUSE[|<epoch>]
--   CMD|<id>|RESUME[|<epoch>]
--   CMD|<id>|MSG|<text>
--   CMD|<id>|SYNC|<game>|<paused>|<start_at>
-- Lua → Go:
--   ACK|<id>
--   NACK|<id>|<reason>
--   HELLO
--   PING|<timestamp>
-- Go → Lua:
--   PONG|<timestamp>

local socket = require("socket.core")
local HOST = "127.0.0.1"
local PORT = 55355

local ROM_DIR = "../roms"
local SAVE_DIR = "../saves"

console.log("Script starting...")

local function now() return socket.gettime() end

-- === GUI message system ===
local function write_to_screen(text, x, y, fontsize, fg, bg)
  gui.use_surface("client")
  gui.drawText(
    x or 10,
    y or 10,
    text,
    fg or 0xFFFFFFFF,
    bg or 0xFF000000,
    fontsize or 12
  )
end

local messages = {} -- list of { text=..., expires=..., ... }

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
  if #messages == 0 then return end
  gui.use_surface("client")
  local t = now()
  local keep = {}
  local yoff = 0
  for _, m in ipairs(messages) do
    if t < m.expires then
      gui.drawText(m.x, m.y + yoff, m.text, m.fg, m.bg, m.fontsize)
      table.insert(keep, m)
      yoff = yoff + (m.fontsize + 4)
    end
  end
  messages = keep
end

-- === File helpers ===
local function file_exists(name)
  local f = io.open(name, "r")
  if f ~= nil then io.close(f) return true else return false end
end

local function save_state(path) savestate.save(path) end
local function load_state_if_exists(path)
  if file_exists(path) then savestate.load(path) end
end

local function load_rom(path)
  if file_exists(path) then
    client.openrom(path)
  else
    console.log("ROM not found: " .. path .. ", cannot load.")
  end
end

-- === ROM naming helpers ===
local function get_rom_display_name()
  if client and client.getromname then return client.getromname() end
  if gameinfo and gameinfo.getromname then return gameinfo.getromname() end
  if emu and emu.getromname then return emu.getromname() end
  return nil
end

local function sanitize_filename(name)
  if not name then return nil end
  name = name:gsub("[/\\:*?\"<>|]", "_")
  name = name:gsub("%s+$", "")
  return name
end

local function strip_extension(filename)
  return (filename:gsub("%.[^%.]+$", ""))
end

-- === Scheduler ===
local pending = {} -- list of { at = <epoch>, fn = function() end }

local function schedule(at_epoch, fn, command)
  table.insert(pending, { at = at_epoch, fn = fn, command = command })
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

local function schedule_or_now(at_epoch, fn, command)
  if at_epoch and at_epoch > (now() + 0.0005) then
    schedule(at_epoch, fn, command)
  else
    local ok, err = pcall(fn)
    if not ok then
      console.log("[ERROR] Immediate command failed: " .. tostring(err))
    end
  end
end

-- === State save/load ===
local function save_current_if_any()
  local cur = get_rom_display_name()
  cur = sanitize_filename(cur)
  if not cur or cur == "" or cur:lower() == "null" then return end
  local path = SAVE_DIR .. "/" .. cur .. ".state"
  local ok, err = pcall(function() save_state(path) end)
  if not ok then
    console.log("[ERROR] Failed to save state for '" .. tostring(cur) .. "': " .. tostring(err))
  end
end

-- === Command handlers ===
local current_game = nil

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
  client.unpause()
  if game == current_game then return end
  current_game = game
  local rom_path = ROM_DIR .. "/" .. game
  load_rom(rom_path)
  local disp = sanitize_filename(get_rom_display_name())
  if not disp or disp == "" or disp:lower() == "null" then
    disp = sanitize_filename(strip_extension(game))
  end
  local save_path = SAVE_DIR .. "/" .. disp .. ".state"
  load_state_if_exists(save_path)
end

local function do_save(path) save_state(path) end
local function do_pause() client.pause(); console.log("[INFO] Paused") end
local function do_resume() client.unpause(); console.log("[INFO] Resumed") end

-- === Socket client ===
local client_socket = nil
local last_attempt = 0

local function send_line(line)
  if client_socket then client_socket:send(line .. "\n") end
end

-- HELLO handshake
local function send_hello() send_line("HELLO") end

-- ACK/NACK wrapper
local function safe_exec(id, fn)
  local ok, err = pcall(fn)
  if ok then
    send_line("ACK|" .. id)
  else
    send_line("NACK|" .. id .. "|" .. tostring(err))
  end
end

local function ensure_connected()
  if client_socket ~= nil then return end
  local t = now()
  if t - last_attempt < 1.0 then return end
  last_attempt = t
  local c, err = socket.tcp()
  if not c then return end
  c:settimeout(0)
  local ok, err2 = c:connect(HOST, PORT)
  client_socket = c
  if not ok and err2 ~= "timeout" then
    client_socket:close()
    client_socket = nil
  else
    send_hello()
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
  if not parts[start_index] then return "" end
  local s = parts[start_index]
  for i = start_index + 1, #parts do
    s = s .. "|" .. parts[i]
  end
  return s
end

-- === Read loop ===
local function read_lines()
  if not client_socket then return end
  while true do
    local line, err = client_socket:receive("*l")
    if line then
      local parts = split_pipe(line)
      console.log(parts)
      if parts[1] == "CMD" then
        local id, cmd = parts[2], parts[3]
        if cmd == "SWAP" then
          local at, game = tonumber(parts[4]), parts[5]
          safe_exec(id, function() schedule_or_now(at, function() do_swap(game) end, game) end)
        elseif cmd == "SAVE" then
          local path = parts[4]
          safe_exec(id, function() do_save(path) end)
        elseif cmd == "MSG" then
          local text = join_from(parts, 4)
          safe_exec(id, function() show_message(text, 3) end)
        elseif cmd == "SYNC" then
          local game, state, state_at = parts[4], parts[5], tonumber(parts[6] or "0")
          safe_exec(id, function()
            if state == "running" then
              if game and game ~= "" then
                schedule_or_now(state_at, function() do_start(game) end, game, 'start')
              end
            else
              schedule_or_now(state_at, do_pause, 'pause')
            end
          end)
        else
          send_line("NACK|" .. id .. "|Unknown command: " .. tostring(cmd))
        end
      elseif parts[1] == "PONG" then
        -- optional: measure latency
      end
    else
      if err == "timeout" then break end
      if err == "closed" then client_socket:close(); client_socket = nil; break end
      break
    end
  end
end

-- === Heartbeat ===
local next_ping = now() + 5
local function heartbeat_tick()
  local t = now()
  if t >= next_ping then
    send_line("PING|" .. tostring(math.floor(t)))
    next_ping = t + 5
  end
end

-- === Auto-save ===
local AUTO_SAVE_INTERVAL = 10.0
local next_auto_save = now() + AUTO_SAVE_INTERVAL
local function auto_save_tick()
  local t = now()
  if t >= next_auto_save then
    save_current_if_any()
    next_auto_save = t + AUTO_SAVE_INTERVAL
  end
end

-- === Pending jobs logger ===
local PENDING_LOG_INTERVAL = 10.0
local next_pending_log = now() + PENDING_LOG_INTERVAL

local function pending_log_tick()
  local t = now()
  if t >= next_pending_log then
    if #pending == 0 then
      console.log("[PENDING] No scheduled games.")
    else
      console.log("[PENDING] Scheduled games:")
      for i, job in ipairs(pending) do
        local secs = math.max(0, job.at - t)
        console.log(string.format("  %s in %.1fs", job.command, secs))
      end
    end
    next_pending_log = t + PENDING_LOG_INTERVAL
  end
end

-- === Main loop ===
while true do
  ensure_connected()
  read_lines()
  execute_due()
  auto_save_tick()
  draw_messages()
  heartbeat_tick()
  pending_log_tick()

  if client.ispaused() then emu.yield() else emu.frameadvance() end
end
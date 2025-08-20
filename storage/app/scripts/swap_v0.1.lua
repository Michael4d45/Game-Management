-- BizHawk Lua: IPC command runner over TCP from local Go client
-- Commands (one per line):
--   SWAP|<epoch>|<game_file>
--   START|<epoch>|<game_file>
--   SAVE|<save_path>
--   PAUSE[|<epoch>]
--   RESUME[|<epoch>]
--   MSG|<text>

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
  local currentTime = socket.gettime()
  return currentTime
end

local function file_exists(name)
  console.log("Checking if file exists: " .. name)
  local f = io.open(name, "r")
  if f ~= nil then
    io.close(f)
    console.log("File exists: " .. name)
    return true
  else
    console.log("File does not exist: " .. name)
    return false
  end
end

local function save_state(path)
  console.log("Attempting to save state to: " .. path)
  savestate.save(path)
  console.log("Save state command issued for: " .. path)
end

local function load_state_if_exists(path)
  console.log("Attempting to load state if it exists at: " .. path)
  if file_exists(path) then
    console.log("Loading state from: " .. path)
    savestate.load(path)
    console.log("Load state command issued for: " .. path)
  else
    console.log("No save state found at: " .. path .. ", skipping load.")
  end
end

local function load_rom(path)
  console.log("Attempting to load ROM: " .. path)
  if file_exists(path) then
    console.log("Loading ROM: " .. path)
    client.openrom(path)
    console.log("Open ROM command issued for: " .. path)
  else
    console.log("ROM not found: " .. path .. ", cannot load.")
  end
end

-- Simple line splitter (keeps spaces intact)
local function split_pipe(s)
  console.log("Splitting string by pipe: '" .. s .. "'")
  local parts = {}
  for part in string.gmatch(s, "([^|]+)") do
    table.insert(parts, part)
  end
  console.log("Split parts: " .. table.concat(parts, ", "))
  return parts
end

-- Scheduler
local pending = {} -- list of { at = <epoch>, fn = function() end }
console.log("Scheduler initialized. Pending jobs list is empty.")

local function schedule(at_epoch, fn)
  console.log("Scheduling job for epoch: " .. tostring(at_epoch))
  table.insert(pending, { at = at_epoch, fn = fn })
  console.log("Job scheduled. Total pending jobs: " .. #pending)
end

local function execute_due()
  local t = now()
  local keep = {}
  local executedCount = 0
  for i, job in ipairs(pending) do
    if job.at <= t then
      console.log("Executing scheduled job due at: " .. tostring(job.at))
      local ok, err = pcall(job.fn)
      if not ok then
        console.log("[ERROR] Scheduled task failed: " .. tostring(err))
      else
        console.log("Scheduled job executed successfully.")
        executedCount = executedCount + 1
      end
    else
      table.insert(keep, job)
    end
  end
  pending = keep
end

local function schedule_or_now(at_epoch, fn)
  console.log(
    "Deciding to schedule or execute immediately. Target epoch: " ..
      tostring(at_epoch) ..
      ", current time: " ..
      tostring(now())
  )
  if at_epoch and at_epoch > (now() + 0.0005) then
    console.log("Scheduling command for future execution.")
    schedule(at_epoch, fn)
  else
    console.log("Executing command immediately.")
    local ok, err = pcall(fn)
    if not ok then
      console.log("[ERROR] Immediate command failed: " .. tostring(err))
    else
      console.log("Immediate command executed successfully.")
    end
  end
end

-- Command handlers
local function do_swap(game)
  console.log("Executing SWAP command for game: " .. game)
  local rom_path = ROM_DIR .. "/" .. game
  local save_path = SAVE_DIR .. "/" .. game .. ".state"
  console.log("SWAP: ROM path determined: " .. rom_path)
  console.log("SWAP: Save path determined: " .. save_path)
  load_rom(rom_path)
  load_state_if_exists(save_path)
  console.log("SWAP command finished for game: " .. game)
end

local function do_start(game)
  console.log("Executing START command for game: " .. game)
  local rom_path = ROM_DIR .. "/" .. game
  console.log("START: ROM path determined: " .. rom_path)
  load_rom(rom_path)
  -- Optional: preload a state if present
  local save_path = SAVE_DIR .. "/" .. game .. ".state"
  console.log("START: Save path determined for optional preload: " .. save_path)
  load_state_if_exists(save_path)
  console.log("START command finished for game: " .. game)
end

local function do_save(path)
  console.log("Executing SAVE command to path: " .. path)
  save_state(path)
  console.log("SAVE command finished for path: " .. path)
end

local function do_pause()
  console.log("Executing PAUSE command: Pausing emulation")
  emu.pause()
  console.log("PAUSE command issued.")
end

local function do_resume()
  console.log("Executing RESUME command: Resuming emulation")
  emu.unpause()
  console.log("RESUME command issued.")
end

-- Socket client (connects to Go server)
local client = nil
local last_attempt = 0
console.log("Socket client initialized to nil. Last attempt time: " .. last_attempt)

local function ensure_connected()
  if client ~= nil then
    return
  end
  local t = now()
  if t - last_attempt < 1.0 then
    console.log(
      "Too soon to retry connection. Last attempt was " ..
        tostring(t - last_attempt) ..
        " seconds ago."
    )
    return
  end
  last_attempt = t
  console.log(string.format("Attempting IPC connect to %s:%d ...", HOST, PORT))
  local c, err = socket.tcp()
  if not c then
    console.log("[ERROR] Failed to create TCP socket: " .. tostring(err))
    return
  end
  c:settimeout(0) -- non-blocking
  local ok, err2 = c:connect(HOST, PORT)
  -- With non-blocking sockets, connect may return nil, 'timeout' while connecting.
  -- We'll treat that as fine and proceed; reads will timeout until connected.
  client = c
  if ok then
    console.log("Successfully initiated non-blocking connect.")
  elseif err2 == "timeout" then
    console.log(
      "Connect operation is in progress (timeout due to non-blocking)."
    )
  else
    console.log("[ERROR] Initial connect failed: " .. tostring(err2))
    client:close()
    client = nil
  end
end

local function read_lines()
  if not client then
    console.log("No client connected, skipping read_lines.")
    return
  end
  while true do
    local line, err, partial = client:receive("*l")
    if line then
      console.log("Received line from IPC: '" .. line .. "'")
      local parts = split_pipe(line)
      local cmd = parts[1]
      if cmd == "SWAP" and #parts >= 3 then
        local at = tonumber(parts[2])
        local game = parts[3]
        console.log(
          string.format(
            "Parsed SWAP command: game='%s', epoch=%s",
            game,
            tostring(at)
          )
        )
        schedule_or_now(at, function()
          do_swap(game)
        end)
      elseif cmd == "START" and #parts >= 3 then
        local at = tonumber(parts[2])
        local game = parts[3]
        console.log(
          string.format(
            "Parsed START command: game='%s', epoch=%s",
            game,
            tostring(at)
          )
        )
        schedule_or_now(nil, function()
          do_start(game)
        end)
      elseif cmd == "SAVE" and #parts >= 2 then
        local path = parts[2]
        console.log("Parsed SAVE command: path='" .. path .. "'")
        schedule_or_now(nil, function()
          do_save(path)
        end)
      elseif cmd == "PAUSE" then
        local at = tonumber(parts[2] or "")
        console.log(
          "Parsed PAUSE command: epoch=" .. tostring(at or "nil (immediate)")
        )
        schedule_or_now(at, function()
          do_pause()
        end)
      elseif cmd == "RESUME" then
        local at = tonumber(parts[2] or "")
        console.log(
          "Parsed RESUME command: epoch=" .. tostring(at or "nil (immediate)")
        )
        schedule_or_now(at, function()
          do_resume()
        end)
      elseif cmd == "MSG" and #parts >= 2 then
        console.log("[SERVER MESSAGE] " .. parts[2])
      else
        console.log("[WARN] Unknown/invalid command line: '" .. line .. "'")
      end
    else
      if err == "timeout" then
        break
      elseif err == "closed" then
        console.log("IPC connection closed by server; will retry connecting.")
        client:close()
        client = nil
        break
      else
        console.log("[ERROR] Error reading from socket: " .. tostring(err))
        console.log("Breaking read loop due to error.")
        -- Any other error while not connected yet? Just break and try again.
        break
      end
    end
  end
end

-- Main loop
console.log("Entering main emulation loop.")
while true do
  ensure_connected()
  read_lines()
  execute_due()
  emu.frameadvance()
end
local socket = require("socket.core")

local function file_exists(name)
    local f = io.open(name, "r")
    if f ~= nil then io.close(f) return true else return false end
end

local function read_lines(name)
    local lines = {}
    local f = io.open(name, "r")
    if not f then return lines end
    for line in f:lines() do
        table.insert(lines, line)
    end
    f:close()
    return lines
end

local function save_state(path)
    console.log("Saving state to: " .. path)
    savestate.save(path)
end

local function load_state(path)
    if file_exists(path) then
        console.log("Loading state from: " .. path)
        savestate.load(path)
    else
        console.log("No save state found at: " .. path)
    end
end

local function load_rom(path)
    if file_exists(path) then
        console.log("Loading ROM: " .. path)
        client.openrom(path)
    else
        console.log("ROM not found: " .. path)
    end
end

while true do
    -- Save trigger
    if file_exists("save_trigger.txt") then
        local lines = read_lines("save_trigger.txt")
        if #lines >= 1 then
            save_state(lines[1])
        end
        os.remove("save_trigger.txt")
    end

    -- Swap trigger
    if file_exists("swap_trigger.txt") then
        local lines = read_lines("swap_trigger.txt")
        if #lines >= 2 then
            local swap_time = tonumber(lines[1]) -- epoch seconds
            local game_name = lines[2]

            console.log(string.format("Swap scheduled for: %s (epoch: %d)",
                os.date("%Y-%m-%d %H:%M:%S", swap_time), swap_time))

            -- Wait until swap time with sub-second precision
            while socket.gettime() < swap_time do
                emu.frameadvance()
            end

            -- Perform swap
            local rom_path = "roms/" .. game_name
            local save_path = "saves/" .. game_name .. ".state"
            load_rom(rom_path)
            load_state(save_path)
        end
        os.remove("swap_trigger.txt")
    end

    emu.frameadvance()
end
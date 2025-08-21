<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <div class="flex items-center gap-2">
                <label for="startDelay" class="text-sm font-medium">
                    Start delay (seconds)
                </label>

                <x-filament::input id="startDelay" name="startDelay" type="number" min="0" step="1"
                    class="w-28" wire:model.defer="startDelay" placeholder="0" />
            </div>

            <x-filament::button color="success" wire:click="start" :disabled="$record?->start_at">
                Start
            </x-filament::button>

            <x-filament::button color="danger" wire:click="pause" :disabled="!$record?->start_at">
                Pause
            </x-filament::button>

            <x-filament::button color="warning" wire:click="triggerSwap" :disabled="!$record?->start_at">
                Trigger Swap
            </x-filament::button>

            <x-filament::button color="warning" wire:click="clearSaves">
                Clear Saves
            </x-filament::button>
        </div>

        @if ($record?->start_at)
            <div class="mt-2 text-sm text-gray-500">
                Mode: <strong>{{ $record->mode->label() }}</strong> |
                Round: <strong>{{ $record->current_round }}</strong> |
                @if ($record->swaps()->exists())
                    Next swap at:
                    <span x-data="{ swaptime: '' }" x-init="let t = new Date('{{ $record->swaps()->latest()->first()?->swap_at->toIso8601String() }}');
                    swaptime = t.toLocaleTimeString();" x-text="swaptime"></span>
                @endif
            </div>
            <div class="mt-2 text-sm text-gray-500">
                Scheduled to start at
                <span x-data="{ localTime: '' }" x-init="let dt = new Date('{{ $record->start_at->toIso8601String() }}');
                localTime = dt.toLocaleTimeString();" x-text="localTime"></span>
            </div>

            <div x-data="{
                startAt: new Date('{{ $record->start_at->toIso8601String() }}'),
                now: new Date(),
                get elapsed() {
                    let diff = Math.floor((this.now - this.startAt) / 1000);
                    let hours = String(Math.floor(diff / 3600)).padStart(2, '0');
                    let minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                    let seconds = String(diff % 60).padStart(2, '0');
                    return `${hours}:${minutes}:${seconds}`;
                },
                init() {
                    setInterval(() => this.now = new Date(), 1000);
                }
            }" x-init="init()" class="mt-1 text-sm font-semibold text-green-600">
                Time since started: <span x-text="elapsed"></span>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

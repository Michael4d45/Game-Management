<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex gap-4">
            <x-filament::button color="success" wire:click="start" :disabled="$record->start_at !== null">
                Start
            </x-filament::button>

            <x-filament::button color="danger" wire:click="pause" :disabled="$record->start_at === null">
                Pause
            </x-filament::button>
        </div>

        @if ($record->start_at)
            <div class="mt-2 text-sm text-gray-500">
                Scheduled to start at
                <span x-data="{ localTime: '' }" x-init="let dt = new Date('{{ $record->start_at->toIso8601String() }}');
                localTime = dt.toLocaleTimeString();" x-text="localTime">
                </span>
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
            }" x-init="init()" class="mt-1 text-sm text-green-600 font-semibold">
                Time since started: <span x-text="elapsed"></span>
            </div>
        @endif


    </x-filament::section>
</x-filament-widgets::widget>

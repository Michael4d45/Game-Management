<x-filament::widget>
    <x-filament::card>
        <div 
            x-data="{
                status: 'connecting',
                init() {
                    if (!window.Echo) {
                        console.warn('Echo is not loaded');
                        this.status = 'Echo missing';
                        return;
                    }

                    const channel = window.Echo.private('{{ $channel }}');

                    channel.subscribed(() => {
                        this.status = 'connected';
                    });

                    window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                        if (states.current === 'connected') {
                            this.status = 'connected';
                        } else if (states.current === 'connecting') {
                            this.status = 'connecting';
                        } else {
                            this.status = 'disconnected';
                        }
                    });

                    window.Echo.connector.pusher.connection.bind('error', (err) => {
                        console.error('Reverb connection error:', err);
                        this.status = 'disconnected';
                    });
                }
            }"
            x-init="init()"
            class="filament-widget"
        >
            <div class="text-sm">Session #{{ $record->id }}</div>
            <div class="font-medium">
                Status: 
                <span 
                    x-text="status" 
                    :class="{
                        'text-green-600': status === 'connected',
                        'text-red-600': status === 'disconnected',
                        'text-yellow-600': status === 'connecting'
                    }"
                ></span>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
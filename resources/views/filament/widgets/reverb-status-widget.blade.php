<x-filament::widget>
    <x-filament::card>
        <div x-data="{
            status: 'Connecting...',
            init() {
                if (window.__subscribed) return;
                window.__subscribed = true;
                if (!window.Echo) {
                    console.warn('Echo is not loaded');
                    this.status = 'Echo missing';
                    return;
                }
        
                const channelName = 'player.bob';
                console.log('Subscribing to', channelName);
                window.Echo.private(channelName);
                const sessionChannelName = 'session.test';
                console.log('Subscribing to', sessionChannelName);
                window.Echo.private(sessionChannelName);
        
                // Also log internal Pusher events
                if (window.Echo.connector.pusher) {
                    window.Echo.connector.pusher.connection.bind_global((eventName, data) => {
                        console.log('[Pusher Event]', eventName, data);
                    });
                }
                this.status = 'Listening for events...';
            }
        }" x-init="init()" class="filament-widget">
            <div class="text-sm">Session #{{ $record->id }}</div>
            <div class="text-xs text-gray-500" x-text="status"></div>
        </div>
    </x-filament::card>
</x-filament::widget>

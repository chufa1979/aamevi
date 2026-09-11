<x-filament-widgets::widget class="aamevi-current-date-time">
    <x-filament::section>
        <div
            x-data="{
                ahora: new Date(),
                tick() {
                    this.ahora = new Date()
                },
            }"
            x-init="setInterval(() => tick(), 1000)"
            class="flex flex-col gap-y-1"
        >
            <p
                x-text="ahora.toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })"
                class="text-sm font-medium capitalize text-gray-500 dark:text-gray-400"
            ></p>

            <p
                x-text="ahora.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit', second: '2-digit' })"
                class="text-2xl font-semibold tabular-nums text-gray-950 dark:text-white"
            ></p>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

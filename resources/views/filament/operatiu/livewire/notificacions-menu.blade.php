<div class="flex items-center">
    <div>
        @if ($noLlegides > 0)
            <div class="flex items-center space-x-1">
                <button
                    x-data="{}"
                    x-on:click="$dispatch('open-modal', { id: 'notificacions-modal' })"
                    class="flex items-center justify-center rounded-md px-2 py-1 bg-primary-500 text-white hover:bg-primary-600 transition-colors"
                >
                    <x-heroicon-s-bell class="w-5 h-5 mr-1" />
                    <span class="text-xs font-medium">{{ $noLlegides }}</span>
                </button>
            </div>
        @else
            <button
                x-data="{}"
                x-on:click="$dispatch('open-modal', { id: 'notificacions-modal' })"
                class="flex items-center justify-center w-10 h-10 rounded-full hover:bg-gray-500/5 focus:bg-gray-500/5 dark:hover:bg-gray-700 dark:focus:bg-gray-700 transition text-gray-500 dark:text-gray-400"
            >
                <span class="sr-only">Notificacions</span>
                <x-heroicon-o-bell class="w-6 h-6" />
            </button>
        @endif
    </div>

    <x-filament::modal
        id="notificacions-modal"
        width="md"
        alignment="right"
        :close-button="true"
    >
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">
                    Notificacions
                </h2>
                
                @if ($noLlegides > 0)
                    <button
                        wire:click="marcarTotesLlegides"
                        class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400"
                    >
                        Marcar totes com llegides
                    </button>
                @endif
            </div>
        </x-slot>
        
        <div class="space-y-4 max-h-[60vh] overflow-y-auto p-2">
            @forelse ($notificacions as $notificacio)
                <div 
                    class="p-4 rounded-lg {{ $notificacio->llegida ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-primary-50 dark:bg-primary-800/20 border-l-4 border-primary-500' }}"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="flex-shrink-0 w-2 h-2 rounded-full {{ $notificacio->llegida ? 'bg-gray-300 dark:bg-gray-600' : 'bg-primary-500' }}"></span>
                            <span class="text-sm font-medium text-{{ $notificacio->getTipusColor() }}-600 dark:text-{{ $notificacio->getTipusColor() }}-400">
                                {{ $notificacio->getTipusFormatted() }}
                            </span>
                        </div>
                        
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $notificacio->getTempsTranscorregut() }}
                        </span>
                    </div>
                    
                    <h3 class="mt-2 text-sm font-medium">{{ $notificacio->titol }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $notificacio->missatge }}</p>
                    
                    <div class="mt-3 flex items-center justify-between">
                        @if ($notificacio->teUrlAccio())
                            <a 
                                href="{{ $notificacio->url_accio }}" 
                                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400"
                            >
                                Veure detalls
                            </a>
                        @else
                            <span></span>
                        @endif
                        
                        @if (!$notificacio->llegida)
                            <button
                                wire:click="marcarNotificacioLlegida({{ $notificacio->id }})"
                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                            >
                                Marcar com llegida
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center">
                    <x-heroicon-o-bell class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">No hi ha notificacions</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quan tinguis notificacions apareixeran aquí.</p>
                </div>
            @endforelse
        </div>
    </x-filament::modal>
</div>
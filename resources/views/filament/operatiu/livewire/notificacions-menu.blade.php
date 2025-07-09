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
                    <br><br><br>Marcar totes com llegides
                    </button>
                @endif
            </div>
        </x-slot>
        
        <div x-data="{
            notificacions: @js($notificacions),
            paginaActual: 1,
            elementsPerPagina: 4,
            get totalPagines() {
                return Math.ceil(this.notificacions.length / this.elementsPerPagina);
            },
            get notificacionsActuals() {
                const inici = (this.paginaActual - 1) * this.elementsPerPagina;
                const fi = inici + this.elementsPerPagina;
                return this.notificacions.slice(inici, fi);
            },
            paginaAnterior() {
                if (this.paginaActual > 1) {
                    this.paginaActual--;
                }
            },
            paginaSeguent() {
                if (this.paginaActual < this.totalPagines) {
                    this.paginaActual++;
                }
            }
        }" class="space-y-4 overflow-y-auto p-2">
            <template x-if="notificacions.length === 0">
                <div class="p-6 text-center">
                    <x-heroicon-o-bell class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">No hi ha notificacions</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quan tinguis notificacions apareixeran aquí.</p>
                </div>
            </template>
            
            <template x-for="(notificacio, index) in notificacionsActuals" :key="notificacio.id">
                <div :class="`p-4 rounded-lg ${notificacio.llegida ? 'bg-gray-50 dark:bg-gray-800/50' : 'bg-gray-200 dark:bg-gray-700 border-l-4 border-primary-500'}`">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center space-x-2">
                            <span :class="`flex-shrink-0 w-2 h-2 rounded-full ${notificacio.llegida ? 'bg-gray-300 dark:bg-gray-600' : 'bg-primary-500'}`"></span>
                            <span class="text-sm font-medium" :class="`text-${notificacio.tipus === 'info' ? 'blue' : notificacio.tipus === 'warning' ? 'yellow' : notificacio.tipus === 'error' ? 'red' : 'green'}-600 dark:text-${notificacio.tipus === 'info' ? 'blue' : notificacio.tipus === 'warning' ? 'yellow' : notificacio.tipus === 'error' ? 'red' : 'green'}-400`" x-text="notificacio.tipus === 'info' ? 'Informació' : notificacio.tipus === 'warning' ? 'Avís' : notificacio.tipus === 'error' ? 'Error' : 'Èxit'"></span>
                        </div>
                        
                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="notificacio.created_at"></span>
                    </div>
                    
                    <h3 class="mt-2 text-sm font-medium" x-text="notificacio.titol"></h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="notificacio.missatge"></p>
                    
                    <div class="mt-3 flex items-center justify-between">
                        <template x-if="notificacio.url_accio">
                            <a 
                                :href="notificacio.url_accio" 
                                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-500 dark:hover:text-primary-400"
                            >
                                Veure detalls
                            </a>
                        </template>
                        <template x-if="!notificacio.url_accio">
                            <span></span>
                        </template>
                        
                        <template x-if="!notificacio.llegida">
                            <button
                                @click="$wire.marcarNotificacioLlegida(notificacio.id)"
                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                            >
                                Marcar com llegida
                            </button>
                        </template>
                    </div>
                </div>
            </template>
            
            <template x-if="totalPagines > 1">
                <div class="py-3 px-2 border-t border-gray-200 dark:border-gray-700">
                    <nav role="navigation" aria-label="Navegació de paginació" class="flex items-center justify-between">
                        <div class="flex justify-between flex-1 sm:hidden">
                            <button 
                                @click="paginaAnterior()"
                                :disabled="paginaActual === 1"
                                :class="`relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md ${paginaActual === 1 ? 'text-gray-300 bg-white dark:bg-gray-800 cursor-not-allowed' : 'text-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'}`"
                            >
                                Anterior
                            </button>
                            
                            <button 
                                @click="paginaSeguent()"
                                :disabled="paginaActual === totalPagines"
                                :class="`relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium rounded-md ${paginaActual === totalPagines ? 'text-gray-300 bg-white dark:bg-gray-800 cursor-not-allowed' : 'text-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'}`"
                            >
                                Següent
                            </button>
                        </div>
                        
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Mostrant <span class="font-medium" x-text="((paginaActual - 1) * elementsPerPagina) + 1"></span>
                                    a <span class="font-medium" x-text="Math.min(paginaActual * elementsPerPagina, notificacions.length)"></span>
                                    de <span class="font-medium" x-text="notificacions.length"></span>&nbsp;&nbsp;
                                </p>
                            </div>
                            
                            <div>
                                <span class="relative z-0 inline-flex shadow-sm rounded-md">
                                    <!-- Botón Anterior -->
                                    <button
                                        @click="paginaAnterior()"
                                        :disabled="paginaActual === 1"
                                        :class="`relative inline-flex items-center px-2 py-2 rounded-l-md border ${paginaActual === 1 ? 'border-gray-300 bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed' : 'border-gray-300 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'}`"
                                    >
                                        <span class="sr-only">Anterior</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    
                                    <!-- Páginas -->
                                    <template x-for="pagina in totalPagines" :key="pagina">
                                        <button
                                            @click="paginaActual = pagina"
                                            :class="`-ml-px relative inline-flex items-center px-4 py-2 border ${paginaActual === pagina ? 'z-10 bg-primary-50 dark:bg-primary-900 border-primary-500 dark:border-primary-500 text-primary-600 dark:text-primary-200' : 'border-gray-300 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'}`"
                                        >
                                            <span x-text="pagina"></span>
                                        </button>
                                    </template>
                                    
                                    <!-- Botón Siguiente -->
                                    <button
                                        @click="paginaSeguent()"
                                        :disabled="paginaActual === totalPagines"
                                        :class="`-ml-px relative inline-flex items-center px-2 py-2 rounded-r-md border ${paginaActual === totalPagines ? 'border-gray-300 bg-white dark:bg-gray-800 text-gray-300 dark:text-gray-600 cursor-not-allowed' : 'border-gray-300 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'}`"
                                    >
                                        <span class="sr-only">Següent</span>
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </nav>
                </div>
            </template>
        </div>
    </x-filament::modal>
</div>
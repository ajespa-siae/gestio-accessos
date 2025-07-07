@php
    $darkMode = \Filament\Facades\Filament::hasDarkMode();
    $icon = $darkMode ? 'heroicon-o-sun' : 'heroicon-o-moon';
    $tooltip = $darkMode ? __('filament-panels::layout.actions.theme_switcher.light') : __('filament-panels::layout.actions.theme_switcher.dark');
    $theme = $darkMode ? 'light' : 'dark';
    $colorClass = 'text-gray-400 hover:text-primary-500';
@endphp

<button
    x-data="{ isDark: document.documentElement.classList.contains('dark') }"
    x-on:click="
        isDark = !isDark;
        document.documentElement.classList.toggle('dark', isDark);
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        $dispatch('theme-changed', { theme: isDark ? 'dark' : 'light' });
    "
    type="button"
    x-bind:title="isDark ? 'Tema claro' : 'Tema oscuro'"
    class="flex items-center justify-center w-10 h-10 text-gray-500 transition-colors rounded-full hover:bg-gray-500/5 hover:text-primary-500 focus:bg-primary-500/10 focus:outline-none"
>
    <template x-if="isDark">
        <x-heroicon-o-sun class="w-5 h-5" />
    </template>
    <template x-if="!isDark">
        <x-heroicon-o-moon class="w-5 h-5" />
    </template>
</button>

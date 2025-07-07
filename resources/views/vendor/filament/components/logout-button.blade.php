@props([
    'action',
    'icon',
    'label' => __('filament-panels::pages/auth/logout.actions.log_out.label'),
])

<x-filament::button
    :form="\Filament\Support\prepare_inherited_attributes(new \Illuminate\View\ComponentAttributeBag([
        'action' => $action,
        'method' => 'post',
        'class' => 'w-full',
    ]))"
    :tag="\Filament\Support\get_filament_button_tag()"
    :color="'gray'"
    :icon="$icon"
    :icon-position="'after'"
    :icon-size="'sm'"
    :size="'sm'"
    :tooltip="$label"
    :attributes="\Filament\Support\prepare_inherited_attributes($attributes)"
>
    {{ $label }}
</x-filament::button>

@once
    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                Livewire.on('loggedOut', () => {
                    window.location.href = '/';
                });
            });
        </script>
    @endpush
@endonce

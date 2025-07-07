@php
    $user = \Illuminate\Support\Facades\Auth::user();
    
    $profile = $user?->name;
    $profileUrl = null;
    $profileImage = $user?->profile_photo_url;
    $profileImageAlt = $user?->name;
    
    $navigation = [];
    
    if ($user?->can('view-profile')) {
        $navigation[] = [
            'label' => __('filament-panels::layout.actions.profile.label'),
            'icon' => 'heroicon-o-user',
            'url' => '#',
        ];
    }
    
    if ($user?->can('view-settings')) {
        $navigation[] = [
            'label' => __('filament-panels::layout.actions.settings.label'),
            'icon' => 'heroicon-o-cog-6-tooth',
            'url' => '#',
        ];
    }
    
    $darkMode = \Filament\Facades\Filament::hasDarkMode();
@endphp



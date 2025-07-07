@props([
    'darkMode' => false,
    'navigation' => [],
    'profile' => null,
    'profileUrl' => null,
    'profileImage' => null,
    'profileImageAlt' => null,
])

<div class="fi-user-menu">
    <x-filament::dropdown
        :dark-mode="$darkMode"
        placement="bottom-end"
        :shift="true"
        :teleport="true"
        :width="'xs'"
        :offset="8"
    >
        <x-slot name="trigger">
            <button
                type="button"
                class="flex items-center justify-center rounded-full bg-gray-100 p-2 transition hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
            >
                @if ($profileImage)
                    <img
                        src="{{ $profileImage }}"
                        alt="{{ $profileImageAlt }}"
                        class="h-6 w-6 rounded-full"
                    />
                @else
                    <x-filament::icon
                        name="heroicon-o-user"
                        class="h-5 w-5 text-gray-600 dark:text-gray-400"
                    />
                @endif
            </button>
        </x-slot>

        <div class="p-2">
            @if ($profile || $profileUrl)
                <div class="mb-2 flex items-center gap-3 p-3">
                    @if ($profileImage)
                        <img
                            src="{{ $profileImage }}"
                            alt="{{ $profileImageAlt }}"
                            class="h-10 w-10 rounded-full"
                        />
                    @endif

                    <div class="grid flex-1">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $profile }}
                        </h3>

                        @if ($profileUrl)
                            <a
                                href="{{ $profileUrl }}"
                                class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                            >
                                {{ __('filament-panels::layout.actions.profile.label') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if (count($navigation))
                <ul class="space-y-1">
                    @foreach ($navigation as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                class="flex w-full items-center gap-3 rounded-lg p-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                @if ($icon = ($item['icon'] ?? null))
                                    <x-filament::icon
                                        :name="$icon"
                                        class="h-5 w-5"
                                    />
                                @endif

                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full items-center gap-3 rounded-lg p-2 text-sm text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <x-filament::icon
                            name="heroicon-o-arrow-left-on-rectangle"
                            class="h-5 w-5"
                        />
                        <span>{{ __('filament-panels::pages/auth/logout.actions.log_out.label') }}</span>
                    </button>
                </form>
            </div>
        </div>
    </x-filament::dropdown>
</div>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        @include('partials.branding-head')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div id="top-progress"></div>
        <x-toasts />

        @include('partials.flash-toasts')
        @include('partials.announcements')

        <div class="min-h-screen bg-gray-100"
             x-data="{ mobileOpen: false, collapsed: localStorage.getItem('xp_sidebar') === '1' }">

            <!-- Mobile overlay -->
            <div x-show="mobileOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" x-on:click="mobileOpen = false"></div>

            <!-- Sidebar -->
            <aside :class="[
                       collapsed ? 'lg:w-[4.5rem]' : 'lg:w-64',
                       mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                   ]"
                   class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 flex flex-col transition-all duration-200 ease-in-out">

                <!-- Brand -->
                <div class="flex items-center gap-2.5 h-14 px-4 border-b border-gray-200 shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                        <img src="{{ asset('xerin-icon.webp') }}" alt="Xerin Group" class="w-8 h-8 shrink-0 rounded-sm" x-show="collapsed">
                        <x-application-logo class="h-9 w-auto max-w-[150px]" x-show="!collapsed" />
                    </a>
                </div>

                <!-- Nav — rendered from the central NavigationRegistry -->
                @php $navGroups = app(App\Services\Navigation\NavigationRegistry::class)->visible(); @endphp
                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4">
                    @foreach ($navGroups as $group => $items)
                        <div>
                            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400" x-show="!collapsed">{{ $group }}</p>
                            <div class="space-y-1">
                                @foreach ($items as $item)
                                    @php $children = $item->visibleChildren(); @endphp
                                    @if ($children === [])
                                        <a href="{{ $item->href() ?? '#' }}"
                                           title="{{ $item->label }}"
                                           class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                                                  {{ $item->isActive() ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item->icon }}" />
                                            </svg>
                                            <span x-show="!collapsed" class="truncate">{{ $item->label }}</span>
                                        </a>
                                    @else
                                        <div x-data="{ open: {{ $item->isActive() ? 'true' : 'false' }} }">
                                            <button type="button" x-on:click="open = !open"
                                                    title="{{ $item->label }}"
                                                    class="w-full flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                                                           {{ $item->isActive() ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item->icon }}" />
                                                </svg>
                                                <span x-show="!collapsed" class="flex-1 text-left truncate">{{ $item->label }}</span>
                                                <svg x-show="!collapsed" class="w-3.5 h-3.5 shrink-0 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''"
                                                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                            <div x-show="open && !collapsed" x-cloak x-transition.opacity.duration.150ms class="mt-1 space-y-0.5">
                                                @foreach ($children as $child)
                                                    <a href="{{ $child->href() ?? '#' }}"
                                                       title="{{ $child->label }}"
                                                       class="flex items-center gap-2.5 rounded-md py-1.5 pl-[2.125rem] pr-3 text-[13px] font-medium transition-colors
                                                              {{ $child->isActive() ? 'text-gray-900 bg-gray-100' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900' }}">
                                                        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $child->isActive() ? 'bg-indigo-500' : 'bg-gray-300' }}"></span>
                                                        <span x-show="!collapsed" class="truncate">{{ $child->label }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <!-- User -->
                <div class="border-t border-gray-200 p-3 shrink-0" x-data="{ open: false }">
                    <div class="relative">
                        <button x-on:click="open = !open" class="w-full flex items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-gray-50 transition-colors">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-800 text-white text-xs font-bold shrink-0">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="min-w-0 flex-1 text-left" x-show="!collapsed">
                                <span class="block font-medium text-gray-900 truncate">{{ auth()->user()->name }}</span>
                                <span class="block text-xs text-gray-500 truncate">{{ auth()->user()->email }}</span>
                            </span>
                            <svg x-show="!collapsed" class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition x-on:click.outside="open = false"
                             class="absolute bottom-full left-0 mb-2 w-full min-w-[10rem] rounded-md border border-gray-200 bg-white shadow-lg py-1">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <div :class="collapsed ? 'lg:pl-[4.5rem]' : 'lg:pl-64'" class="flex flex-col min-h-screen transition-[padding] duration-200 ease-in-out">

                <!-- Header bar -->
                <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6">
                    <!-- Mobile trigger -->
                    <button x-on:click="mobileOpen = true" class="lg:hidden p-2 -ml-2 rounded-md text-gray-500 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <!-- Desktop collapse trigger -->
                    <button x-on:click="collapsed = !collapsed; localStorage.setItem('xp_sidebar', collapsed ? '1' : '0')" class="hidden lg:flex p-2 -ml-2 rounded-md text-gray-500 hover:bg-gray-100">
                        <svg x-show="!collapsed" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
                        </svg>
                        <svg x-show="collapsed" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5l7.5 7.5-7.5 7.5m6-15l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>

                    <div class="h-5 w-px bg-gray-200"></div>

                    <!-- Breadcrumb -->
                    <nav class="flex items-center gap-1.5 text-sm min-w-0">
                        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-900 transition-colors">{{ config('app.name', 'XerinERP') }}</a>
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                        <span class="font-medium text-gray-900 truncate">
                            {{ $header ?? __('Dashboard') }}
                        </span>
                    </nav>

                    <!-- Language selector (EN/SW) -->
                    <form method="POST" action="{{ route('locale.set') }}" class="ml-auto flex items-center">
                        @csrf
                        <select name="locale" onchange="this.form.submit()"
                                class="rounded-sm border-gray-200 text-xs text-gray-600 py-1 pl-2 pr-6 focus:border-xerin-500 focus:ring-xerin-500">
                            <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
                            <option value="sw" @selected(app()->getLocale() === 'sw')>SW</option>
                        </select>
                    </form>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>

                @php $__ft = app(\App\Services\SettingsService::class); @endphp
                <footer class="px-6 py-4 text-center text-xs text-gray-400">
                    © {{ $__ft->get('branding.footer_year_override') ?: now()->year }} {{ $__ft->get('branding.footer_text', 'Xerin Group Company Limited. All Rights Reserved.') }}
                    @if ($__ft->get('branding.privacy_url')) · <a href="{{ $__ft->get('branding.privacy_url') }}" class="hover:text-gray-600">Privacy</a>@endif
                    @if ($__ft->get('branding.terms_url')) · <a href="{{ $__ft->get('branding.terms_url') }}" class="hover:text-gray-600">Terms</a>@endif
                </footer>
            </div>
        </div>
    </body>
</html>

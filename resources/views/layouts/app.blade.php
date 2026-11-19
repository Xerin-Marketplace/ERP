<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div id="top-progress"></div>
        <x-toasts />

        @if (session('status') || session('success') || session('error'))
            <script>
                window.addEventListener('DOMContentLoaded', () => window.toast(
                    @json(session('error') ?? session('success') ?? session('status')),
                    {{ session('error') ? "'error'" : "'success'" }}
                ));
            </script>
        @endif

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
                        <x-application-logo class="w-8 h-8 shrink-0" />
                        <span class="font-bold text-gray-900 truncate" x-show="!collapsed">{{ config('app.name', 'XerinERP') }}</span>
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
                                    <a href="{{ $item->href() ?? '#' }}"
                                       title="{{ $item->label }}"
                                       class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                                              {{ $item->isActive() ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item->icon }}" />
                                        </svg>
                                        <span x-show="!collapsed" class="truncate">{{ $item->label }}</span>
                                    </a>
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
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>

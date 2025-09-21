@props(['currentPage' => null])

<nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-slate-200/50 dark:border-slate-700/50" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-14">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center">
                    <div class="w-6 h-6 bg-slate-700 dark:bg-slate-300 rounded-sm flex items-center justify-center mr-2">
                        <span class="text-slate-100 dark:text-slate-900 text-xs font-medium">G</span>
                    </div>
                    <span class="text-slate-700 dark:text-slate-300 text-sm font-medium">Games</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:block">
                <div class="ml-8 flex items-center space-x-6">
                    <a href="{{ url('/') }}" 
                       class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 text-sm font-medium transition-colors duration-200 
                              {{ $currentPage === 'home' ? 'text-slate-800 dark:text-slate-200' : '' }}">
                        Home
                    </a>
                    <a href="{{ url('/games') }}" 
                       class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 text-sm font-medium transition-colors duration-200
                              {{ $currentPage === 'games' ? 'text-slate-800 dark:text-slate-200' : '' }}">
                        All Games
                    </a>
                </div>
            </div>

            <!-- Auth Links -->
            <div class="hidden md:block">
                @if (Route::has('login'))
                    <div class="ml-4 flex items-center md:ml-6">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 text-sm font-medium transition-colors duration-200">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 text-sm font-medium transition-colors duration-200">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="ml-4 text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 text-sm font-medium transition-colors duration-200">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" 
                        class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 inline-flex items-center justify-center p-2 transition-colors duration-200">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen }" 
                              class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen }" 
                              class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-100 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border-t border-slate-200/50 dark:border-slate-700/50">
            <a href="{{ url('/') }}" 
               class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 block px-3 py-2 text-sm font-medium transition-colors duration-200
                      {{ $currentPage === 'home' ? 'text-slate-800 dark:text-slate-200' : '' }}">
                Home
            </a>
            <a href="{{ url('/games') }}" 
               class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 block px-3 py-2 text-sm font-medium transition-colors duration-200
                      {{ $currentPage === 'games' ? 'text-slate-800 dark:text-slate-200' : '' }}">
                All Games
            </a>

            <!-- Mobile Auth Links -->
            @if (Route::has('login'))
                <div class="border-t border-slate-200/50 dark:border-slate-700/50 pt-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 block px-3 py-2 text-sm font-medium transition-colors duration-200">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 block px-3 py-2 text-sm font-medium transition-colors duration-200">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" 
                               class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 block px-3 py-2 text-sm font-medium transition-colors duration-200">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </div>
</nav>

<!-- Spacer for fixed navigation -->
<div class="h-14"></div>

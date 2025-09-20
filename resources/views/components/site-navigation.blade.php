@props(['currentPage' => null])

<nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border-b border-slate-200 dark:border-slate-700" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center">
                    <div class="w-8 h-8 bg-slate-900 dark:bg-slate-100 rounded-md flex items-center justify-center mr-3">
                        <span class="text-slate-100 dark:text-slate-900 text-sm font-medium">G</span>
                    </div>
                    <span class="text-slate-900 dark:text-slate-100 text-lg font-medium">Games</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:block">
                <div class="ml-10 flex items-baseline space-x-1">
                    <a href="{{ url('/') }}" 
                       class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 
                              {{ $currentPage === 'home' ? 'bg-slate-100 dark:bg-slate-800' : '' }}">
                        Home
                    </a>
                    <a href="{{ url('/games') }}" 
                       class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200
                              {{ $currentPage === 'games' ? 'bg-slate-100 dark:bg-slate-800' : '' }}">
                        All Games
                    </a>
                    
                    <!-- Quick Game Links -->
                    <div class="relative group">
                        <button class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                            Quick Play
                            <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Dropdown -->
                        <div class="absolute left-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <div class="py-2">
                                <a href="{{ url('/checkers') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    ⚫ Checkers
                                </a>
                                <a href="{{ url('/connect4') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    🔴 Connect 4
                                </a>
                                <a href="{{ url('/solitaire') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    🃏 Solitaire
                                </a>
                                <a href="{{ url('/tic-tac-toe') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    ❌ Tic Tac Toe
                                </a>
                                <a href="{{ url('/nine-mens-morris') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    ⚫ Nine Men's Morris
                                </a>
                                <a href="{{ url('/2048') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    🔢 2048
                                </a>
                                <a href="{{ url('/peg-solitaire') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    🔵 Peg Solitaire
                                </a>
                                <a href="{{ url('/war') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors duration-200">
                                    ⚔️ War
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auth Links -->
            <div class="hidden md:block">
                @if (Route::has('login'))
                    <div class="ml-4 flex items-center md:ml-6">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-100 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" 
                                   class="ml-4 bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-slate-200 text-slate-100 dark:text-slate-900 px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
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
                        class="bg-slate-100 dark:bg-slate-800 inline-flex items-center justify-center p-2 rounded-md text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors duration-200">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
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
        <div class="px-2 pt-2 pb-3 space-y-1 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-700">
            <a href="{{ url('/') }}" 
               class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200
                      {{ $currentPage === 'home' ? 'bg-slate-100 dark:bg-slate-800' : '' }}">
                Home
            </a>
            <a href="{{ url('/games') }}" 
               class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200
                      {{ $currentPage === 'games' ? 'bg-slate-100 dark:bg-slate-800' : '' }}">
                All Games
            </a>
            
            <!-- Mobile Game Links -->
            <div class="px-3 py-2">
                <div class="text-slate-500 dark:text-slate-500 text-sm font-medium mb-2">Quick Play</div>
                <div class="space-y-1">
                    <a href="{{ url('/checkers') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">⚫ Checkers</a>
                    <a href="{{ url('/connect4') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">🔴 Connect 4</a>
                    <a href="{{ url('/solitaire') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">🃏 Solitaire</a>
                    <a href="{{ url('/tic-tac-toe') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">❌ Tic Tac Toe</a>
                    <a href="{{ url('/nine-mens-morris') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">⚫ Nine Men's Morris</a>
                    <a href="{{ url('/2048') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">🔢 2048</a>
                    <a href="{{ url('/peg-solitaire') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">🔵 Peg Solitaire</a>
                    <a href="{{ url('/war') }}" class="block text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 py-1 text-sm">⚔️ War</a>
                </div>
            </div>

            <!-- Mobile Auth Links -->
            @if (Route::has('login'))
                <div class="border-t border-slate-200 dark:border-slate-700 pt-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" 
                           class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" 
                               class="text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
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
<div class="h-16"></div>

@props([
    'hints' => [],
    'position' => 'bottom-right'
])

@if(!empty($hints))
<div class="fixed {{ $position === 'bottom-right' ? 'bottom-4 right-4' : 'bottom-4 left-4' }} z-50 max-w-sm">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100">
                💡 Hints
            </h3>
            <button onclick="this.parentElement.parentElement.parentElement.style.display='none'" 
                    class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="space-y-2">
            @foreach($hints as $hint)
                <div class="text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-700 rounded p-2">
                    {{ $hint }}
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

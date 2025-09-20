@props([
    'hints' => [],
    'show' => false,
    'position' => 'bottom-right' // top-left, top-right, bottom-left, bottom-right, center
])

<div x-data="{ 
    showHints: {{ $show ? 'true' : 'false' }},
    hints: @js($hints),
    currentHintIndex: 0,
    autoRotate: true,
    rotateInterval: null,
    startAutoRotate() {
        if (this.autoRotate && this.hints.length > 1) {
            this.rotateInterval = setInterval(() => {
                this.currentHintIndex = (this.currentHintIndex + 1) % this.hints.length;
            }, 4000);
        }
    },
    stopAutoRotate() {
        if (this.rotateInterval) {
            clearInterval(this.rotateInterval);
            this.rotateInterval = null;
        }
    },
    toggleHints() {
        this.showHints = !this.showHints;
        if (this.showHints) {
            this.startAutoRotate();
        } else {
            this.stopAutoRotate();
        }
    },
    selectHint(index) {
        this.currentHintIndex = index;
        this.autoRotate = false;
        this.stopAutoRotate();
    }
}" 
x-init="if (showHints && hints.length > 1) startAutoRotate()"
class="hint-system">

    <!-- Hint Toggle Button -->
    <button @click="toggleHints()" 
            class="hint-toggle-btn hint-position-{{ $position }}"
            :class="{ 'active': showHints }"
            title="Toggle hints">
        <svg class="hint-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10"/>
            <path d="9,9h0a3,3,0,0,1,6,0c0,2-3,3-3,3"/>
            <path d="M12,17h0"/>
        </svg>
        <span class="hint-count" x-show="hints.length > 0" x-text="hints.length"></span>
    </button>

    <!-- Hint Panel -->
    <div x-show="showHints && hints.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="hint-panel hint-panel-{{ $position }}">
        
        <!-- Panel Header -->
        <div class="hint-header">
            <div class="hint-title">
                <svg class="hint-header-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                Smart Hints
            </div>
            <button @click="autoRotate = !autoRotate; autoRotate ? startAutoRotate() : stopAutoRotate()" 
                    class="auto-rotate-btn"
                    :class="{ 'active': autoRotate }"
                    title="Toggle auto-rotate">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M1 4v6h6m16 10v-6h-6M3.51 15a9 9 0 1013.2-3.3L12 12"/>
                </svg>
            </button>
        </div>

        <!-- Current Hint Display -->
        <div class="hint-content" x-show="hints.length > 0">
            <template x-for="(hint, index) in hints" :key="index">
                <div x-show="currentHintIndex === index" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-4"
                     class="hint-item">
                    
                    <!-- Hint Priority Badge -->
                    <div class="hint-priority" :class="`priority-${hint.priority >= 8 ? 'high' : hint.priority >= 5 ? 'medium' : 'low'}`">
                        <span x-text="hint.priority >= 8 ? '!' : hint.priority >= 5 ? '★' : 'i'"></span>
                    </div>
                    
                    <!-- Hint Content -->
                    <div class="hint-text">
                        <div class="hint-description" x-text="hint.description"></div>
                        <div class="hint-reasoning" x-show="hint.reasoning" x-text="hint.reasoning"></div>
                    </div>
                    
                    <!-- Hint Type Badge -->
                    <div class="hint-type" :class="`type-${hint.type}`" x-text="hint.type"></div>
                </div>
            </template>
        </div>

        <!-- Hint Navigation -->
        <div class="hint-navigation" x-show="hints.length > 1">
            <template x-for="(hint, index) in hints" :key="index">
                <button @click="selectHint(index)" 
                        class="hint-nav-dot"
                        :class="{ 'active': currentHintIndex === index }"
                        :title="`Hint ${index + 1}: ${hint.type}`">
                </button>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="hints.length === 0" class="hint-empty">
            <svg class="hint-empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"/>
                <circle cx="12" cy="12" r="5"/>
            </svg>
            <div class="hint-empty-text">No hints available</div>
            <div class="hint-empty-subtext">You're doing great! Keep exploring.</div>
        </div>
    </div>

    <style>
        .hint-system {
            position: fixed;
            z-index: 1000;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .hint-toggle-btn {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hint-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        }

        .hint-toggle-btn.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            transform: scale(1.1);
        }

        .hint-icon {
            width: 24px;
            height: 24px;
            stroke-width: 2;
        }

        .hint-count {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            border: 2px solid white;
        }

        .hint-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            width: 320px;
            max-height: 400px;
            overflow: hidden;
        }

        .hint-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .hint-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .hint-header-icon {
            width: 18px;
            height: 18px;
            stroke-width: 2;
        }

        .auto-rotate-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 6px;
            color: white;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .auto-rotate-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .auto-rotate-btn.active {
            background: rgba(255,255,255,0.4);
            animation: spin 2s linear infinite;
        }

        .auto-rotate-btn svg {
            width: 16px;
            height: 16px;
            stroke-width: 2;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hint-content {
            padding: 20px;
            min-height: 120px;
        }

        .hint-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .hint-priority {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            flex-shrink: 0;
        }

        .priority-high {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }

        .priority-medium {
            background: linear-gradient(135deg, #feca57, #ff9ff3);
            color: white;
        }

        .priority-low {
            background: linear-gradient(135deg, #48dbfb, #0abde3);
            color: white;
        }

        .hint-text {
            flex: 1;
        }

        .hint-description {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .hint-reasoning {
            font-size: 12px;
            color: #7f8c8d;
            line-height: 1.3;
        }

        .hint-type {
            background: #ecf0f1;
            color: #34495e;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .type-critical { background: #ffebee; color: #c62828; }
        .type-immediate { background: #e8f5e8; color: #2e7d32; }
        .type-strategic { background: #e3f2fd; color: #1565c0; }
        .type-tactical { background: #fff3e0; color: #ef6c00; }
        .type-defensive { background: #fce4ec; color: #ad1457; }

        .hint-navigation {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 16px 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }

        .hint-nav-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            border: none;
            background: #ced4da;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .hint-nav-dot.active {
            background: #667eea;
            transform: scale(1.25);
        }

        .hint-nav-dot:hover {
            background: #adb5bd;
        }

        .hint-empty {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .hint-empty-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            opacity: 0.5;
            stroke-width: 1;
        }

        .hint-empty-text {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .hint-empty-subtext {
            font-size: 12px;
            opacity: 0.7;
        }

        /* Position Classes */
        .hint-position-top-left { top: 20px; left: 20px; }
        .hint-position-top-right { top: 20px; right: 20px; }
        .hint-position-bottom-left { bottom: 20px; left: 20px; }
        .hint-position-bottom-right { bottom: 20px; right: 20px; }
        .hint-position-center { top: 50%; left: 50%; transform: translate(-50%, -50%); }

        .hint-panel-top-left { position: absolute; top: 60px; left: 0; }
        .hint-panel-top-right { position: absolute; top: 60px; right: 0; }
        .hint-panel-bottom-left { position: absolute; bottom: 60px; left: 0; }
        .hint-panel-bottom-right { position: absolute; bottom: 60px; right: 0; }
        .hint-panel-center { position: absolute; top: 60px; left: 50%; transform: translateX(-50%); }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hint-panel {
                width: 280px;
                position: fixed !important;
                bottom: 80px !important;
                left: 50% !important;
                right: auto !important;
                top: auto !important;
                transform: translateX(-50%) !important;
            }
            
            .hint-toggle-btn {
                width: 44px;
                height: 44px;
            }
            
            .hint-content {
                padding: 16px;
                min-height: 100px;
            }
        }
    </style>
</div>

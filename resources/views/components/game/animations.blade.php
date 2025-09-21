{{-- 
    Liminal Game Animations Component
    
    Provides consistent, subtle animations that enhance without overwhelming.
    All animations respect user preferences for reduced motion.
--}}

<style>
    /* Base Animation System - Liminal Principles */
    
    /* Smooth, purposeful transitions */
    .game-transition {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .game-transition-slow {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Gentle entrance animations */
    .fade-in {
        animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .slide-in-up {
        animation: slideInUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .slide-in-down {
        animation: slideInDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .scale-in {
        animation: scaleIn 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Subtle hover effects */
    .hover-lift {
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hover-lift:hover {
        transform: translateY(-1px);
    }
    
    .hover-scale {
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hover-scale:hover {
        transform: scale(1.02);
    }
    
    /* Game-specific animations */
    
    /* Card animations */
    .card-flip {
        animation: cardFlip 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-slide {
        animation: cardSlide 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-stack {
        animation: cardStack 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Piece animations */
    .piece-drop {
        animation: pieceDrop 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .piece-move {
        animation: pieceMove 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .piece-capture {
        animation: pieceCapture 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Tile animations (for 2048) */
    .tile-appear {
        animation: tileAppear 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .tile-merge {
        animation: tileMerge 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .tile-slide {
        animation: tileSlide 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Win/success animations */
    .success-pulse {
        animation: successPulse 1s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    
    .win-celebration {
        animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Subtle indicator animations */
    .gentle-pulse {
        animation: gentlePulse 2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    
    .soft-glow {
        animation: softGlow 1.5s cubic-bezier(0.4, 0, 0.2, 1) infinite alternate;
    }
    
    /* Loading animations */
    .loading-dots {
        animation: loadingDots 1.4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }
    
    .loading-fade {
        animation: loadingFade 1s cubic-bezier(0.4, 0, 0.2, 1) infinite alternate;
    }
    
    /* Keyframe definitions */
    
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(1rem);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-1rem);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes scaleIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    @keyframes cardFlip {
        0% {
            transform: rotateY(0deg);
        }
        50% {
            transform: rotateY(90deg);
        }
        100% {
            transform: rotateY(0deg);
        }
    }
    
    @keyframes cardSlide {
        from {
            transform: translateX(-100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes cardStack {
        from {
            transform: translateY(-0.5rem) scale(1.05);
            opacity: 0.8;
        }
        to {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }
    
    @keyframes pieceDrop {
        0% {
            transform: translateY(-100%) scale(1.1);
            opacity: 0.8;
        }
        70% {
            transform: translateY(0) scale(0.95);
            opacity: 1;
        }
        100% {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
    }
    
    @keyframes pieceMove {
        from {
            transform: scale(1.05);
        }
        to {
            transform: scale(1);
        }
    }
    
    @keyframes pieceCapture {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.2);
            opacity: 0.7;
        }
        100% {
            transform: scale(0.8);
            opacity: 0;
        }
    }
    
    @keyframes tileAppear {
        from {
            transform: scale(0);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    @keyframes tileMerge {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
        }
    }
    
    @keyframes tileSlide {
        from {
            transform: translateX(0);
        }
        to {
            transform: translateX(var(--slide-distance, 0));
        }
    }
    
    @keyframes successPulse {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.02);
        }
    }
    
    @keyframes winCelebration {
        0% {
            transform: scale(1);
        }
        25% {
            transform: scale(1.05) rotate(1deg);
        }
        50% {
            transform: scale(1.1) rotate(-1deg);
        }
        75% {
            transform: scale(1.05) rotate(1deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
        }
    }
    
    @keyframes gentlePulse {
        0%, 100% {
            opacity: 0.6;
        }
        50% {
            opacity: 1;
        }
    }
    
    @keyframes softGlow {
        from {
            box-shadow: 0 0 5px rgb(59 130 246 / 0.3);
        }
        to {
            box-shadow: 0 0 10px rgb(59 130 246 / 0.5);
        }
    }
    
    @keyframes loadingDots {
        0%, 20% {
            color: rgba(255, 255, 255, 0);
            text-shadow: 0.25em 0 0 rgba(255, 255, 255, 0), 
                         0.5em 0 0 rgba(255, 255, 255, 0);
        }
        40% {
            color: white;
            text-shadow: 0.25em 0 0 rgba(255, 255, 255, 0), 
                         0.5em 0 0 rgba(255, 255, 255, 0);
        }
        60% {
            text-shadow: 0.25em 0 0 white, 
                         0.5em 0 0 rgba(255, 255, 255, 0);
        }
        80%, 100% {
            text-shadow: 0.25em 0 0 white, 
                         0.5em 0 0 white;
        }
    }
    
    @keyframes loadingFade {
        from {
            opacity: 0.3;
        }
        to {
            opacity: 1;
        }
    }
    
    /* Animation delays for staggered effects */
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
    
    /* Animation utilities */
    .animate-once {
        animation-iteration-count: 1;
        animation-fill-mode: forwards;
    }
    
    .animate-infinite {
        animation-iteration-count: infinite;
    }
    
    .animate-pause {
        animation-play-state: paused;
    }
    
    /* Reduced motion preferences */
    @media (prefers-reduced-motion: reduce) {
        .fade-in,
        .slide-in-up,
        .slide-in-down,
        .scale-in,
        .card-flip,
        .card-slide,
        .card-stack,
        .piece-drop,
        .piece-move,
        .piece-capture,
        .tile-appear,
        .tile-merge,
        .tile-slide,
        .success-pulse,
        .win-celebration,
        .gentle-pulse,
        .soft-glow,
        .loading-dots,
        .loading-fade {
            animation: none !important;
        }
        
        .game-transition,
        .game-transition-slow,
        .hover-lift,
        .hover-scale {
            transition: none !important;
        }
        
        .hover-lift:hover {
            transform: none !important;
        }
        
        .hover-scale:hover {
            transform: none !important;
        }
    }
    
    /* High contrast mode adjustments */
    @media (prefers-contrast: high) {
        .soft-glow {
            animation: none;
            box-shadow: 0 0 0 2px currentColor;
        }
        
        .gentle-pulse {
            animation: none;
            opacity: 1;
        }
    }
</style>

<?php

namespace App\Services;

class GameAnimationService
{
    /**
     * Get CSS animation classes for common game effects
     */
    public static function getAnimationClasses(): array
    {
        return [
            'appear' => 'animate-fade-in',
            'bounce' => 'animate-bounce',
            'pulse' => 'animate-pulse', 
            'scale' => 'animate-scale',
            'slide-up' => 'animate-slide-up',
            'slide-down' => 'animate-slide-down',
            'slide-left' => 'animate-slide-left',
            'slide-right' => 'animate-slide-right',
            'glow' => 'animate-glow',
            'shake' => 'animate-shake'
        ];
    }

    /**
     * Generate CSS keyframes for custom animations
     */
    public static function generateKeyframes(): string
    {
        return "
            @keyframes fade-in {
                0% { opacity: 0; transform: scale(0.8); }
                100% { opacity: 1; transform: scale(1); }
            }
            
            @keyframes scale {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            @keyframes slide-up {
                0% { transform: translateY(20px); opacity: 0; }
                100% { transform: translateY(0); opacity: 1; }
            }
            
            @keyframes slide-down {
                0% { transform: translateY(-20px); opacity: 0; }
                100% { transform: translateY(0); opacity: 1; }
            }
            
            @keyframes slide-left {
                0% { transform: translateX(20px); opacity: 0; }
                100% { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slide-right {
                0% { transform: translateX(-20px); opacity: 0; }
                100% { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes glow {
                0%, 100% { box-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }
                50% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.6); }
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            .animate-fade-in { animation: fade-in 0.3s ease-out; }
            .animate-scale { animation: scale 0.3s ease-in-out; }
            .animate-slide-up { animation: slide-up 0.3s ease-out; }
            .animate-slide-down { animation: slide-down 0.3s ease-out; }
            .animate-slide-left { animation: slide-left 0.3s ease-out; }
            .animate-slide-right { animation: slide-right 0.3s ease-out; }
            .animate-glow { animation: glow 1.5s ease-in-out infinite; }
            .animate-shake { animation: shake 0.5s ease-in-out; }
        ";
    }
}

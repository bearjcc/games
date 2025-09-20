<?php

namespace App\Games\Shared;

/**
 * Shared UI Components for Games
 */
class GameUIComponents
{
    /**
     * Get common game styles
     */
    public static function getCommonStyles(): string
    {
        return '
        /* Common Game Styles */
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }

        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            backdrop-filter: blur(10px);
        }

        .dark .game-header {
            background: rgba(0, 0, 0, 0.2);
        }

        .game-status {
            text-align: center;
        }

        .player-indicator {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(71 85 105);
        }

        .dark .player-indicator {
            color: rgb(203 213 225);
        }

        .winner-indicator {
            font-size: 1.25rem;
            font-weight: bold;
            color: rgb(34 197 94);
            text-align: center;
        }

        .game-board-container {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .controls-panel {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .dark .controls-panel {
            background: rgba(0, 0, 0, 0.2);
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.375rem;
            color: rgb(71 85 105);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(10px);
        }

        .dark .action-button {
            color: rgb(203 213 225);
        }

        .action-button:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .action-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .action-button.primary {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
            color: rgb(59, 130, 246);
        }

        .action-button.primary:hover:not(:disabled) {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.4);
        }

        .action-button.success {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.3);
            color: rgb(34, 197, 94);
        }

        .action-button.success:hover:not(:disabled) {
            background: rgba(34, 197, 94, 0.3);
            border-color: rgba(34, 197, 94, 0.4);
        }

        .action-button.danger {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.3);
            color: rgb(239, 68, 68);
        }

        .action-button.danger:hover:not(:disabled) {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.4);
        }

        .instructions {
            margin-top: 2rem;
            text-align: center;
        }

        .instruction-toggle {
            background: rgba(107, 114, 128, 0.2);
            border: 1px solid rgba(107, 114, 128, 0.3);
            color: rgb(107, 114, 128);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .instruction-toggle:hover {
            background: rgba(107, 114, 128, 0.3);
            border-color: rgba(107, 114, 128, 0.4);
        }

        .instruction-content {
            margin-top: 1rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            backdrop-filter: blur(10px);
            text-align: left;
        }

        .dark .instruction-content {
            background: rgba(0, 0, 0, 0.2);
        }

        .instruction-section {
            margin-bottom: 1.5rem;
        }

        .instruction-section h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: rgb(71 85 105);
        }

        .dark .instruction-section h4 {
            color: rgb(203 213 225);
        }

        .instruction-section ul {
            list-style: none;
            padding-left: 0;
        }

        .instruction-section li {
            margin-bottom: 0.25rem;
            padding-left: 1rem;
            position: relative;
        }

        .instruction-section li::before {
            content: "•";
            color: rgb(59, 130, 246);
            position: absolute;
            left: 0;
        }

        /* Smooth animations */
        .game-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .game-container {
                padding: 0.5rem;
            }
            
            .game-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .controls-panel {
                padding: 1rem;
            }
        }
        ';
    }

    /**
     * Get common game animations
     */
    public static function getCommonAnimations(): string
    {
        return '
        /* Common Game Animations */
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .bounce {
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
            }
            50% {
                transform: translateY(0);
                animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
            }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .glow {
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { box-shadow: 0 0 5px rgba(59, 130, 246, 0.5); }
            to { box-shadow: 0 0 20px rgba(59, 130, 246, 0.8); }
        }
        ';
    }

    /**
     * Get loading spinner
     */
    public static function getLoadingSpinner(): string
    {
        return '
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
        
        <style>
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        
        .spinner {
            width: 2rem;
            height: 2rem;
            border: 2px solid rgba(59, 130, 246, 0.2);
            border-top: 2px solid rgb(59, 130, 246);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        ';
    }

    /**
     * Get score display component
     */
    public static function getScoreDisplay(int $score, int $highScore = null): string
    {
        $highScoreHtml = $highScore ? " | High: {$highScore}" : '';
        return "
        <div class='score-display'>
            <span class='score-label'>Score:</span>
            <span class='score-value'>{$score}</span>
            {$highScoreHtml}
        </div>
        ";
    }

    /**
     * Get timer display component
     */
    public static function getTimerDisplay(int $seconds): string
    {
        $minutes = intval($seconds / 60);
        $seconds = $seconds % 60;
        $timeString = sprintf('%02d:%02d', $minutes, $seconds);
        
        return "
        <div class='timer-display'>
            <span class='timer-label'>Time:</span>
            <span class='timer-value'>{$timeString}</span>
        </div>
        ";
    }
}

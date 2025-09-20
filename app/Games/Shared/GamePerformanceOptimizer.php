<?php

namespace App\Games\Shared;

/**
 * Game Performance Optimizer - Shared performance utilities
 */
class GamePerformanceOptimizer
{
    /**
     * Optimize game state for storage/transmission
     */
    public static function optimizeState(array $state): array
    {
        // Remove unnecessary data
        $optimized = $state;
        
        // Remove debug information
        unset($optimized['debug']);
        unset($optimized['lastAction']);
        unset($optimized['handHistory']);
        
        // Compress large arrays
        if (isset($optimized['board']) && is_array($optimized['board'])) {
            $optimized['board'] = self::compressBoard($optimized['board']);
        }
        
        return $optimized;
    }

    /**
     * Compress board data
     */
    protected static function compressBoard(array $board): array
    {
        // Convert to more compact format if needed
        return $board;
    }

    /**
     * Cache game calculations
     */
    public static function cacheCalculation(string $key, callable $calculation, int $ttl = 300): mixed
    {
        static $cache = [];
        
        if (isset($cache[$key]) && time() - $cache[$key]['timestamp'] < $ttl) {
            return $cache[$key]['value'];
        }
        
        $value = $calculation();
        $cache[$key] = [
            'value' => $value,
            'timestamp' => time()
        ];
        
        return $value;
    }

    /**
     * Batch process moves for better performance
     */
    public static function batchProcessMoves(array $state, array $moves): array
    {
        foreach ($moves as $move) {
            $state = self::applyMoveOptimized($state, $move);
        }
        
        return $state;
    }

    /**
     * Optimized move application
     */
    protected static function applyMoveOptimized(array $state, array $move): array
    {
        // Implement optimized move application logic
        return $state;
    }

    /**
     * Lazy load game components
     */
    public static function lazyLoadComponent(string $component, callable $loader): mixed
    {
        static $loaded = [];
        
        if (!isset($loaded[$component])) {
            $loaded[$component] = $loader();
        }
        
        return $loaded[$component];
    }

    /**
     * Debounce function calls
     */
    public static function debounce(callable $callback, int $delay = 300): callable
    {
        static $timers = [];
        
        return function(...$args) use ($callback, $delay) {
            $key = spl_object_hash($callback);
            
            if (isset($timers[$key])) {
                clearTimeout($timers[$key]);
            }
            
            $timers[$key] = setTimeout(function() use ($callback, $args) {
                $callback(...$args);
            }, $delay);
        };
    }

    /**
     * Throttle function calls
     */
    public static function throttle(callable $callback, int $limit = 100): callable
    {
        static $lastCall = [];
        
        return function(...$args) use ($callback, $limit) {
            $key = spl_object_hash($callback);
            $now = microtime(true) * 1000;
            
            if (!isset($lastCall[$key]) || $now - $lastCall[$key] >= $limit) {
                $lastCall[$key] = $now;
                return $callback(...$args);
            }
        };
    }

    /**
     * Memory usage optimization
     */
    public static function optimizeMemory(): void
    {
        // Clear unnecessary variables
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Get performance metrics
     */
    public static function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'loaded_extensions' => get_loaded_extensions()
        ];
    }
}

<?php

namespace App\Services;

/**
 * Asset Manager Service
 * 
 * Centralized management of game assets including pieces, dice, chips, and cards.
 * Provides consistent asset loading, caching, and validation across all games.
 */
class AssetManager
{
    /**
     * Asset type constants
     */
    public const TYPE_PIECE = 'piece';
    public const TYPE_DICE = 'dice';
    public const TYPE_CHIP = 'chip';
    public const TYPE_CARD = 'card';
    public const TYPE_CARD_BACK = 'card_back';

    /**
     * Asset style constants
     */
    public const STYLE_BORDER = 'border';
    public const STYLE_MULTI = 'multi';
    public const STYLE_SINGLE = 'single';
    public const STYLE_SIDE = 'side';
    public const STYLE_SIDE_BORDER = 'sideBorder';

    /**
     * Asset color constants
     */
    public const COLOR_BLACK = 'Black';
    public const COLOR_RED = 'Red';
    public const COLOR_BLUE = 'Blue';
    public const COLOR_GREEN = 'Green';
    public const COLOR_WHITE = 'White';
    public const COLOR_YELLOW = 'Yellow';
    public const COLOR_PURPLE = 'Purple';

    /**
     * Valid piece colors
     */
    private const PIECE_COLORS = [
        self::COLOR_BLACK, self::COLOR_RED, self::COLOR_BLUE, 
        self::COLOR_GREEN, self::COLOR_WHITE, self::COLOR_YELLOW, 
        self::COLOR_PURPLE
    ];

    /**
     * Valid piece styles
     */
    private const PIECE_STYLES = [
        self::STYLE_BORDER, self::STYLE_MULTI, self::STYLE_SINGLE
    ];

    /**
     * Valid dice colors
     */
    private const DICE_COLORS = ['red', 'white'];

    /**
     * Valid dice styles
     */
    private const DICE_STYLES = ['border', 'no-border'];

    /**
     * Valid chip color combinations
     */
    private const CHIP_COLORS = [
        'BlackWhite', 'Blue', 'BlueWhite', 'Green', 'GreenWhite',
        'RedWhite', 'White', 'WhiteBlue'
    ];

    /**
     * Valid chip styles
     */
    private const CHIP_STYLES = ['border', 'side', 'sideBorder', 'standard'];

    /**
     * Valid card back colors
     */
    private const CARD_BACK_COLORS = ['blue', 'green', 'red'];

    /**
     * Valid card back styles
     */
    private const CARD_BACK_STYLES = [1, 2, 3, 4, 5];

    /**
     * Asset cache for performance
     */
    private static array $assetCache = [];

    /**
     * Get piece asset URL
     * 
     * @param string $color Piece color (Black, Red, Blue, etc.)
     * @param string $style Piece style (border, multi, single)
     * @param int $index Piece index (0-18)
     * @return string Asset URL
     * @throws \InvalidArgumentException
     */
    public static function getPieceAsset(string $color, string $style, int $index): string
    {
        self::validatePieceParams($color, $style, $index);
        
        $cacheKey = "piece_{$color}_{$style}_{$index}";
        
        if (isset(self::$assetCache[$cacheKey])) {
            return self::$assetCache[$cacheKey];
        }

        $filename = "piece{$color}_{$style}" . sprintf('%02d', $index) . '.png';
        $url = self::getAssetUrl("Pieces ({$color})/{$filename}");
        
        self::$assetCache[$cacheKey] = $url;
        return $url;
    }

    /**
     * Get dice asset URL
     * 
     * @param string $color Dice color (red, white)
     * @param string $style Dice style (border, no-border)
     * @param int $value Dice value (1-6)
     * @return string Asset URL
     * @throws \InvalidArgumentException
     */
    public static function getDiceAsset(string $color, string $style, int $value): string
    {
        self::validateDiceParams($color, $style, $value);
        
        $cacheKey = "dice_{$color}_{$style}_{$value}";
        
        if (isset(self::$assetCache[$cacheKey])) {
            return self::$assetCache[$cacheKey];
        }

        $styleSuffix = $style === 'border' ? '_border' : '';
        $colorCapitalized = ucfirst($color);
        $filename = "die{$colorCapitalized}{$styleSuffix}{$value}.png";
        $url = self::getAssetUrl("Dice/{$filename}");
        
        self::$assetCache[$cacheKey] = $url;
        return $url;
    }

    /**
     * Get chip asset URL
     * 
     * @param string $color Chip color combination (BlackWhite, Blue, etc.)
     * @param string $style Chip style (border, side, sideBorder, standard)
     * @return string Asset URL
     * @throws \InvalidArgumentException
     */
    public static function getChipAsset(string $color, string $style): string
    {
        self::validateChipParams($color, $style);
        
        $cacheKey = "chip_{$color}_{$style}";
        
        if (isset(self::$assetCache[$cacheKey])) {
            return self::$assetCache[$cacheKey];
        }

        $styleSuffix = $style === 'standard' ? '' : "_{$style}";
        $filename = "chip{$color}{$styleSuffix}.png";
        $url = self::getAssetUrl("Chips/{$filename}");
        
        self::$assetCache[$cacheKey] = $url;
        return $url;
    }

    /**
     * Get card back asset URL
     * 
     * @param string $color Card back color (blue, green, red)
     * @param int $style Card back style (1-5)
     * @return string Asset URL
     * @throws \InvalidArgumentException
     */
    public static function getCardBackAsset(string $color, int $style): string
    {
        self::validateCardBackParams($color, $style);
        
        $cacheKey = "cardback_{$color}_{$style}";
        
        if (isset(self::$assetCache[$cacheKey])) {
            return self::$assetCache[$cacheKey];
        }

        $filename = "cardBack_{$color}{$style}.png";
        $url = self::getAssetUrl("Cards/{$filename}");
        
        self::$assetCache[$cacheKey] = $url;
        return $url;
    }

    /**
     * Get playing card asset URL
     * 
     * @param string $suit Card suit (hearts, diamonds, clubs, spades)
     * @param string $rank Card rank (A, 2-10, J, Q, K)
     * @return string Asset URL
     * @throws \InvalidArgumentException
     */
    public static function getCardAsset(string $suit, string $rank): string
    {
        self::validateCardParams($suit, $rank);
        
        $cacheKey = "card_{$suit}_{$rank}";
        
        if (isset(self::$assetCache[$cacheKey])) {
            return self::$assetCache[$cacheKey];
        }

        $suitName = ucfirst($suit);
        $filename = "card{$suitName}{$rank}.png";
        $url = self::getAssetUrl("Cards/{$filename}");
        
        self::$assetCache[$cacheKey] = $url;
        return $url;
    }

    /**
     * Get asset URL using @images alias for proper Bootstrap bundling
     * 
     * @param string $path Asset path relative to resources/img
     * @return string Asset URL
     */
    private static function getAssetUrl(string $path): string
    {
        return "@images/{$path}";
    }

    /**
     * Validate piece parameters
     */
    private static function validatePieceParams(string $color, string $style, int $index): void
    {
        if (!in_array($color, self::PIECE_COLORS)) {
            throw new \InvalidArgumentException("Invalid piece color: {$color}");
        }
        
        if (!in_array($style, self::PIECE_STYLES)) {
            throw new \InvalidArgumentException("Invalid piece style: {$style}");
        }
        
        if ($index < 0 || $index > 18) {
            throw new \InvalidArgumentException("Invalid piece index: {$index}. Must be 0-18");
        }
    }

    /**
     * Validate dice parameters
     */
    private static function validateDiceParams(string $color, string $style, int $value): void
    {
        if (!in_array($color, self::DICE_COLORS)) {
            throw new \InvalidArgumentException("Invalid dice color: {$color}");
        }
        
        if (!in_array($style, self::DICE_STYLES)) {
            throw new \InvalidArgumentException("Invalid dice style: {$style}");
        }
        
        if ($value < 1 || $value > 6) {
            throw new \InvalidArgumentException("Invalid dice value: {$value}. Must be 1-6");
        }
    }

    /**
     * Validate chip parameters
     */
    private static function validateChipParams(string $color, string $style): void
    {
        if (!in_array($color, self::CHIP_COLORS)) {
            throw new \InvalidArgumentException("Invalid chip color: {$color}");
        }
        
        if (!in_array($style, self::CHIP_STYLES)) {
            throw new \InvalidArgumentException("Invalid chip style: {$style}");
        }
    }

    /**
     * Validate card back parameters
     */
    private static function validateCardBackParams(string $color, int $style): void
    {
        if (!in_array($color, self::CARD_BACK_COLORS)) {
            throw new \InvalidArgumentException("Invalid card back color: {$color}");
        }
        
        if (!in_array($style, self::CARD_BACK_STYLES)) {
            throw new \InvalidArgumentException("Invalid card back style: {$style}. Must be 1-5");
        }
    }

    /**
     * Validate card parameters
     */
    private static function validateCardParams(string $suit, string $rank): void
    {
        $validSuits = ['hearts', 'diamonds', 'clubs', 'spades'];
        if (!in_array($suit, $validSuits)) {
            throw new \InvalidArgumentException("Invalid card suit: {$suit}");
        }
        
        $validRanks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        if (!in_array($rank, $validRanks)) {
            throw new \InvalidArgumentException("Invalid card rank: {$rank}");
        }
    }

    /**
     * Clear asset cache
     */
    public static function clearCache(): void
    {
        self::$assetCache = [];
    }

    /**
     * Get available piece colors
     */
    public static function getPieceColors(): array
    {
        return self::PIECE_COLORS;
    }

    /**
     * Get available piece styles
     */
    public static function getPieceStyles(): array
    {
        return self::PIECE_STYLES;
    }

    /**
     * Get available dice colors
     */
    public static function getDiceColors(): array
    {
        return self::DICE_COLORS;
    }

    /**
     * Get available chip color combinations
     */
    public static function getChipColors(): array
    {
        return self::CHIP_COLORS;
    }

    /**
     * Get available card back colors
     */
    public static function getCardBackColors(): array
    {
        return self::CARD_BACK_COLORS;
    }
}

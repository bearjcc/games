# Game Asset Usage Guide

This guide provides comprehensive instructions for using game assets consistently across all games in the project.

## Overview

The project includes 470 individual game assets across 4 categories:
- **Game Pieces**: 399 assets (7 colors × 3 styles × 19 pieces)
- **Dice**: 24 assets (2 colors × 2 styles × 6 values)
- **Poker Chips**: 32 assets (8 color combinations × 4 styles)
- **Playing Cards**: 15 card backs + individual card faces

## Asset Management System

### AssetManager Service

The `AssetManager` service provides centralized asset management with validation, caching, and consistent URL generation.

```php
use App\Services\AssetManager;

// Get piece asset
$pieceUrl = AssetManager::getPieceAsset('Red', 'border', 5);

// Get dice asset
$diceUrl = AssetManager::getDiceAsset('red', 'border', 3);

// Get chip asset
$chipUrl = AssetManager::getChipAsset('BlueWhite', 'border');

// Get card back asset
$cardBackUrl = AssetManager::getCardBackAsset('blue', 1);

// Get playing card asset
$cardUrl = AssetManager::getCardAsset('hearts', 'A');
```

### Blade Components

Use the provided Blade components for consistent asset rendering:

```blade
{{-- Game Piece --}}
<x-assets.piece color="Red" style="border" :index="5" class="game-piece" />

{{-- Dice --}}
<x-assets.dice color="red" style="border" :value="3" class="dice" />

{{-- Poker Chip --}}
<x-assets.chip color="BlueWhite" style="border" class="chip" />

{{-- Card Back --}}
<x-assets.card-back color="blue" :style="1" class="card-back" />

{{-- Playing Card --}}
<x-assets.playing-card suit="hearts" rank="A" class="card" />
```

## Asset Categories

### Game Pieces

**Colors**: Black, Red, Blue, Green, White, Yellow, Purple
**Styles**: border, multi, single
**Indices**: 0-18 (each represents a different piece type)

```php
// Examples
AssetManager::getPieceAsset('Red', 'single', 0);    // Red pawn
AssetManager::getPieceAsset('Black', 'border', 8);   // Black rook
AssetManager::getPieceAsset('Blue', 'multi', 12);   // Blue car
```

### Dice

**Colors**: red, white
**Styles**: border, no-border
**Values**: 1-6

```php
// Examples
AssetManager::getDiceAsset('red', 'border', 1);      // Red die with border showing 1
AssetManager::getDiceAsset('white', 'no-border', 6); // White die without border showing 6
```

### Poker Chips

**Color Combinations**: BlackWhite, Blue, BlueWhite, Green, GreenWhite, RedWhite, White, WhiteBlue
**Styles**: standard, border, side, sideBorder

```php
// Examples
AssetManager::getChipAsset('RedWhite', 'border');    // Red and white chip with border
AssetManager::getChipAsset('Blue', 'side');          // Solid blue chip, side view
```

### Playing Cards

**Card Backs**:
- Colors: blue, green, red
- Styles: 1-5 (different design patterns)

**Individual Cards**:
- Suits: hearts, diamonds, clubs, spades
- Ranks: A, 2-10, J, Q, K

```php
// Examples
AssetManager::getCardBackAsset('blue', 1);           // Blue card back, style 1
AssetManager::getCardAsset('hearts', 'A');          // Ace of Hearts
AssetManager::getCardAsset('spades', 'K');          // King of Spades
```

## Best Practices

### 1. Use AssetManager Service

Always use the `AssetManager` service instead of hardcoded paths:

```php
// ✅ Good
$pieceUrl = AssetManager::getPieceAsset('Red', 'border', 5);

// ❌ Bad
$pieceUrl = '/images/Pieces%20(Red)/pieceRed_border05.png';
```

### 2. Use Blade Components

Prefer Blade components for consistent rendering:

```blade
{{-- ✅ Good --}}
<x-assets.piece color="Red" style="border" :index="5" />

{{-- ❌ Bad --}}
<img src="/images/Pieces%20(Red)/pieceRed_border05.png" alt="Red piece" />
```

### 3. Validate Parameters

The AssetManager automatically validates all parameters and throws exceptions for invalid values:

```php
try {
    $url = AssetManager::getPieceAsset('Invalid', 'border', 5);
} catch (InvalidArgumentException $e) {
    // Handle invalid parameters
    logger()->error('Invalid asset parameters: ' . $e->getMessage());
}
```

### 4. Cache Assets

Assets are automatically cached for performance. Clear cache when needed:

```php
AssetManager::clearCache();
```

### 5. Consistent Styling

Use consistent CSS classes and styling:

```blade
<x-assets.piece 
    color="Red" 
    style="border" 
    :index="5" 
    class="game-piece piece-red piece-border"
    width="32px"
    height="32px"
/>
```

## Game-Specific Usage

### Chess Games

```php
// Pawns
AssetManager::getPieceAsset('White', 'single', 0);  // White pawn
AssetManager::getPieceAsset('Black', 'single', 0);  // Black pawn

// Rooks
AssetManager::getPieceAsset('White', 'border', 8);  // White rook
AssetManager::getPieceAsset('Black', 'border', 8);  // Black rook
```

### Checkers Games

```php
// Regular pieces
AssetManager::getPieceAsset('Red', 'single', 0);    // Red piece
AssetManager::getPieceAsset('Black', 'single', 0);  // Black piece

// Kings
AssetManager::getPieceAsset('Red', 'multi', 0);     // Red king
AssetManager::getPieceAsset('Black', 'multi', 0);   // Black king
```

### Card Games

```php
// Card backs
AssetManager::getCardBackAsset('blue', 1);          // Blue card back

// Individual cards
AssetManager::getCardAsset('hearts', 'A');          // Ace of Hearts
AssetManager::getCardAsset('spades', 'K');          // King of Spades
```

### Dice Games

```php
// Standard dice
AssetManager::getDiceAsset('red', 'border', 1);      // Red die showing 1
AssetManager::getDiceAsset('white', 'border', 6);    // White die showing 6
```

### Casino Games

```php
// Poker chips
AssetManager::getChipAsset('RedWhite', 'border');   // Red/white chip
AssetManager::getChipAsset('Blue', 'side');         // Blue chip, side view

// Cards
AssetManager::getCardAsset('hearts', 'A');          // Ace of Hearts
AssetManager::getCardBackAsset('red', 2);           // Red card back
```

## Migration Guide

### Updating Existing Games

1. **Replace hardcoded paths**:
   ```php
   // Old
   $pieceUrl = '/images/Pieces%20(Red)/pieceRed_single00.png';
   
   // New
   $pieceUrl = AssetManager::getPieceAsset('Red', 'single', 0);
   ```

2. **Update Blade templates**:
   ```blade
   {{-- Old --}}
   <img src="/images/Pieces%20(Red)/pieceRed_single00.png" alt="Red piece" />
   
   {{-- New --}}
   <x-assets.piece color="Red" style="single" :index="0" />
   ```

3. **Update Livewire components**:
   ```php
   // Old
   public function getPieceAsset($piece) {
       return match($piece) {
           'red' => '/images/Pieces%20(Red)/pieceRed_single00.png',
           'black' => '/images/Pieces%20(Black)/pieceBlack_single00.png',
       };
   }
   
   // New
   public function getPieceAsset($piece) {
       return match($piece) {
           'red' => AssetManager::getPieceAsset('Red', 'single', 0),
           'black' => AssetManager::getPieceAsset('Black', 'single', 0),
       };
   }
   ```

## Error Handling

The AssetManager throws `InvalidArgumentException` for invalid parameters:

```php
try {
    $url = AssetManager::getPieceAsset('InvalidColor', 'border', 5);
} catch (InvalidArgumentException $e) {
    // Log error and provide fallback
    logger()->error('Asset error: ' . $e->getMessage());
    $url = AssetManager::getPieceAsset('Black', 'single', 0); // Fallback
}
```

## Performance Considerations

1. **Asset Caching**: Assets are automatically cached in memory
2. **Lazy Loading**: Use `loading="lazy"` for images below the fold
3. **Optimized Paths**: Use `@images/` alias for proper Bootstrap bundling
4. **Validation**: Parameter validation prevents runtime errors

## Testing

Test asset loading in your game tests:

```php
public function test_piece_asset_loading()
{
    $url = AssetManager::getPieceAsset('Red', 'border', 5);
    $this->assertStringContains('@images/', $url);
    $this->assertStringContains('pieceRed_border05.png', $url);
}

public function test_invalid_piece_parameters()
{
    $this->expectException(InvalidArgumentException::class);
    AssetManager::getPieceAsset('Invalid', 'border', 5);
}
```

## Troubleshooting

### Common Issues

1. **Asset not found**: Check that the asset exists in `resources/img/`
2. **Invalid parameters**: Use the AssetManager validation methods
3. **Cache issues**: Clear cache with `AssetManager::clearCache()`
4. **Path issues**: Ensure you're using `@images/` alias

### Debug Mode

Enable debug mode to see asset loading details:

```php
// In your game component
public function debugAssets()
{
    $assets = [
        'piece' => AssetManager::getPieceAsset('Red', 'border', 5),
        'dice' => AssetManager::getDiceAsset('red', 'border', 3),
        'chip' => AssetManager::getChipAsset('BlueWhite', 'border'),
    ];
    
    logger()->debug('Asset URLs', $assets);
}
```

This guide ensures consistent, maintainable, and performant asset usage across all games in the project.

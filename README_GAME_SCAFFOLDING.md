# 🎮 Game Scaffolding System - Quick Reference

> **Get from idea to playable game in minutes with consistent liminal design**

## ⚡ Quick Start

### Create a New Game

```bash
# Basic game
php artisan make:game "My Awesome Game"

# Board game with AI
php artisan make:game "Strategic Chess" --type=board --ai

# Card game for 4 players  
php artisan make:game "Card Battle" --type=card --players=4

# Tile-based puzzle
php artisan make:game "Number Slide" --type=tile
```

### Game Types Available

- **`board`** - Grid-based games (Chess, Checkers, Go)
- **`card`** - Card games (Poker, Solitaire, War)
- **`tile`** - Tile/number games (2048, Sliding puzzles)
- **`puzzle`** - General puzzle games (Custom layouts)

---

## 📁 What Gets Created

```
app/Games/YourGame/
├── YourGameGame.php     # Game interface implementation
├── YourGameEngine.php   # Game logic and rules
└── YourGameAI.php       # AI opponent (if --ai flag used)

resources/views/livewire/games/
└── your-game.blade.php  # Liminal UI with all components
```

---

## 🚀 Instant Features

Every generated game automatically includes:

### 🎨 **Liminal Design System**
- ✅ Consistent UI/UX across all games
- ✅ Dark/light mode support
- ✅ Responsive mobile-friendly layout
- ✅ Accessibility features (keyboard nav, screen readers)

### 🎮 **Game Functionality**
- ✅ Game state management
- ✅ Move validation and history
- ✅ Undo system (10 moves)
- ✅ Hint system with AI suggestions
- ✅ Score tracking and statistics
- ✅ Game mode selection (single/AI/multiplayer)

### 🎯 **UI Components**
- ✅ Game board with proper layouts
- ✅ Game pieces, cards, or tiles
- ✅ Status indicators and game info
- ✅ Settings and controls
- ✅ Instructions panel

---

## 🛠️ Customization Points

### 1. Game Logic (`YourGameEngine.php`)

```php
// Customize these methods:
public static function newGame(): array           // Initial game state
public static function isValidMove(): bool        // Move validation  
public static function applyMove(): array         // Apply player moves
public static function getValidMoves(): array     // Get available moves
public static function checkGameEnd(): array      // Win/loss conditions
```

### 2. Game Board (`your-game.blade.php`)

```blade
<!-- Replace the board section with your layout -->
<div class="your-game-board">
    <!-- Your custom game board here -->
</div>
```

### 3. AI Difficulty (`YourGameAI.php`)

```php
// Three difficulty levels automatically implemented:
- easy: random moves
- medium: basic strategy  
- hard: optimal strategy (implement minimax/etc.)
```

---

## 📖 Helper Utilities

### Base Game Engine (Included)

```php
use App\Games\Shared\BaseGameEngine;

// Common utilities available:
self::initializeBoard(8, 8)              // Create grid boards
self::getAdjacentPositions($row, $col)   // Get neighbors
self::checkWinCondition($board, $player) // Standard win detection
self::calculateStandardScore($state)     // Time/move-based scoring
```

### UI Components

```blade
<!-- Game pieces for any board game -->
<x-game.piece type="circle" :player="'white'" :selected="true" />
<x-game.piece type="image" :imageUrl="'/path/to/piece.png'" />

<!-- Playing cards -->  
<x-game.card :card="$cardData" :faceUp="true" :draggable="true" />

<!-- Number tiles -->
<x-game.tile :value="2048" :position="5" :isNew="true" />

<!-- Utility CSS classes -->
<div class="game-board-grid grid-8x8">8x8 grid layout</div>
<div class="board-square position-selected">Selected square</div>
<div class="status-indicator winner">Winner!</div>
```

---

## 🎯 Complete Example Workflow

### 1. Generate Game
```bash
php artisan make:game "Color Match" --type=board --ai
```

### 2. Register Game
```php
// app/Providers/AppServiceProvider.php
$gameRegistry->register(new \App\Games\ColorMatch\ColorMatchGame());
```

### 3. Implement Logic
```php
// app/Games/ColorMatch/ColorMatchEngine.php
public static function newGame(): array {
    return [
        'board' => self::initializeBoard(4, 4),
        'colors' => ['red', 'blue', 'green', 'yellow'],
        'score' => 0,
        // ... your game state
    ];
}
```

### 4. Customize UI
```blade
<!-- resources/views/livewire/games/color-match.blade.php -->
<div class="game-board-grid grid-4x4">
    @foreach($state['board'] as $row => $cols)
        @foreach($cols as $col => $color)
            <div class="board-square" wire:click="selectColor({{$row}}, {{$col}})">
                <x-game.piece type="circle" :player="$color" />
            </div>
        @endforeach
    @endforeach
</div>
```

### 5. Test & Play!
Visit `/games/color-match` and enjoy your liminal-designed game!

---

## 🎨 Design Patterns

### Board Games
- Use `grid-8x8`, `grid-4x4` classes for automatic layouts
- `x-game.piece` handles all piece types and interactions
- `position-selected`, `position-valid-target` for move indicators

### Card Games  
- Use `game-board-card-table` for spacious card layouts
- `x-game.card` handles face-up/down, dragging, animations
- `card-pile`, `card-spread` classes for organized layouts

### Tile Games
- Use `tile-container` with CSS grid for tile arrangement  
- `x-game.tile` handles values, animations, and merging
- Perfect for number-based puzzles and sliding games

---

## 🚀 Advanced Features

### Custom Board Shapes
```css
/* Add to your game's <style> section */
.triangular-board {
    /* Custom positioning for triangular layouts */
}
```

### Special Animations
```css
@keyframes custom-move {
    /* Your custom game animations */
}
```

### AI Integration
```php
// In your Livewire component
public function makeAIMove() {
    $move = YourGameAI::getBestMove($this->state, $this->difficulty);
    if ($move) {
        $this->makeMove($move);
    }
}
```

---

## 📚 Full Documentation

- **Complete Guide**: `GAME_DEVELOPMENT_GUIDE.md`  
- **Component Reference**: All components in `resources/views/components/game/`
- **Examples**: Check existing games for patterns and inspiration

---

**🎉 Result: Professional, accessible, beautiful games with minimal effort!**

The scaffolding system handles all the boilerplate, UI consistency, and common patterns - you just focus on the fun part: **your game logic!**

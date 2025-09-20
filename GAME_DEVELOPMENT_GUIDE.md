# 🎮 Game Development Guide - Liminal Design System

> **Complete guide for creating new games with consistent, beautiful UI**

## 🚀 Quick Start

1. **Copy the template**: `resources/views/games/_template.blade.php`
2. **Replace placeholders**: `{{GAME_NAME}}`, `{{GAME_SLUG}}`, `{{GAME_DISPLAY_NAME}}`
3. **Customize the board section** with your game logic
4. **Register your game** in `AppServiceProvider`
5. **Create engine and game classes** following existing patterns

---

## 🎨 Component System Overview

### Core Components (Always Include)

```blade
<x-game.styles />      <!-- Base liminal styling -->
<x-game.animations />  <!-- Subtle animation system -->
<x-game.layout title="Game Name">  <!-- Consistent page structure -->
```

### Game Elements

```blade
<x-game.piece />       <!-- Universal pieces (checkers, chess, pegs, etc.) -->
<x-game.card />        <!-- Playing cards (poker, solitaire, etc.) -->
<x-game.tile />        <!-- Number tiles (2048, etc.) -->
<x-game.utilities />   <!-- Helper CSS classes -->
```

---

## 🏗️ Standard Game Structure

### 1. Livewire Component Properties

```php
public array $state;                // Main game state
public string $gameMode = 'single_player';  // vs_ai, pass_and_play
public string $difficulty = 'medium';       // easy, medium, hard  
public bool $showHints = false;             // Hint system toggle
public array $undoStack = [];               // Undo functionality
```

### 2. Required Methods

```php
public function mount() { $this->resetGame(); }
public function resetGame() { /* Initialize game state */ }
public function makeMove($moveData) { /* Handle player moves */ }
public function undo() { /* Undo last move */ }
public function getStats() { /* Return game statistics */ }
```

### 3. Standard Blade Structure

```blade
<x-game.layout title="Your Game">
    <!-- Game Header (status, turn indicator) -->
    <!-- Game Settings (mode, difficulty) -->  
    <!-- Game Board (customize this section) -->
    <!-- Game Info Panel (stats) -->
    <!-- Game Controls (new game, undo, hints) -->
    <!-- Optional: Hints display -->
    <!-- Optional: Instructions -->
</x-game.layout>
```

---

## 🎯 Component Usage Examples

### Game Pieces

```blade
<!-- Checkers piece -->
<x-game.piece
    type="image"
    :piece="$piece"
    :player="'red'"
    :selected="$isSelected"
    :imageUrl="'/images/red-piece.png'"
    size="large" />

<!-- Chess piece -->
<x-game.piece
    type="image"
    :piece="$piece"
    :player="'white'"
    :variant="'king'"
    :draggable="true"
    size="default" />

<!-- Simple circular piece -->
<x-game.piece
    type="circle"
    :player="'black'"
    :highlighted="$isHighlighted"
    size="small" />

<!-- Peg for peg solitaire -->
<x-game.piece
    type="peg"
    :selected="$isSelected"
    variant="hole" />
```

### Playing Cards

```blade
<!-- Face-up card -->
<x-game.card
    :card="$cardData"
    :faceUp="true"
    :draggable="true"
    :animate="true" />

<!-- Face-down card -->
<x-game.card
    :faceUp="false"
    :clickable="true" />

<!-- Empty pile -->
<x-game.card
    :valid="true"
    :suit="'hearts'" />
```

### Number Tiles

```blade
<!-- 2048 tile -->
<x-game.tile
    :value="2048"
    :position="5"
    :isNew="true"
    size="default" />

<!-- Empty tile slot -->
<x-game.tile
    :value="0"
    :position="3" />
```

---

## 🎨 Styling Patterns

### Board Layouts

```blade
<!-- Grid-based board (chess, checkers) -->
<div class="game-board-grid grid-8x8">
    <!-- 64 squares automatically laid out -->
</div>

<!-- Flexible layout (card games) -->
<div class="game-board-flex">
    <!-- Vertical flex layout with gaps -->
</div>

<!-- Card table (war, poker) -->
<div class="game-board-card-table">
    <!-- Spacious layout for card positioning -->
</div>
```

### Utility Classes

```blade
<!-- Board squares -->
<div class="board-square position-selected">Selected</div>
<div class="board-square position-valid-target">Valid move</div>
<div class="board-square position-highlighted">Highlighted</div>

<!-- Status indicators -->
<div class="status-indicator active-player">Your turn</div>
<div class="status-indicator winner">Winner!</div>
<div class="status-indicator warning">Check!</div>

<!-- Transitions -->
<div class="game-transition">Smooth animation</div>
<div class="game-transition-slow">Slower animation</div>
```

---

## ⚡ Game Types & Patterns

### Board Games (Chess, Checkers, etc.)

```blade
<div class="game-board-grid grid-8x8">
    @for($row = 0; $row < 8; $row++)
        @for($col = 0; $col < 8; $col++)
            <div class="board-square {{ $isDark ? 'dark-square' : 'light-square' }}"
                 wire:click="selectSquare({{ $row }}, {{ $col }})">
                
                @if($piece = $board[$row][$col])
                    <x-game.piece
                        type="image"
                        :piece="$piece"
                        :player="$piece['player']"
                        :imageUrl="$pieceImage" />
                @endif
            </div>
        @endfor
    @endfor
</div>
```

### Card Games (Solitaire, War, etc.)

```blade
<div class="game-board-card-table">
    <div class="card-spread">
        @foreach($hand as $card)
            <x-game.card
                :card="$card"
                :faceUp="true"
                :draggable="true" />
        @endforeach
    </div>
    
    <div class="card-pile valid-drop">
        <!-- Drop zone -->
    </div>
</div>
```

### Tile Games (2048, etc.)

```blade
<div class="tile-container" style="grid-template-columns: repeat(4, 1fr);">
    @foreach($board as $i => $value)
        <div class="tile-slot">
            @if($value > 0)
                <x-game.tile
                    :value="$value"
                    :position="$i"
                    :isNew="in_array($i, $newTiles)" />
            @endif
        </div>
    @endforeach
</div>
```

### Strategy Games (Morris, etc.)

```blade
<div class="game-board-flex">
    <svg viewBox="0 0 300 300" class="board-grid">
        <!-- SVG board lines -->
    </svg>
    
    <div class="pieces-container">
        @foreach($positions as $pos => $piece)
            <x-game.piece
                type="circle"
                :piece="$piece"
                :position="$pos"
                :selected="$selectedPos === $pos" />
        @endforeach
    </div>
</div>
```

---

## 🎯 Color System

### Semantic Colors (RGB format for consistency)

```css
/* Primary colors */
rgb(59 130 246)   /* Blue - primary actions */
rgb(34 197 94)    /* Green - success, valid moves */
rgb(239 68 68)    /* Red - danger, captures */
rgb(168 85 247)   /* Purple - special moves */

/* Neutral colors */
rgb(248 250 252)  /* Light background */
rgb(30 41 59)     /* Dark background */
rgb(100 116 139)  /* Medium gray */
rgb(203 213 225)  /* Light gray */
```

### Usage Examples

```css
.winner-indicator { color: rgb(34 197 94); }
.danger-indicator { color: rgb(239 68 68); }
.primary-button { background: rgb(59 130 246); }
```

---

## 🎮 Game State Management

### Standard State Structure

```php
[
    'gameOver' => false,
    'winner' => null,           // 'player1', 'player2', or null
    'currentPlayer' => 'white', // Current player's turn
    'board' => [],              // Game board state
    'moves' => 0,               // Move counter
    'score' => 0,               // Current score
    // Game-specific properties...
]
```

### Move Validation Pattern

```php
public function makeMove($moveData)
{
    if ($this->state['gameOver']) return;
    
    $game = new YourGame();
    $move = ['type' => 'move', 'data' => $moveData];
    
    if ($game->validateMove($this->state, $move)) {
        $this->saveStateForUndo();
        $this->state = $game->applyMove($this->state, $move);
        $this->updateValidMoves();
    }
}
```

---

## 📱 Responsive Design

### Built-in Responsive Classes

```blade
<!-- Responsive board sizing -->
<div class="board-responsive">Standard size</div>
<div class="board-responsive large">Larger games</div>
<div class="board-responsive small">Compact games</div>
```

### Mobile Considerations

- All boards automatically scale down on mobile
- Touch-friendly minimum sizes
- Swipe gestures for applicable games
- Accessible tap targets

---

## ♿ Accessibility Features

### Automatic Features

- Keyboard navigation support
- Screen reader compatibility
- High contrast mode support
- Reduced motion preferences
- Focus indicators

### Implementation

```blade
<!-- Always include accessibility info -->
<div class="sr-only">
    @if($gameOver)
        Game over. Winner: {{ $winner }}
    @else
        {{ $currentPlayer }}'s turn
    @endif
</div>

<!-- Proper ARIA labels -->
<div role="button" 
     tabindex="0"
     aria-label="Chess piece at {{ $position }}">
    <x-game.piece ... />
</div>
```

---

## 🔧 Advanced Customization

### Custom Animations

```css
/* Add to your game's <style> section */
@keyframes custom-move {
    from { transform: translateX(0); }
    to { transform: translateX(100px); }
}

.custom-animation {
    animation: custom-move 0.3s ease-in-out;
}
```

### Custom Board Shapes

```css
/* Triangular board (peg solitaire) */
.triangular-board {
    position: relative;
    width: 20rem;
    height: 18rem;
}

.peg-position {
    position: absolute;
    /* Calculate positions based on row/column */
}
```

---

## 🚀 Performance Tips

1. **Use CSS Grid/Flexbox** for layouts instead of absolute positioning
2. **Minimize DOM updates** by batching state changes
3. **Use CSS transitions** instead of JavaScript animations
4. **Optimize images** and use proper loading strategies
5. **Limit undo stack size** to prevent memory issues

---

## 📚 Examples & References

- **Simple Grid**: Tic Tac Toe (`tic-tac-toe.blade.php`)
- **Complex Board**: Chess (`chess.blade.php`)
- **Card Game**: Solitaire (`solitaire.blade.php`)
- **Tile Game**: 2048 (`2048.blade.php`)
- **Strategy Game**: Nine Men's Morris (`nine-mens-morris.blade.php`)

---

## 🎯 Checklist for New Games

- [ ] Copy and customize template
- [ ] Include all core components
- [ ] Implement standard methods
- [ ] Use semantic color system
- [ ] Add proper accessibility
- [ ] Test responsive design
- [ ] Include hint system
- [ ] Add game statistics
- [ ] Test undo functionality
- [ ] Register in AppServiceProvider

---

**🎉 Result: Beautiful, consistent, accessible games that feel like part of a unified gaming platform!**

# Games Architecture Documentation

## Overview
This Laravel application implements a scalable game platform using Livewire Volt for reactive UI and a clean contract-based architecture.

## Core Architecture

### GameInterface Contract
All games implement `App\Games\Contracts\GameInterface`:
- `id()`: Unique identifier
- `slug()`: URL-friendly identifier  
- `name()`: Human-readable name
- `description()`: Brief description
- `newGameState()`: Initial state array
- `isOver(array $state)`: Game completion check
- `applyMove(array $state, array $move)`: State transitions

### Game Registry
`App\Games\GameRegistry` manages all games:
- Auto-discovery and registration
- Metadata listing for UI
- Game lookup by slug

### Score Tracking
- `UserGameScore` model: Links users to best scores per game
- `UserBestScoreService`: Handles score retrieval/updates
- Automatic best score tracking on game completion

### Frontend Patterns
- **Livewire Volt**: Reactive components in Blade files
- **Route Structure**: `/games/{slug}` + shortcut routes
- **State Management**: Game state in Livewire component properties
- **Real-time Updates**: Automatic re-rendering on state changes

## Testing Strategy

### Unit Tests
- Game logic in `Tests\Unit\Games\{GameName}\`
- Service classes in `Tests\Unit\Services\`
- Focus on pure game logic, edge cases

### Feature Tests  
- Full game workflows in `Tests\Feature\Games\`
- UI interactions, score tracking
- Authentication integration

### Testing Utilities
- `GameTestCase`: Base class for game tests
- Factory methods for common game states
- Assertion helpers for game-specific outcomes

## Asset Management
- SVG game pieces in `resources/img/`
- Vite bundling with '@images/' alias
- No copying to public folder (git-friendly)

## Performance Considerations
- Stateless game engines (pure functions)
- Minimal database writes (best scores only)
- Efficient state serialization
- Component-level caching where appropriate

## Development Patterns

### Adding New Games
1. Create game directory: `app/Games/{GameName}/`
2. Implement `GameInterface` in `{GameName}Game.php`
3. Create engine class for game logic
4. Add Livewire component: `resources/views/livewire/games/{slug}.blade.php`
5. Register in `AppServiceProvider`
6. Add tests in both Unit and Feature directories
7. Optional: Add shortcut route

### Naming Conventions
- **Classes**: PascalCase (`TicTacToeGame`, `ChessEngine`)
- **Files**: Match class names
- **Routes**: kebab-case (`tic-tac-toe`, `chess`)
- **Game States**: camelCase keys
- **Database**: snake_case

### Code Organization
- Keep game logic in separate Engine classes
- Livewire components handle UI state only
- Extract reusable UI patterns into Blade components
- Shared utilities in `app/Games/Shared/`

## Scalability Notes
- Games are stateless and horizontally scalable
- Database only stores user scores (minimal writes)
- Frontend state management handles concurrent users
- Easy to add caching layers as needed

## Theme Guidelines
**Liminal Design**: Clean, focused, engaging without being garish
- Neutral color palette with subtle accents
- Clear typography (Instrument Sans)
- Minimal but purposeful animations
- Consistent spacing and layout patterns
- Dark/light mode support built-in

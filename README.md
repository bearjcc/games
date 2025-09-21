# 🎮 Laravel Games Collection

A comprehensive collection of classic games built with Laravel, Livewire, and a beautiful liminal design system.

## ✨ Features

- **25+ Classic Games**: Chess, Checkers, Poker, Solitaire, 2048, Tetris, and more
- **Liminal Design System**: Clean, focused, and engaging UI/UX
- **Livewire Integration**: Reactive components with real-time updates
- **Asset Management**: 470+ game assets with centralized management
- **AI Opponents**: Smart AI for strategy games with multiple difficulty levels
- **Score Tracking**: Persistent best scores and game statistics
- **Responsive Design**: Works perfectly on desktop and mobile
- **Accessibility**: Full keyboard navigation and screen reader support

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- Composer
- Node.js & npm
- Laravel Herd (recommended)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd games
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   ```

5. **Build assets**
   ```bash
   npm run dev
   ```

6. **Start development server**
   ```bash
   php artisan serve
   # Or use Laravel Herd: http://tavernsandtreasures.test/
   ```

## 🎯 Available Games

### Board Games
- **Chess** - Classic strategy with AI opponent
- **Checkers** - Traditional draughts game
- **Nine Men's Morris** - Ancient strategy game
- **Tic Tac Toe** - Simple 3x3 grid game

### Card Games
- **Poker** - Texas Hold'em with AI
- **Blackjack** - Casino classic with betting
- **Solitaire** - Klondike solitaire
- **Spider Solitaire** - Advanced solitaire variant
- **War** - Simple card comparison game
- **Go Fish** - Multiplayer card game
- **Crazy Eights** - Uno-style game
- **Phase 10** - Progressive rummy game

### Puzzle Games
- **2048** - Number sliding puzzle
- **Sudoku** - Logic number puzzle
- **Minesweeper** - Classic bomb-finding game
- **Tetris** - Block-stacking puzzle
- **Peg Solitaire** - Jumping peg puzzle
- **Mastermind** - Code-breaking game
- **Slitherlink** - Number-based logic puzzle

### Dice & Casino Games
- **Yahtzee** - Five-dice scoring game
- **Farkle** - Risk and reward dice game
- **Snake** - Classic arcade game
- **Memory** - Card matching game
- **Hiking** - Adventure-themed board game

### Word Games
- **Word Detective** - Mystery-solving word game

## 🏗️ Architecture

### Game Development Pattern
All games follow a consistent architecture:

```
app/Games/{GameName}/
├── {GameName}Game.php      # GameInterface implementation
├── {GameName}Engine.php    # Pure game logic
└── AI/{GameName}AI.php     # AI opponent (optional)

resources/views/livewire/games/
└── {slug}.blade.php        # Reactive UI component
```

### Key Components
- **GameInterface**: Standard contract for all games
- **AssetManager**: Centralized asset loading and validation
- **UserBestScoreService**: Score tracking and persistence
- **HintEngine**: AI-powered hints and suggestions

## 🎨 Design System

### Liminal Design Principles
- **Clean & Focused**: Minimal distractions, maximum engagement
- **Consistent**: Unified experience across all games
- **Accessible**: Full keyboard navigation and screen reader support
- **Responsive**: Perfect on all device sizes

### Asset System
- **470+ Game Assets**: Pieces, dice, chips, cards
- **Consistent Loading**: All assets use `@images/` alias
- **Automatic Caching**: Performance-optimized asset delivery
- **Validation**: Runtime parameter validation prevents errors

## 🛠️ Development

### Creating New Games

1. **Use the scaffolding system**:
   ```bash
   php artisan make:game "My Game" --type=board --ai
   ```

2. **Follow the development guide**:
   - Read `GAME_DEVELOPMENT_GUIDE.md` for detailed instructions
   - Use the liminal design components
   - Implement proper game logic separation

3. **Test thoroughly**:
   ```bash
   php artisan test
   ```

### Documentation
- **Architecture**: `ARCHITECTURE.md`
- **Development Guide**: `GAME_DEVELOPMENT_GUIDE.md`
- **Asset Usage**: `ASSET_USAGE_GUIDE.md`
- **Game Pieces**: `GAME_PIECES_CATALOG.md`
- **Scaffolding**: `README_GAME_SCAFFOLDING.md`

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run tests for specific game
php artisan test --filter=Chess
```

## 📊 Performance

- **Stateless Games**: Horizontal scalability
- **Minimal Database**: Only stores user scores
- **Asset Optimization**: Automatic caching and bundling
- **Efficient State Management**: Livewire handles reactivity

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-game`
3. Follow the coding standards and documentation guidelines
4. Add tests for new functionality
5. Submit a pull request

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- Laravel framework and Livewire
- Game asset creators
- Open source community contributions

---

**Built with ❤️ using Laravel, Livewire, and the liminal design system**

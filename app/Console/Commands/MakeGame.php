<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeGame extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'make:game {name : The name of the game} 
                                     {--type=board : Type of game (board, card, tile, puzzle)}
                                     {--players=2 : Number of players}
                                     {--ai : Include AI opponent}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new game with liminal design scaffolding';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        $players = $this->option('players');
        $includeAI = $this->option('ai');

        // Normalize names
        $className = Str::studly($name);
        $slug = Str::slug($name);
        $displayName = Str::title($name);

        $this->info("Creating game: {$displayName}");
        $this->info("Type: {$type}, Players: {$players}, AI: " . ($includeAI ? 'Yes' : 'No'));

        // Create directory structure
        $gameDir = app_path("Games/{$className}");
        if (!is_dir($gameDir)) {
            mkdir($gameDir, 0755, true);
            $this->info("Created directory: {$gameDir}");
        }

        // Create Game class
        $this->createGameClass($gameDir, $className, $slug, $displayName, $players);
        
        // Create Engine class
        $this->createEngineClass($gameDir, $className, $type);
        
        // Create AI class if requested
        if ($includeAI) {
            $this->createAIClass($gameDir, $className);
        }
        
        // Create Blade template
        $this->createBladeTemplate($slug, $className, $displayName, $type);
        
        // Show next steps
        $this->showNextSteps($className, $slug, $displayName);

        return Command::SUCCESS;
    }

    private function createGameClass($dir, $className, $slug, $displayName, $players)
    {
        $template = <<<PHP
<?php

namespace App\Games\\{$className};

use App\Games\Contracts\GameInterface;

class {$className}Game implements GameInterface
{
    public function id(): string
    {
        return '{$slug}';
    }

    public function name(): string
    {
        return '{$displayName}';
    }

    public function slug(): string
    {
        return '{$slug}';
    }

    public function description(): string
    {
        return 'A {$displayName} game with liminal design.';
    }

    public function rules(): array
    {
        return [
            'Basic Rules' => [
                'Add your game rules here',
                'Explain how to play',
                'Include scoring information',
            ],
        ];
    }

    public function minPlayers(): int
    {
        return 1;
    }

    public function maxPlayers(): int
    {
        return {$players};
    }

    public function estimatedDuration(): string
    {
        return '5-15 minutes';
    }

    public function difficulty(): string
    {
        return 'Medium';
    }

    public function tags(): array
    {
        return ['strategy', 'logic'];
    }

    public function initialState(): array
    {
        return {$className}Engine::newGame();
    }

    public function newGameState(): array
    {
        return \$this->initialState();
    }

    public function isOver(array \$state): bool
    {
        return \$state['gameOver'] ?? false;
    }

    public function applyMove(array \$state, array \$move): array
    {
        return {$className}Engine::applyMove(\$state, \$move);
    }

    public function validateMove(array \$state, array \$move): bool
    {
        return {$className}Engine::isValidMove(\$state, \$move);
    }

    public function getScore(array \$state): int
    {
        return {$className}Engine::calculateScore(\$state);
    }
}
PHP;

        file_put_contents("{$dir}/{$className}Game.php", $template);
        $this->info("Created: {$className}Game.php");
    }

    private function createEngineClass($dir, $className, $type)
    {
        $boardInitialization = match($type) {
            'board' => 'return self::initializeBoard(8, 8);',
            'card' => 'return self::initializeDeck();',
            'tile' => 'return array_fill(0, 16, 0);',
            default => 'return [];',
        };

        $template = <<<PHP
<?php

namespace App\Games\\{$className};

use App\Games\Shared\BaseGameEngine;

class {$className}Engine
{
    use BaseGameEngine;

    /**
     * Create a new game state
     */
    public static function newGame(): array
    {
        \$state = self::initializeBaseState();
        \$state['board'] = self::initializeGameBoard();
        \$state['currentPlayer'] = 'player1';
        
        return \$state;
    }

    /**
     * Initialize the game board
     */
    private static function initializeGameBoard(): array
    {
        {$boardInitialization}
    }

    /**
     * Validate if a move is legal
     */
    public static function isValidMove(array \$state, array \$move): bool
    {
        if (\$state['gameOver']) {
            return false;
        }

        // Add your move validation logic here
        return true;
    }

    /**
     * Apply a move to the game state
     */
    public static function applyMove(array \$state, array \$move): array
    {
        if (!self::isValidMove(\$state, \$move)) {
            return \$state;
        }

        // Record the move
        \$state = self::recordMove(\$state, \$move);
        
        // Apply move logic here
        // \$state['board'] = ... your move logic
        
        // Check for game end conditions
        \$state = self::checkGameEnd(\$state);
        
        return \$state;
    }

    /**
     * Get all valid moves for current position
     */
    public static function getValidMoves(array \$state, \$position = null): array
    {
        if (\$state['gameOver']) {
            return [];
        }

        // Return array of valid moves
        return [];
    }

    /**
     * Check if the game has ended
     */
    private static function checkGameEnd(array \$state): array
    {
        // Add your win/loss/draw detection logic here
        
        return \$state;
    }

    /**
     * Calculate the current score
     */
    public static function calculateScore(array \$state): int
    {
        return self::calculateStandardScore(\$state, 1000);
    }

    /**
     * Get game statistics
     */
    public static function getStats(array \$state): array
    {
        \$baseStats = self::getBaseStats(\$state);
        
        return array_merge(\$baseStats, [
            'score' => self::calculateScore(\$state),
            // Add game-specific stats here
        ]);
    }

    /**
     * Get hints for the current player
     */
    public static function getHints(array \$state): array
    {
        return self::generateBasicHints(\$state);
    }

    /**
     * Get the best move for the current player
     */
    public static function getBestMove(array \$state): ?array
    {
        \$validMoves = self::getValidMoves(\$state);
        
        if (empty(\$validMoves)) {
            return null;
        }
        
        // Return the first valid move for now
        // Implement AI logic for better moves
        return \$validMoves[0] ?? null;
    }
}
PHP;

        file_put_contents("{$dir}/{$className}Engine.php", $template);
        $this->info("Created: {$className}Engine.php");
    }

    private function createAIClass($dir, $className)
    {
        $template = <<<PHP
<?php

namespace App\Games\\{$className};

class {$className}AI
{
    /**
     * Calculate the best move for the AI
     */
    public static function getBestMove(array \$state, string \$difficulty = 'medium'): ?array
    {
        \$validMoves = {$className}Engine::getValidMoves(\$state);
        
        if (empty(\$validMoves)) {
            return null;
        }
        
        return match(\$difficulty) {
            'easy' => self::getRandomMove(\$validMoves),
            'medium' => self::getDecentMove(\$state, \$validMoves),
            'hard' => self::getBestMove(\$state, \$validMoves),
            default => self::getDecentMove(\$state, \$validMoves),
        };
    }

    /**
     * Get a random valid move (easy difficulty)
     */
    private static function getRandomMove(array \$validMoves): array
    {
        return \$validMoves[array_rand(\$validMoves)];
    }

    /**
     * Get a decent move (medium difficulty)
     */
    private static function getDecentMove(array \$state, array \$validMoves): array
    {
        // Implement basic strategy here
        return \$validMoves[0];
    }

    /**
     * Get the optimal move (hard difficulty)
     */
    private static function getOptimalMove(array \$state, array \$validMoves): array
    {
        // Implement advanced AI strategy here
        // Consider using minimax, alpha-beta pruning, etc.
        return \$validMoves[0];
    }

    /**
     * Evaluate a game position
     */
    public static function evaluatePosition(array \$state): int
    {
        // Return a score representing how good the position is
        // Positive = good for current player, negative = bad
        return 0;
    }
}
PHP;

        file_put_contents("{$dir}/{$className}AI.php", $template);
        $this->info("Created: {$className}AI.php");
    }

    private function createBladeTemplate($slug, $className, $displayName, $type)
    {
        // Read the template file
        $templatePath = resource_path('views/games/_template.blade.php');
        if (!file_exists($templatePath)) {
            $this->error("Template file not found: {$templatePath}");
            return;
        }

        $template = file_get_contents($templatePath);
        
        // Replace placeholders
        $template = str_replace('{{GAME_NAME}}', $className, $template);
        $template = str_replace('{{GAME_SLUG}}', $slug, $template);
        $template = str_replace('{{GAME_DISPLAY_NAME}}', $displayName, $template);

        // Customize board section based on type
        $boardSection = $this->getBoardSectionForType($type);
        $template = str_replace(
            '{{-- REPLACE THIS SECTION WITH YOUR GAME BOARD',
            $boardSection . "\n                {{-- REPLACE THIS SECTION WITH YOUR GAME BOARD",
            $template
        );

        $bladePath = resource_path("views/livewire/games/{$slug}.blade.php");
        file_put_contents($bladePath, $template);
        $this->info("Created: resources/views/livewire/games/{$slug}.blade.php");
    }

    private function getBoardSectionForType($type): string
    {
        return match($type) {
            'board' => <<<BLADE
                <!-- 8x8 Board Game -->
                <div class="game-board-grid grid-8x8">
                    @for(\$row = 0; \$row < 8; \$row++)
                        @for(\$col = 0; \$col < 8; \$col++)
                            <div class="board-square {{ (\$row + \$col) % 2 ? 'dark-square' : 'light-square' }}"
                                 wire:click="makeMove(['row' => {{ \$row }}, 'col' => {{ \$col }}])">
                                
                                @if(\$piece = \$state['board'][\$row][\$col] ?? null)
                                    <x-game.piece
                                        type="circle"
                                        :piece="\$piece"
                                        :player="\$piece"
                                        size="default" />
                                @endif
                            </div>
                        @endfor
                    @endfor
                </div>
BLADE,
            'card' => <<<BLADE
                <!-- Card Game Layout -->
                <div class="game-board-card-table">
                    <div class="card-spread">
                        @foreach(\$state['hand'] ?? [] as \$card)
                            <x-game.card
                                :card="\$card"
                                :faceUp="true"
                                :draggable="true" />
                        @endforeach
                    </div>
                </div>
BLADE,
            'tile' => <<<BLADE
                <!-- 4x4 Tile Game -->
                <div class="tile-container" style="grid-template-columns: repeat(4, 1fr);">
                    @for(\$i = 0; \$i < 16; \$i++)
                        <div class="tile-slot">
                            @if((\$state['board'][\$i] ?? 0) > 0)
                                <x-game.tile
                                    :value="\$state['board'][\$i]"
                                    :position="\$i" />
                            @endif
                        </div>
                    @endfor
                </div>
BLADE,
            default => <<<BLADE
                <!-- Custom Game Layout -->
                <div class="game-board-flex">
                    <!-- Add your custom game board here -->
                    <div class="custom-game-area">
                        <!-- Implement your game's specific layout -->
                    </div>
                </div>
BLADE,
        };
    }

    private function showNextSteps($className, $slug, $displayName)
    {
        $this->info("\n🎉 Game scaffolding created successfully!");
        $this->info("\n📋 Next steps:");
        $this->line("1. Register your game in app/Providers/AppServiceProvider.php:");
        $this->line("   \$gameRegistry->register(new \\App\\Games\\{$className}\\{$className}Game());");
        $this->line("\n2. Implement your game logic in:");
        $this->line("   - app/Games/{$className}/{$className}Engine.php");
        $this->line("\n3. Customize your game UI in:");
        $this->line("   - resources/views/livewire/games/{$slug}.blade.php");
        $this->line("\n4. Add your game route or update the games list");
        $this->line("\n5. Test your game at: /games/{$slug}");
        $this->info("\n🎨 Your game will automatically have:");
        $this->line("  ✓ Liminal design system");
        $this->line("  ✓ Responsive layout");
        $this->line("  ✓ Dark mode support");
        $this->line("  ✓ Accessibility features");
        $this->line("  ✓ Undo/hint system");
        $this->line("  ✓ Game statistics");
        $this->info("\n📖 See GAME_DEVELOPMENT_GUIDE.md for detailed documentation");
    }
}

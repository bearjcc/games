<?php

namespace App\Games\Solitaire;

use App\Games\Contracts\GameInterface;

class SolitaireGame implements GameInterface
{
    public function id(): string
    {
        return 'solitaire';
    }

    public function name(): string
    {
        return 'Klondike Solitaire';
    }

    public function slug(): string
    {
        return 'solitaire';
    }

    public function description(): string
    {
        return 'Classic Klondike Solitaire - build foundation piles from Ace to King in each suit. Move cards between tableau columns with alternating colors and descending ranks.';
    }

    public function rules(): array
    {
        return [
            'Setup' => [
                '7 tableau columns with 1, 2, 3, 4, 5, 6, 7 cards respectively',
                'Only top card in each column starts face-up',
                'Remaining 24 cards form the stock pile',
                '4 foundation piles (one per suit) start empty'
            ],
            'Tableau Rules' => [
                'Build down in alternating colors (red on black, black on red)',
                'Move sequences of properly built cards together',
                'Empty spaces can only be filled with Kings',
                'Flip face-down cards when they become exposed'
            ],
            'Foundation Rules' => [
                'Build up by suit from Ace to King',
                'Once on foundation, cards can only be moved back to tableau if valid',
                'Foundations start with Aces only'
            ],
            'Stock and Waste' => [
                'Click stock to draw 3 cards to waste pile',
                'Use top waste card or flip through drawn cards',
                'When stock is empty, waste pile can be recycled to stock'
            ],
            'Scoring' => [
                'Waste to tableau: 5 points',
                'Waste to foundation: 10 points', 
                'Tableau to foundation: 10 points',
                'Revealing tableau card: 5 points',
                'Winning bonus: 500 points + time bonus + move efficiency'
            ],
            'Winning' => [
                'Get all 52 cards to the foundation piles',
                'Each foundation should have Ace through King of its suit'
            ]
        ];
    }

    public function initialState(): array
    {
        return SolitaireEngine::newGame();
    }

    public function newGameState(): array
    {
        return $this->initialState();
    }

    public function isOver(array $state): bool
    {
        return SolitaireEngine::isGameWon($state);
    }

    public function applyMove(array $state, array $move): array
    {
        switch ($move['type']) {
            case 'draw_stock':
                return SolitaireEngine::drawFromStock($state);
                
            case 'waste_to_tableau':
                return SolitaireEngine::moveWasteToTableau($state, $move['tableauCol']);
                
            case 'waste_to_foundation':
                return SolitaireEngine::moveWasteToFoundation($state, $move['suit']);
                
            case 'tableau_to_tableau':
                return SolitaireEngine::moveTableauToTableau(
                    $state, 
                    $move['fromCol'], 
                    $move['cardIndex'], 
                    $move['toCol']
                );
                
            case 'tableau_to_foundation':
                return SolitaireEngine::moveTableauToFoundation($state, $move['fromCol'], $move['suit']);
                
            default:
                return $state;
        }
    }

    public function validateMove(array $state, array $move): bool
    {
        // Test the move by applying it and checking if state changed meaningfully
        $newState = $this->applyMove($state, $move);
        
        switch ($move['type']) {
            case 'draw_stock':
                // Valid if we have stock cards or can recycle waste
                return !empty($state['stock']) || !empty($state['waste']);
                
            case 'waste_to_tableau':
                // Check if waste card exists and can be placed
                $wasteCard = SolitaireEngine::getWasteCard($state);
                if (!$wasteCard) return false;
                
                $targetCard = !empty($state['tableau'][$move['tableauCol']]) ? 
                             end($state['tableau'][$move['tableauCol']]) : null;
                return SolitaireEngine::canPlaceOnTableau($wasteCard, $targetCard);
                
            case 'waste_to_foundation':
                // Check if waste card can go to specified foundation
                $wasteCard = SolitaireEngine::getWasteCard($state);
                if (!$wasteCard || $wasteCard['suit'] !== $move['suit']) return false;
                
                return SolitaireEngine::canPlaceOnFoundation($wasteCard, $state['foundations'][$move['suit']]);
                
            case 'tableau_to_tableau':
                // Validate tableau move parameters
                if (!isset($state['tableau'][$move['fromCol']][$move['cardIndex']])) return false;
                if ($move['fromCol'] === $move['toCol']) return false;
                
                $movingCards = array_slice($state['tableau'][$move['fromCol']], $move['cardIndex']);
                if (empty($movingCards)) return false;
                
                // Check if first moving card can be placed on target
                $firstCard = $movingCards[0];
                $targetCard = !empty($state['tableau'][$move['toCol']]) ? 
                             end($state['tableau'][$move['toCol']]) : null;
                             
                return SolitaireEngine::canPlaceOnTableau($firstCard, $targetCard);
                
            case 'tableau_to_foundation':
                // Check if tableau top card can go to foundation
                if (empty($state['tableau'][$move['fromCol']])) return false;
                
                $card = end($state['tableau'][$move['fromCol']]);
                if (!$card['faceUp'] || $card['suit'] !== $move['suit']) return false;
                
                return SolitaireEngine::canPlaceOnFoundation($card, $state['foundations'][$move['suit']]);
                
            default:
                return false;
        }
    }

    public function getScore(array $state): int
    {
        return SolitaireEngine::getScore($state);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Services\AssetManager;
use PHPUnit\Framework\TestCase;

class AssetManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AssetManager::clearCache();
    }

    public function test_get_piece_asset_with_valid_parameters()
    {
        $url = AssetManager::getPieceAsset('Red', 'border', 5);
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Pieces (Red)/pieceRed_border05.png', $url);
    }

    public function test_get_piece_asset_caches_results()
    {
        $url1 = AssetManager::getPieceAsset('Red', 'border', 5);
        $url2 = AssetManager::getPieceAsset('Red', 'border', 5);
        
        $this->assertSame($url1, $url2);
    }

    public function test_get_piece_asset_throws_exception_for_invalid_color()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid piece color: Invalid');
        
        AssetManager::getPieceAsset('Invalid', 'border', 5);
    }

    public function test_get_piece_asset_throws_exception_for_invalid_style()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid piece style: invalid');
        
        AssetManager::getPieceAsset('Red', 'invalid', 5);
    }

    public function test_get_piece_asset_throws_exception_for_invalid_index()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid piece index: 25. Must be 0-18');
        
        AssetManager::getPieceAsset('Red', 'border', 25);
    }

    public function test_get_dice_asset_with_valid_parameters()
    {
        $url = AssetManager::getDiceAsset('red', 'border', 3);
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Dice/dieRed_border3.png', $url);
    }

    public function test_get_dice_asset_without_border()
    {
        $url = AssetManager::getDiceAsset('white', 'no-border', 6);
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Dice/dieWhite6.png', $url);
    }

    public function test_get_dice_asset_throws_exception_for_invalid_color()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dice color: blue');
        
        AssetManager::getDiceAsset('blue', 'border', 3);
    }

    public function test_get_dice_asset_throws_exception_for_invalid_value()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dice value: 7. Must be 1-6');
        
        AssetManager::getDiceAsset('red', 'border', 7);
    }

    public function test_get_chip_asset_with_valid_parameters()
    {
        $url = AssetManager::getChipAsset('RedWhite', 'border');
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Chips/chipRedWhite_border.png', $url);
    }

    public function test_get_chip_asset_standard_style()
    {
        $url = AssetManager::getChipAsset('Blue', 'standard');
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Chips/chipBlue.png', $url);
    }

    public function test_get_chip_asset_throws_exception_for_invalid_color()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid chip color: Invalid');
        
        AssetManager::getChipAsset('Invalid', 'border');
    }

    public function test_get_card_back_asset_with_valid_parameters()
    {
        $url = AssetManager::getCardBackAsset('blue', 1);
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Cards/cardBack_blue1.png', $url);
    }

    public function test_get_card_back_asset_throws_exception_for_invalid_color()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid card back color: yellow');
        
        AssetManager::getCardBackAsset('yellow', 1);
    }

    public function test_get_card_back_asset_throws_exception_for_invalid_style()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid card back style: 6. Must be 1-5');
        
        AssetManager::getCardBackAsset('blue', 6);
    }

    public function test_get_card_asset_with_valid_parameters()
    {
        $url = AssetManager::getCardAsset('hearts', 'A');
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Cards/cardHeartsA.png', $url);
    }

    public function test_get_card_asset_with_numeric_rank()
    {
        $url = AssetManager::getCardAsset('spades', '10');
        
        $this->assertStringContainsString('@images/', $url);
        $this->assertStringContainsString('Cards/cardSpades10.png', $url);
    }

    public function test_get_card_asset_throws_exception_for_invalid_suit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid card suit: invalid');
        
        AssetManager::getCardAsset('invalid', 'A');
    }

    public function test_get_card_asset_throws_exception_for_invalid_rank()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid card rank: Z');
        
        AssetManager::getCardAsset('hearts', 'Z');
    }

    public function test_clear_cache()
    {
        $url1 = AssetManager::getPieceAsset('Red', 'border', 5);
        AssetManager::clearCache();
        $url2 = AssetManager::getPieceAsset('Red', 'border', 5);
        
        $this->assertSame($url1, $url2);
    }

    public function test_get_piece_colors()
    {
        $colors = AssetManager::getPieceColors();
        
        $this->assertContains('Red', $colors);
        $this->assertContains('Black', $colors);
        $this->assertContains('Blue', $colors);
        $this->assertCount(7, $colors);
    }

    public function test_get_piece_styles()
    {
        $styles = AssetManager::getPieceStyles();
        
        $this->assertContains('border', $styles);
        $this->assertContains('multi', $styles);
        $this->assertContains('single', $styles);
        $this->assertCount(3, $styles);
    }

    public function test_get_dice_colors()
    {
        $colors = AssetManager::getDiceColors();
        
        $this->assertContains('red', $colors);
        $this->assertContains('white', $colors);
        $this->assertCount(2, $colors);
    }

    public function test_get_chip_colors()
    {
        $colors = AssetManager::getChipColors();
        
        $this->assertContains('RedWhite', $colors);
        $this->assertContains('Blue', $colors);
        $this->assertCount(8, $colors);
    }

    public function test_get_card_back_colors()
    {
        $colors = AssetManager::getCardBackColors();
        
        $this->assertContains('blue', $colors);
        $this->assertContains('green', $colors);
        $this->assertContains('red', $colors);
        $this->assertCount(3, $colors);
    }

    public function test_all_piece_combinations_generate_valid_urls()
    {
        $colors = AssetManager::getPieceColors();
        $styles = AssetManager::getPieceStyles();
        
        foreach ($colors as $color) {
            foreach ($styles as $style) {
                for ($index = 0; $index <= 18; $index++) {
                    $url = AssetManager::getPieceAsset($color, $style, $index);
                    $this->assertStringContainsString('@images/', $url);
                    $this->assertStringContainsString("Pieces ({$color})/piece{$color}_{$style}" . sprintf('%02d', $index) . '.png', $url);
                }
            }
        }
    }

    public function test_all_dice_combinations_generate_valid_urls()
    {
        $colors = AssetManager::getDiceColors();
        $styles = ['border', 'no-border'];
        
        foreach ($colors as $color) {
            foreach ($styles as $style) {
                for ($value = 1; $value <= 6; $value++) {
                    $url = AssetManager::getDiceAsset($color, $style, $value);
                    $this->assertStringContainsString('@images/', $url);
                    
                    $styleSuffix = $style === 'border' ? '_border' : '';
                    $colorCapitalized = ucfirst($color);
                    $this->assertStringContainsString("Dice/die{$colorCapitalized}{$styleSuffix}{$value}.png", $url);
                }
            }
        }
    }

    public function test_all_chip_combinations_generate_valid_urls()
    {
        $colors = AssetManager::getChipColors();
        $styles = ['standard', 'border', 'side', 'sideBorder'];
        
        foreach ($colors as $color) {
            foreach ($styles as $style) {
                $url = AssetManager::getChipAsset($color, $style);
                $this->assertStringContainsString('@images/', $url);
                
                $styleSuffix = $style === 'standard' ? '' : "_{$style}";
                $this->assertStringContainsString("Chips/chip{$color}{$styleSuffix}.png", $url);
            }
        }
    }

    public function test_all_card_back_combinations_generate_valid_urls()
    {
        $colors = AssetManager::getCardBackColors();
        $styles = [1, 2, 3, 4, 5];
        
        foreach ($colors as $color) {
            foreach ($styles as $style) {
                $url = AssetManager::getCardBackAsset($color, $style);
                $this->assertStringContainsString('@images/', $url);
                $this->assertStringContainsString("Cards/cardBack_{$color}{$style}.png", $url);
            }
        }
    }

    public function test_all_card_combinations_generate_valid_urls()
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
        
        foreach ($suits as $suit) {
            foreach ($ranks as $rank) {
                $url = AssetManager::getCardAsset($suit, $rank);
                $this->assertStringContainsString('@images/', $url);
                $this->assertStringContainsString("Cards/card" . ucfirst($suit) . "{$rank}.png", $url);
            }
        }
    }
}

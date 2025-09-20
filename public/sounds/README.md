# Game Sound Effects

This directory contains audio files for game enhancements.

## Required Sound Files

To enable full audio functionality, add the following sound files:

- `dice-roll.mp3` - Dice rolling sound effect
- `card-deal.mp3` - Card dealing sound effect  
- `card-flip.mp3` - Card flipping sound effect
- `move.mp3` - General move/click sound effect
- `win.mp3` - Win celebration sound effect
- `lose.mp3` - Lose/game over sound effect
- `background.mp3` - Background music (optional)

## Audio Format

- Format: MP3
- Quality: 128kbps or higher
- Duration: 1-3 seconds for effects, longer for background music
- Volume: Normalized to prevent clipping

## Usage

The GameAudioService will automatically load these files when the game enhancements are initialized. If files are missing, audio will be disabled gracefully.

## License

Ensure all audio files are properly licensed for use in your application.

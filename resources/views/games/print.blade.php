<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ucfirst($game) }} Puzzle - Print</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        
        @media screen {
            .print-only { display: none !important; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        
        .puzzle-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .puzzle-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .puzzle-info {
            font-size: 14px;
            color: #666;
        }
        
        .sudoku-print-grid {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            grid-template-rows: repeat(9, 1fr);
            width: 450px;
            height: 450px;
            margin: 0 auto 30px;
            border: 3px solid #000;
            background: white;
        }
        
        .sudoku-print-cell {
            width: 50px;
            height: 50px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .border-top-thick {
            border-top: 3px solid #000 !important;
        }
        
        .border-left-thick {
            border-left: 3px solid #000 !important;
        }
        
        .puzzle-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .solution-grid {
            display: grid;
            grid-template-columns: repeat(9, 1fr);
            grid-template-rows: repeat(9, 1fr);
            width: 450px;
            height: 450px;
            margin: 0 auto;
            border: 3px solid #000;
            background: white;
        }
        
        .instructions {
            max-width: 600px;
            margin: 0 auto;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .instructions h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 5px;
        }
        
        .back-button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <button class="back-button no-print" onclick="window.history.back()">← Back to Game</button>
    
    <div class="puzzle-header">
        <div class="puzzle-title">{{ ucfirst($game) }} Puzzle</div>
        <div class="puzzle-info">
            Difficulty: {{ ucfirst($puzzleData['difficulty']) }} | 
            Generated: {{ $puzzleData['timestamp'] }}
        </div>
    </div>

    <div class="puzzle-section">
        <div class="section-title">Puzzle</div>
        <div class="sudoku-print-grid">
            @for($row = 0; $row < 9; $row++)
                @for($col = 0; $col < 9; $col++)
                    <div class="sudoku-print-cell {{ ($row % 3 === 0 && $row > 0) ? 'border-top-thick' : '' }} {{ ($col % 3 === 0 && $col > 0) ? 'border-left-thick' : '' }}">
                        {{ $puzzleData['puzzle'][$row][$col] !== 0 ? $puzzleData['puzzle'][$row][$col] : '' }}
                    </div>
                @endfor
            @endfor
        </div>
    </div>

    <div class="puzzle-section">
        <div class="section-title">Solution</div>
        <div class="solution-grid">
            @for($row = 0; $row < 9; $row++)
                @for($col = 0; $col < 9; $col++)
                    <div class="sudoku-print-cell {{ ($row % 3 === 0 && $row > 0) ? 'border-top-thick' : '' }} {{ ($col % 3 === 0 && $col > 0) ? 'border-left-thick' : '' }}">
                        {{ $puzzleData['solution'][$row][$col] }}
                    </div>
                @endfor
            @endfor
        </div>
    </div>

    <div class="instructions">
        <h3>How to Play Sudoku:</h3>
        <ul>
            <li>Fill the 9×9 grid with digits 1-9</li>
            <li>Each row must contain all digits 1-9 exactly once</li>
            <li>Each column must contain all digits 1-9 exactly once</li>
            <li>Each 3×3 sub-grid must contain all digits 1-9 exactly once</li>
        </ul>
        
        <h3>Tips:</h3>
        <ul>
            <li>Start with cells that have the fewest possible numbers</li>
            <li>Look for numbers that can only go in one place in a row, column, or box</li>
            <li>Use pencil marks to track possible numbers for each cell</li>
            <li>Don't guess - every puzzle has a logical solution</li>
        </ul>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>

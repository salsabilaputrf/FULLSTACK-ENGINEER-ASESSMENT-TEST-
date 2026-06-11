<?php
// cari item tersembunyi di grid
// Pergerakan: Utara A langkah → Timur B langkah → Selatan C langkah

// '#' = rintangan, '.' = jalan, 'X' = start
$grid = [
    ['#', '#', '#', '#', '#', '#', '#', '#'],
    ['#', '.', '.', '.', '.', '.', '.', '#'],
    ['#', '.', '#', '#', '#', '.', '.', '#'],
    ['#', '.', '.', '.', '#', '.', '#', '#'],
    ['#', 'X', '#', '.', '.', '.', '.', '#'],
    ['#', '#', '#', '#', '#', '#', '#', '#'],
];

$steps_north = 2;
$steps_east  = 3;
$steps_south = 1;

// Cari posisi 'X' di grid
$start_row = $start_col = null;
foreach ($grid as $r => $row) {
    foreach ($row as $c => $cell) {
        if ($cell === 'X') {
            [$start_row, $start_col] = [$r, $c];
            break 2;
        }
    }
}

if ($start_row === null) {
    die("Error: Tidak ada posisi 'X' di dalam grid!\n");
}

$total_rows = count($grid);
$total_cols = count($grid[0]);

function isValidPosition(array $grid, int $row, int $col): bool
{
    return $row >= 0 && $row < count($grid)
        && $col >= 0 && $col < count($grid[0])
        && $grid[$row][$col] !== '#';
}

// Rekursif: cari semua posisi yang bisa dicapai setelah N langkah ke satu arah. Jika kena rintangan sebelum habis, pemain berhenti di posisi terakhir yang valid
function getReachablePositions(
    array $grid, int $row, int $col,
    string $direction, int $steps, array &$visited = []
): array {
    $key = "{$row},{$col}";
    if (isset($visited[$key])) return [];
    $visited[$key] = true;

    if ($steps === 0) return [[$row, $col]];

    [$dr, $dc] = match ($direction) {
        'N' => [-1,  0],
        'E' => [ 0,  1],
        'S' => [ 1,  0],
        'W' => [ 0, -1],
    };

    $next_row = $row + $dr;
    $next_col = $col + $dc;

    if (isValidPosition($grid, $next_row, $next_col)) {
        return getReachablePositions($grid, $next_row, $next_col, $direction, $steps - 1, $visited);
    }

    // Jika ada rintangan berhenti di sini
    return [[$row, $col]];
}

// Jalankan 3 fase pergerakan
$v1 = [];
$after_north = array_unique(
    getReachablePositions($grid, $start_row, $start_col, 'N', $steps_north, $v1),
    SORT_REGULAR
);

$after_east = [];
foreach ($after_north as $pos) {
    $v2 = [];
    $after_east = array_merge($after_east,
        getReachablePositions($grid, $pos[0], $pos[1], 'E', $steps_east, $v2)
    );
}
$after_east = array_unique($after_east, SORT_REGULAR);

$final_positions = [];
foreach ($after_east as $pos) {
    $v3 = [];
    $final_positions = array_merge($final_positions,
        getReachablePositions($grid, $pos[0], $pos[1], 'S', $steps_south, $v3)
    );
}

$final_positions = array_unique($final_positions, SORT_REGULAR);
$final_positions = array_filter($final_positions, function ($pos) use ($grid, $start_row, $start_col) {
    [$r, $c] = $pos;
    return $grid[$r][$c] !== '#' && !($r === $start_row && $c === $start_col);
});

// koordinat
echo "HIDDEN ITEM GAME\n";
echo "Posisi awal: baris={$start_row}, kolom={$start_col}\n";
echo "Langkah: Utara {$steps_north} -> Timur {$steps_east} -> Selatan {$steps_south}\n\n";

echo "KEMUNGKINAN LOKASI ITEM\n";
if (empty($final_positions)) {
    echo "Tidak ada posisi yang memungkinkan.\n";
} else {
    echo "Ditemukan " . count($final_positions) . " kemungkinan lokasi:\n\n";
    foreach (array_values($final_positions) as $i => $pos) {
        [$r, $c] = $pos;
        echo "  [" . ($i + 1) . "] Baris={$r}, Kolom={$c}\n";
    }
}

// visualisasi grid dengan '$' sebagai penanda lokasi item
$display_grid = $grid;
foreach ($final_positions as $pos) {
    $display_grid[$pos[0]][$pos[1]] = '$';
}

echo "\nVISUALISASI GRID\n";
echo "X=start  #=rintangan  \$=lokasi item\n\n";

echo "    ";
for ($c = 0; $c < $total_cols; $c++) echo " {$c} ";
echo "\n    " . str_repeat("───", $total_cols) . "\n";

foreach ($display_grid as $r => $row) {
    echo " {$r} │";
    foreach ($row as $cell) {
        $colored = match ($cell) {
            'X'     => "\033[1;33mX\033[0m",
            '#'     => "\033[1;31m#\033[0m",
            '$'     => "\033[1;32m\$\033[0m",
            default => $cell,
        };
        echo " {$colored} ";
    }
    echo "\n";
}

echo "\nSymbol:\n";
echo "  \033[1;33mX\033[0m = Posisi awal\n";
echo "  \033[1;31m#\033[0m = Rintangan\n";
echo "  . = Jalan bebas\n";
echo "  \033[1;32m\$\033[0m = Kemungkinan lokasi item\n\n";
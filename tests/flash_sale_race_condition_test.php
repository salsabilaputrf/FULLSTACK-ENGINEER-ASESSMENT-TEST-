<?php
$BASE_URL     = 'http://localhost:8000';
$PRODUCT_ID   = 1;
$STOCK_LIMIT  = 10;
$TOTAL_BUYERS = 50;

echo "FLASH SALE RACE CONDITION TEST\n";
echo "API         : {$BASE_URL}\n";
echo "Produk ID   : {$PRODUCT_ID}\n";
echo "Stok        : {$STOCK_LIMIT}\n";
echo "Total Buyer : {$TOTAL_BUYERS}\n\n";

// [1] Reset stok
echo "[1/4] Mereset stok produk ke {$STOCK_LIMIT}...\n";
$resetResponse = httpRequest('PUT', "{$BASE_URL}/products/{$PRODUCT_ID}", [
    'name'        => 'Laptop Gaming XYZ',
    'price'       => 15000000,
    'flash_price' => 9999000,
    'stock'       => $STOCK_LIMIT,
]);

if (!$resetResponse['success']) {
    die("Gagal reset produk! Pastikan API berjalan dan produk ID={$PRODUCT_ID} ada.\nError: " . ($resetResponse['message'] ?? 'Unknown') . "\n");
}
echo "  Stok berhasil direset ke {$STOCK_LIMIT}\n\n";

// [2] Cek stok awal
echo "[2/4] Memeriksa stok awal...\n";
$productBefore = httpRequest('GET', "{$BASE_URL}/products/{$PRODUCT_ID}");
$stokAwal      = (int) $productBefore['data']['stock'];
echo "  Stok awal: {$stokAwal}\n\n";

// [3] Kirim semua request bersamaan pakai cURL Multi
echo "[3/4] Mengirim {$TOTAL_BUYERS} request secara bersamaan...\n";

$multiHandle = curl_multi_init();
$handles     = [];

for ($i = 1; $i <= $TOTAL_BUYERS; $i++) {
    $ch = curl_init("{$BASE_URL}/orders");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'customer_name' => "Pembeli #{$i}",
            'items'         => [['product_id' => $PRODUCT_ID, 'quantity' => 1]],
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $handles[$i] = $ch;
    curl_multi_add_handle($multiHandle, $ch);
}

$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

$successCount = 0;
$failCount    = 0;

for ($i = 1; $i <= $TOTAL_BUYERS; $i++) {
    $data = json_decode(curl_multi_getcontent($handles[$i]), true);
    $data && $data['success'] === true ? $successCount++ : $failCount++;
    curl_multi_remove_handle($multiHandle, $handles[$i]);
    curl_close($handles[$i]);
}
curl_multi_close($multiHandle);
echo "  Semua request selesai diproses\n\n";

// [4] Verifikasi
echo "[4/4] Memverifikasi hasil...\n\n";

$stokAkhir = (int) httpRequest('GET', "{$BASE_URL}/products/{$PRODUCT_ID}")['data']['stock'];

echo "Total request dikirim : {$TOTAL_BUYERS}\n";
echo "Pesanan BERHASIL      : {$successCount}\n";
echo "Pesanan DITOLAK       : {$failCount}\n";
echo "Stok awal             : {$stokAwal}\n";
echo "Stok akhir            : {$stokAkhir}\n\n";

$testPassed    = true;
$jumlahTerjual = $stokAwal - $stokAkhir;

$checks = [
    $successCount <= $stokAwal
        => "Pesanan berhasil ({$successCount}) tidak melebihi stok ({$stokAwal})",
    $stokAkhir >= 0
        => "Stok akhir tidak negatif ({$stokAkhir})",
    $successCount === $jumlahTerjual
        => "Konsistensi data terjaga (sukses={$successCount}, terjual={$jumlahTerjual})",
];

foreach ($checks as $passed => $message) {
    if ($passed) {
        echo "LULUS : {$message}\n";
    } else {
        echo "GAGAL : {$message}\n";
        $testPassed = false;
    }
}

echo "\n" . ($testPassed ? "SEMUA TEST LULUS!\n" : "ADA TEST YANG GAGAL!\n") . "\n";

exit($testPassed ? 0 : 1);


function httpRequest(string $method, string $url, array $body = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);

    if (!empty($body)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? ['success' => false, 'message' => 'Invalid response'];
}
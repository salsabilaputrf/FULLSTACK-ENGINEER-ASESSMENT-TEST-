<?php
// Entry point — semua request masuk sini, lalu diarahkan ke controller

spl_autoload_register(function (string $class): void {
    // App\Controllers\ProductController -> /src/Controllers/ProductController.php
    $file = __DIR__ . '/../src/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once __DIR__ . '/../src/Helpers/Response.php';

$method = $_SERVER['REQUEST_METHOD'];
$uri    = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

$body = [];
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

use App\Controllers\ProductController;
use App\Controllers\OrderController;
use App\Helpers\Response;

if (preg_match('#^/products(?:/(\d+))?$#', $uri, $matches)) {
    $id         = isset($matches[1]) ? (int) $matches[1] : null;
    $controller = new ProductController();

    match (true) {
        $method === 'GET'    && $id === null  => $controller->index(),
        $method === 'GET'    && $id !== null  => $controller->show($id),
        $method === 'POST'   && $id === null  => $controller->store($body),
        $method === 'PUT'    && $id !== null  => $controller->update($id, $body),
        $method === 'DELETE' && $id !== null  => $controller->destroy($id),
        default                               => Response::error('Endpoint tidak ditemukan', 404),
    };

} elseif (preg_match('#^/orders(?:/(\d+))?$#', $uri, $matches)) {
    $id         = isset($matches[1]) ? (int) $matches[1] : null;
    $controller = new OrderController();

    match (true) {
        $method === 'GET'  && $id === null => $controller->index(),
        $method === 'GET'  && $id !== null => $controller->show($id),
        $method === 'POST' && $id === null => $controller->store($body),
        default                            => Response::error('Endpoint tidak ditemukan', 404),
    };

} elseif ($uri === '' || $uri === '/') {
    echo json_encode([
        'success'   => true,
        'message'   => 'Selamat datang di Online Store API!',
        'endpoints' => [
            'GET    /products'       => 'Ambil semua produk',
            'GET    /products/{id}'  => 'Ambil satu produk',
            'POST   /products'       => 'Tambah produk baru',
            'PUT    /products/{id}'  => 'Update produk',
            'DELETE /products/{id}'  => 'Hapus produk',
            'GET    /orders'         => 'Ambil semua pesanan',
            'GET    /orders/{id}'    => 'Ambil detail pesanan',
            'POST   /orders'         => 'Buat pesanan baru',
        ],
    ], JSON_PRETTY_PRINT);

} else {
    Response::error('Endpoint tidak ditemukan', 404);
}
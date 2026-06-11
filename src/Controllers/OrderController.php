<?php
// Controller untuk endpoint-endpoint order

namespace App\Controllers;

use App\Models\Order;
use App\Helpers\Response;
use Exception;

class OrderController
{
    private Order $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    
    // GET /orders
    // Tampilkan semua pesanan
    public function index(): void
    {
        $orders = $this->orderModel->getAll();
        Response::success($orders, 'Daftar semua pesanan berhasil diambil');
    }

    // GET /orders/{id}
    // Tampilkan detail satu pesanan
    public function show(int $id): void
    {
        $order = $this->orderModel->findById($id);

        if (!$order) {
            Response::error('Pesanan tidak ditemukan', 404);
        }

        Response::success($order, 'Detail pesanan berhasil diambil');
    }

    /**
     * POST /orders
     * Buat pesanan baru.
     *
     * Contoh body JSON yang dikirim:
     * {
     *   "customer_name": "Budi Santoso",
     *   "items": [
     *     { "product_id": 1, "quantity": 1 },
     *     { "product_id": 2, "quantity": 2 }
     *   ]
     * }
     */
    public function store(array $body): void
    {
        // Validasi input 
        $errors = $this->validateOrder($body);
        if (!empty($errors)) {
            Response::error('Validasi gagal', 422, $errors);
        }

        try {
            // throw jika stok abis
            $order = $this->orderModel->create(
                $body['customer_name'],
                $body['items']
            );

            Response::success($order, 'Pesanan berhasil dibuat!', 201);

        } catch (Exception $e) {
            Response::error($e->getMessage(), 409);
        }
    }

    // Validasi data pesanan yang masuk
    private function validateOrder(array $data): array
    {
        $errors = [];

        if (empty($data['customer_name'])) {
            $errors[] = 'Nama customer wajib diisi';
        }

        // Pesanan harus punya minimal 1 item
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'Pesanan harus mengandung minimal 1 item';
            return $errors;
        }

        // Validasi setiap item
        foreach ($data['items'] as $index => $item) {
            $no = $index + 1;

            if (empty($item['product_id']) || !is_numeric($item['product_id'])) {
                $errors[] = "Item ke-{$no}: product_id wajib diisi dan harus berupa angka";
            }

            if (empty($item['quantity']) || !is_numeric($item['quantity']) || $item['quantity'] < 1) {
                $errors[] = "Item ke-{$no}: quantity harus minimal 1";
            }
        }

        return $errors;
    }
}

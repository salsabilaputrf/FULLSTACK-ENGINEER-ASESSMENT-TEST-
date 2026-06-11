<?php
// Model untuk semua order

namespace App\Models;

use App\Database\Connection;
use PDO;
use Exception;

class Order
{
    private PDO $db;
    private Product $productModel;

    public function __construct()
    {
        $this->db           = Connection::getInstance();
        $this->productModel = new Product();
    }

    // Ambil semua order beserta item-itemnya
    public function getAll(): array
    {
        $orders = $this->db->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();

        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }

        return $orders;
    }

    // Cari satu order berdasarkan ID, lengkap dengan item-nya
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch();

        if ($order) {
            $order['items'] = $this->getOrderItems($id);
        }

        return $order;
    }

    /**
     * Buat order baru.
     *
     * Alur kerjanya:
     * 1. Mulai transaksi
     * 2. Lock stok produk 
     * 3. Kurangi stok
     * 4. Simpan order dan order_items
     * 5. Commit atau Rollback kalau error
     *
     */
    public function create(string $customerName, array $items): array
    {
        $this->db->beginTransaction();

        try {
            $totalPrice     = 0;
            $processedItems = [];

            // Proses setiap item yang dipesan
            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity  = (int) $item['quantity'];

                // Kunci stok produk ini dan kurangi 
                $product = $this->productModel->decreaseStockWithLock($productId, $quantity);

                if ($product === false) {
                    throw new Exception("Stok produk ID {$productId} tidak mencukupi atau produk tidak ditemukan.");
                }

                $effectivePrice = $product['flash_price'] !== null
                    ? (float) $product['flash_price']
                    : (float) $product['price'];

                $subtotal    = $effectivePrice * $quantity;
                $totalPrice += $subtotal;

                $processedItems[] = [
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'price'      => $effectivePrice,
                ];
            }

            $orderStmt = $this->db->prepare(
                'INSERT INTO orders (customer_name, total_price, status)
                 VALUES (:customer_name, :total_price, :status)'
            );
            $orderStmt->execute([
                ':customer_name' => $customerName,
                ':total_price'   => $totalPrice,
                ':status'        => 'confirmed', 
            ]);

            $orderId = (int) $this->db->lastInsertId();

            // Simpan detail item-itemnya ke tabel order_items
            $itemStmt = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, price)
                 VALUES (:order_id, :product_id, :quantity, :price)'
            );

            foreach ($processedItems as $processedItem) {
                $itemStmt->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $processedItem['product_id'],
                    ':quantity'   => $processedItem['quantity'],
                    ':price'      => $processedItem['price'],
                ]);
            }

            $this->db->commit();

            return $this->findById($orderId);

        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    // Ambil semua item dari sebuah pesanan
    private function getOrderItems(int $orderId): array
    {
        $sql = 'SELECT oi.*, p.name as product_name
                FROM order_items oi
                JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = :order_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}

<?php
// Model untuk semua produk

namespace App\Models;

use App\Database\Connection;
use PDO;

class Product
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    // Ambil semua produk yang ada
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM products ORDER BY id ASC');
        return $stmt->fetchAll();
    }

    // Cari satu produk berdasarkan ID-nya
    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Tambah produk baru ke database
    public function create(array $data): int
    {
        $sql = 'INSERT INTO products (name, price, flash_price, stock)
                VALUES (:name, :price, :flash_price, :stock)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name'        => $data['name'],
            ':price'       => $data['price'],
            ':flash_price' => $data['flash_price'] ?? null,
            ':stock'       => $data['stock'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Update data produk yang sudah ada
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE products
                SET name = :name, price = :price, flash_price = :flash_price, stock = :stock
                WHERE id = :id';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id'          => $id,
            ':name'        => $data['name'],
            ':price'       => $data['price'],
            ':flash_price' => $data['flash_price'] ?? null,
            ':stock'       => $data['stock'],
        ]);
    }

    // Hapus produk 
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Kurangi stok produk dengan menggunakan database locking.
    public function decreaseStockWithLock(int $productId, int $qty): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products WHERE id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            return false;
        }

        if ($product['stock'] < $qty) {
            return false; 
        }

        // Stok cukup, kurangi stoknya
        $updateStmt = $this->db->prepare(
            'UPDATE products SET stock = stock - :qty WHERE id = :id'
        );
        $updateStmt->execute([
            ':qty' => $qty,
            ':id'  => $productId,
        ]);

        return $product;
    }
}

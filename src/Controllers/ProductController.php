<?php
// Controller untuk endpoint-endpoint yang berkaitan dengan Produk

namespace App\Controllers;

use App\Models\Product;
use App\Helpers\Response;

class ProductController
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    // GET /products
    // Tampilkan semua produk
    public function index(): void
    {
        $products = $this->productModel->getAll();
        Response::success($products, 'Daftar semua produk berhasil diambil');
    }

    // GET /products/{id}
    // Tampilkan satu produk berdasarkan ID
    public function show(int $id): void
    {
        $product = $this->productModel->findById($id);

        if (!$product) {
            Response::error('Produk tidak ditemukan', 404);
        }

        Response::success($product, 'Data produk berhasil diambil');
    }

    // POST /products
    // Tambah produk baru
    public function store(array $body): void
    {
        // Validasi
        $errors = $this->validateProduct($body);
        if (!empty($errors)) {
            Response::error('Validasi gagal', 422, $errors);
        }

        $newId   = $this->productModel->create($body);
        $product = $this->productModel->findById($newId);

        Response::success($product, 'Produk baru berhasil ditambahkan', 201);
    }

    // PUT /products/{id}
    // Update data produk yang sudah ada
    public function update(int $id, array $body): void
    {
        // Cek dulu apakah produknya ada
        $existing = $this->productModel->findById($id);
        if (!$existing) {
            Response::error('Produk tidak ditemukan', 404);
        }

        $errors = $this->validateProduct($body);
        if (!empty($errors)) {
            Response::error('Validasi gagal', 422, $errors);
        }

        $this->productModel->update($id, $body);
        $product = $this->productModel->findById($id);

        Response::success($product, 'Data produk berhasil diupdate');
    }

    // DELETE /products/{id}
    // Hapus produk
    public function destroy(int $id): void
    {
        $existing = $this->productModel->findById($id);
        if (!$existing) {
            Response::error('Produk tidak ditemukan', 404);
        }

        $this->productModel->delete($id);

        Response::success(null, 'Produk berhasil dihapus');
    }

    // Validasi data produk.
    // Kembalikan array error kalau ada yang tidak valid.
    private function validateProduct(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Nama produk wajib diisi';
        }

        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = 'Harga produk harus berupa angka positif';
        }

        if (!isset($data['stock']) || !is_numeric($data['stock']) || $data['stock'] < 0) {
            $errors[] = 'Stok harus berupa angka tidak negatif';
        }

        if (isset($data['flash_price']) && $data['flash_price'] !== null && $data['flash_price'] !== '') {
            if (!is_numeric($data['flash_price']) || $data['flash_price'] < 0) {
                $errors[] = 'Harga flash sale harus berupa angka positif';
            }
        }

        return $errors;
    }
}

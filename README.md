# Task Online Store API + Hidden Item Game

Submission untuk Fullstack Engineer Assessment Test.

---

## Task 1: Online Store API

API toko online berbasis PHP murni + MySQL yang mampu menangani **flash sale** dengan banyak pembeli sekaligus tanpa stok jadi negatif (*race condition safe*).

### Cara Kerja Proteksi Race Condition

Kunci utamanya ada di `src/Models/Product.php` method `decreaseStockWithLock()`:

```sql
SELECT * FROM products WHERE id = :id FOR UPDATE
```

Dengan `SELECT ... FOR UPDATE`, baris produk **dikunci di level database**. Kalau 1000 pembeli datang bersamaan saat flash sale, mereka akan **antri** satu per satu saat mengakses baris produk tersebut. Sehingga stok tidak akan pernah jadi negatif.

Alur lengkapnya menggunakan **Database Transaction**:
1. `BEGIN TRANSACTION`
2. `SELECT ... FOR UPDATE` → kunci stok
3. Cek stok cukup? Kalau tidak → `ROLLBACK`
4. Kurangi stok → simpan order → `COMMIT`

### Cara Install & Jalankan

**Requirement:** PHP 8.1+, MySQL 5.7+ 

```bash
# 1. Clone repository
git clone <repo-url>
cd online-store

# 2. Setup database
mysql -u root -p < database.sql

# 3. Sesuaikan konfigurasi database 
# Edit: config/database.php
export DB_HOST=localhost
export DB_NAME=online_store
export DB_USERNAME=root
export DB_PASSWORD=yourpassword

# 4. Jalankan development server
php -S localhost:8000 -t public
```

### Endpoint API

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/products` | Daftar semua produk |
| GET | `/products/{id}` | Detail produk |
| POST | `/products` | Tambah produk baru |
| PUT | `/products/{id}` | Update produk |
| DELETE | `/products/{id}` | Hapus produk |
| GET | `/orders` | Daftar semua pesanan |
| GET | `/orders/{id}` | Detail pesanan |
| POST | `/orders` | **Buat pesanan baru (flash sale ready!)** |

### Contoh Request

**Buat Pesanan (POST /orders):**
```json
{
  "customer_name": "Budi Santoso",
  "items": [
    { "product_id": 1, "quantity": 1 },
    { "product_id": 2, "quantity": 2 }
  ]
}
```

**Response Sukses (201):**
```json
{
  "success": true,
  "message": "Pesanan berhasil dibuat!",
  "data": {
    "id": 1,
    "customer_name": "Budi Santoso",
    "total_price": "10699000.00",
    "status": "confirmed",
    "items": [...]
  }
}
```

**Response Stok Habis (409):**
```json
{
  "success": false,
  "message": "Stok produk ID 1 tidak mencukupi atau produk tidak ditemukan."
}
```

### Jalankan Test Race Condition

```bash
# Pastikan server sudah berjalan di localhost:8000
php tests/flash_sale_race_condition_test.php

```

Test ini akan mengirim **50 request bersamaan** untuk produk yang stoknya **10**. Hasilnya:
- ✅ Tepat 10 pesanan berhasil
- ✅ 40 pesanan ditolak (stok habis)
- ✅ Stok akhir = 0 (tidak negatif!)

---

## Task 2: Hidden Item Game

Program CLI untuk menemukan item tersembunyi di grid.

```bash
php hidden_item_game.php
```

### Cara Kerja

1. Grid diisi dengan `#` (rintangan), `.` (jalan), dan `X` (posisi start)
2. Dari `X`, pemain bergerak: **Utara A langkah → Timur B langkah → Selatan C langkah**
3. Program mencari semua titik akhir yang mungkin (mempertimbangkan rintangan)
4. Menampilkan koordinat dan grid visual dengan `$` sebagai penanda lokasi item

### Contoh Output

```

## Struktur Proyek

```
online-store/
├── config/
│   └── database.php          # Konfigurasi koneksi DB
├── public/
│   └── index.php             # Entry point 
├── src/
│   ├── Controllers/
│   │   ├── ProductController.php
│   │   └── OrderController.php
│   ├── Database/
│   │   └── Connection.php    # DB connection
│   ├── Helpers/
│   │   └── Response.php      # JSON response helper
│   └── Models/
│       ├── Product.php       # Model produk + stock locking
│       └── Order.php         # Model order + transaction
├── tests/
│   └── flash_sale_race_condition_test.php
├── database.sql              # Schema 
└── hidden_item_game.php      # Task 2
```

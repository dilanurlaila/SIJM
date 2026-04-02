<?php
class TransaksiModel
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Ambil semua barang yang stoknya masih ada
    public function getAllBarang()
    {
        $query = "SELECT * FROM barang WHERE stok > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil semua layanan jasa
    public function getAllLayanan()
    {
        $query = "SELECT * FROM layanan";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Proses simpan transaksi ke banyak tabel sekaligus (Database Transaction)
    public function buatTransaksi($dataTrx, $items, $kasir_id)
    {
        try {
            $this->conn->beginTransaction(); // Mulai mode aman (jika gagal satu, batal semua)

            // 1. Simpan ke tabel transaksi (Header)
            $kode_trx = 'TRX-' . date('YmdHis');
            $queryTrx = "INSERT INTO transaksi (kode_transaksi, nama_pelanggan, plat_nomor, total_bayar, kasir_id) 
                         VALUES (:kode, :nama, :plat, :total, :kasir)";
            $stmtTrx = $this->conn->prepare($queryTrx);
            $stmtTrx->execute([
                ':kode' => $kode_trx,
                ':nama' => $dataTrx['nama_pelanggan'],
                ':plat' => $dataTrx['plat_nomor'],
                ':total' => $dataTrx['total_bayar'],
                ':kasir' => $kasir_id
            ]);

            $id_transaksi = $this->conn->lastInsertId(); // Ambil ID transaksi yang baru dibuat

            // 2. Loop item yang dibeli (Simpan ke detail_transaksi)
            foreach ($items as $item) {
                // PERBARUAN: Tangani id_item dan nama_item
                // Jika jenisnya manual, id_item kita set 0. Jika bukan, pakai id_item dari database
                $id_item = ($item['jenis'] == 'manual') ? 0 : $item['id_item'];
                $nama_item = isset($item['nama_item']) ? $item['nama_item'] : '';

                // PERBARUAN: Tambahkan kolom `nama_item` ke dalam query
                $queryDetail = "INSERT INTO detail_transaksi (id_transaksi, jenis_item, id_item, nama_item, jumlah, harga_satuan, subtotal) 
                                VALUES (:id_trx, :jenis, :id_item, :nama_item, :qty, :harga, :subtotal)";
                $stmtDetail = $this->conn->prepare($queryDetail);
                $stmtDetail->execute([
                    ':id_trx'    => $id_transaksi,
                    ':jenis'     => $item['jenis'],
                    ':id_item'   => $id_item,
                    ':nama_item' => $nama_item,
                    ':qty'       => $item['qty'],
                    ':harga'     => $item['harga'],
                    ':subtotal'  => $item['subtotal']
                ]);

                // 3. Jika itu Barang, kurangi stok dan catat ke riwayat_stok
                if ($item['jenis'] == 'barang') {
                    // Kurangi stok
                    $queryUpdate = "UPDATE barang SET stok = stok - :qty WHERE id = :id";
                    $stmtUpdate = $this->conn->prepare($queryUpdate);
                    $stmtUpdate->execute([':qty' => $item['qty'], ':id' => $item['id_item']]);

                    // Catat riwayat
                    $queryRiwayat = "INSERT INTO riwayat_stok (id_barang, jenis, jumlah, keterangan) 
                                     VALUES (:id_barang, 'keluar', :qty, :ket)";
                    $stmtRiwayat = $this->conn->prepare($queryRiwayat);
                    $stmtRiwayat->execute([
                        ':id_barang' => $item['id_item'],
                        ':qty'       => $item['qty'],
                        ':ket'       => "Terjual di kasir (Struk: $kode_trx)"
                    ]);
                }
            }

            $this->conn->commit(); // Simpan permanen
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack(); // Batalkan jika ada yang error (misal gagal potong stok)
            // Uncomment baris di bawah ini jika transaksi gagal dan kamu ingin melihat error aslinya (untuk proses debugging):
            die("Gagal menyimpan transaksi: " . $e->getMessage());
            return false;
        }
    }
}

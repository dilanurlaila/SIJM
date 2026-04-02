<?php
require_once 'config/Database.php';
require_once 'models/TransaksiModel.php';

class TransaksiController
{
    private $model;

    public function __construct()
    {
        $db = (new Database())->getConnection();
        $this->model = new TransaksiModel($db);
    }

    // Ambil data untuk ditampilkan di Dropdown form kasir
    public function getFormData()
    {
        return [
            'barang' => $this->model->getAllBarang(),
            'layanan' => $this->model->getAllLayanan()
        ];
    }

    // Proses data saat kasir menekan tombol "Simpan Transaksi"
    public function prosesTransaksi($post_data, $kasir_id)
    {
        $dataTrx = [
            'nama_pelanggan' => $post_data['nama_pelanggan'],
            'plat_nomor' => $post_data['plat_nomor'],
            'total_bayar' => $post_data['total_bayar']
        ];

        $items = [];
        // Menggabungkan array dari input form dinamis ke dalam satu array rapi
        if (isset($post_data['item_id'])) {
            for ($i = 0; $i < count($post_data['item_id']); $i++) {
                $items[] = [
                    'id_item'   => $post_data['item_id'][$i],
                    'jenis'     => $post_data['item_jenis'][$i],
                    // PERBARUAN: Tangkap nama item (sangat penting untuk item tipe 'manual')
                    'nama_item' => isset($post_data['item_nama'][$i]) ? $post_data['item_nama'][$i] : '',
                    'harga'     => $post_data['item_harga'][$i],
                    'qty'       => $post_data['item_qty'][$i],
                    'subtotal'  => $post_data['item_subtotal'][$i]
                ];
            }
        }

        if (count($items) > 0) {
            // Meneruskan data transaksi dan detail item ke Model
            return $this->model->buatTransaksi($dataTrx, $items, $kasir_id);
        }
        return false;
    }
}

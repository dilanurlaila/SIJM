<?php
require_once 'controllers/AuthController.php';
require_once 'controllers/DashboardController.php';
require_once 'controllers/BarangController.php'; // 1. Panggil BarangController

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek apakah user sudah login
$auth = new AuthController();
$auth->checkAuth();

// Inisiasi Controller
$dashboardCtrl = new DashboardController();
$barangCtrl = new BarangController(); // 2. Inisiasi objek BarangController

// ------------------------------------------------------------------
// LOGIKA UNTUK MENANGKAP FORM TAMBAH STOK
// ------------------------------------------------------------------
$pesan_notifikasi = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_stok'])) {

    // 3. Kita langsung lempar seluruh data $_POST ke BarangController
    // Sesuai dengan fungsi prosesTambahStok($post) yang Anda tunjukkan
    $berhasil = $barangCtrl->prosesTambahStok($_POST);

    if ($berhasil) {
        echo "<script>alert('Stok berhasil ditambahkan!'); window.location.href='index.php';</script>";
        exit;
    } else {
        $pesan_notifikasi = "Gagal menambahkan stok. Terjadi kesalahan pada database.";
    }
}
// ------------------------------------------------------------------

// Ambil data dashboard
$data = $dashboardCtrl->getDashboardData();

// Tambahkan operator null coalescing (??) agar aman jika key tidak ada
$pendapatan_bulan_ini = $data['pendapatan'] ?? 0;
$pengeluaran_bulan_ini = $data['pengeluaran'] ?? 0;
$laba_bersih = $data['laba_bersih'] ?? 0;
$stok_menipis = $data['stok_menipis'] ?? [];
$karpet_aktif = $data['karpet_aktif'] ?? [];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Ikhsan Jaya Motor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<!-- <body class="bg-gray-100 font-sans leading-normal tracking-normal"> -->

<body class="bg-gray-100 flex flex-col md:flex-row min-h-screen">

    <div class="flex flex-col md:flex-row min-h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="w-full bg-gray-100 flex-1 p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Dashboard Kasir</h1>

            <!-- Notifikasi Error (Jika ada) -->
            <?php if (!empty($pesan_notifikasi)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p class="font-bold">Perhatian</p>
                    <p><?= $pesan_notifikasi ?></p>
                </div>
            <?php endif; ?>

            <!-- Top Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                    <h3 class="text-gray-500 text-sm font-semibold mb-2">Pemasukan Kotor</h3>
                    <p class="text-2xl font-bold text-gray-800">Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?></p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
                    <h3 class="text-gray-500 text-sm font-semibold mb-2">Pengeluaran Operasional</h3>
                    <p class="text-2xl font-bold text-red-600">Rp <?= number_format($pengeluaran_bulan_ini, 0, ',', '.') ?></p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                    <h3 class="text-gray-500 text-sm font-semibold mb-2">Laba Bersih Bulan Ini</h3>
                    <p class="text-2xl font-bold text-green-600">Rp <?= number_format($laba_bersih, 0, ',', '.') ?></p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-2 gap-6">
                <!-- Tabel Stok Menipis -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Item Perlu Restock</h2>
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="w-full bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Nama Barang</th>
                                <th class="py-3 px-6 text-center">Sisa Stok</th>
                                <th class="py-3 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php if (count($stok_menipis) > 0): ?>
                                <?php foreach ($stok_menipis as $stok): ?>
                                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-3 px-6 text-left font-medium">
                                            <?= htmlspecialchars($stok['nama_barang'] ?? 'Tanpa Nama') ?>
                                        </td>
                                        <td class="py-3 px-6 text-center text-red-500 font-bold">
                                            <?= htmlspecialchars($stok['stok'] ?? 0) ?>
                                        </td>
                                        <td class="py-3 px-6 text-center">
                                            <?php
                                            $id_brg = $stok['id_barang'] ?? $stok['id'] ?? 0;
                                            $nama_brg = htmlspecialchars($stok['nama_barang'] ?? 'Barang', ENT_QUOTES);
                                            ?>
                                            <button onclick="bukaModalStok('<?= $id_brg ?>', '<?= $nama_brg ?>')" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition duration-150" title="Restock">
                                                Tambah Stok
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="py-3 px-6 text-center">Stok aman semua.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Backdrop Modal Stok -->
            <div class="hidden opacity-50 fixed inset-0 z-40 bg-black transition-opacity duration-300" id="modal-stok-backdrop"></div>

            <!-- Modal Stok -->
            <div class="hidden overflow-x-hidden overflow-y-auto fixed inset-0 z-50 outline-none focus:outline-none justify-center items-center transition-all duration-300" id="modal-stok">
                <div class="relative w-full my-6 mx-auto max-w-md">
                    <div class="border-0 rounded-lg shadow-lg relative flex flex-col w-full bg-white outline-none focus:outline-none">
                        <div class="flex items-start justify-between p-5 border-b border-solid border-gray-300 rounded-t">
                            <h3 class="text-xl font-semibold text-gray-800">Restock Barang</h3>
                            <button class="p-1 ml-auto border-0 text-gray-400 hover:text-gray-600 float-right text-3xl leading-none font-semibold outline-none focus:outline-none" onclick="tutupModalStok()">
                                <span class="text-gray-500 h-6 w-6 text-2xl block outline-none focus:outline-none">×</span>
                            </button>
                        </div>

                        <!-- FORM POST -->
                        <form action="" method="POST">
                            <div class="relative p-6 flex-auto">
                                <input type="hidden" name="id_barang" id="stok_id_barang">
                                <p class="mb-4 text-gray-600">Menambah stok untuk: <strong id="stok_nama_barang" class="text-blue-600"></strong></p>

                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Jumlah Ditambahkan</label>
                                    <input type="number" name="jumlah_tambah" min="1" value="1" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-blue-500">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Keterangan / Catatan Kulakan</label>
                                    <input type="text" name="keterangan" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-blue-500" placeholder="Cth: Beli dari Agen Maju Jaya (Nota 123)">
                                </div>
                            </div>
                            <div class="flex items-center justify-end p-6 border-t border-solid border-gray-300 rounded-b">
                                <button class="text-red-500 hover:bg-gray-100 font-bold uppercase px-6 py-2 rounded text-sm outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="button" onclick="tutupModalStok()">Batal</button>
                                <button class="bg-green-600 text-white hover:bg-green-700 font-bold uppercase text-sm px-6 py-3 rounded shadow outline-none focus:outline-none mr-1 mb-1 ease-linear transition-all duration-150" type="submit" name="simpan_stok">Update Stok</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Script JavaScript -->
            <script>
                function bukaModalStok(id, nama) {
                    document.getElementById('stok_id_barang').value = id;
                    document.getElementById('stok_nama_barang').innerText = nama;

                    document.getElementById('modal-stok').classList.remove('hidden');
                    document.getElementById('modal-stok').classList.add('flex');
                    document.getElementById('modal-stok-backdrop').classList.remove('hidden');
                }

                function tutupModalStok() {
                    document.getElementById('modal-stok').classList.add('hidden');
                    document.getElementById('modal-stok').classList.remove('flex');
                    document.getElementById('modal-stok-backdrop').classList.add('hidden');

                    document.getElementById('stok_id_barang').value = '';
                    document.getElementById('stok_nama_barang').innerText = '';
                }
            </script>
</body>

</html>
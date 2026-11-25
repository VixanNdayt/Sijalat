<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Pinjam;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AlatController extends Controller
{
    /**
     * Kategori jenis alat. Harus sama dengan nilai enum pada kolom alats.jenis
     * agar penyimpanan tidak melanggar constraint database.
     */
    public const JENIS_LABEL = Alat::JENIS_LABEL;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('manage', Alat::class);

        $alats = Alat::orderBy('created_at', 'desc')->get();
        $jenisOptions = self::JENIS_LABEL;

        return view('admin.alat', compact('alats', 'jenisOptions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('manage', Alat::class);

        $data = $request->validate([
            'nama'                => 'required|string|max:255',
            'description'         => 'required|string|max:2000',
            'code'                => 'required|string|max:50|unique:alats,code',
            'lokasi'              => 'nullable|string|max:100',
            'kondisi_baik'        => 'required|integer|min:0',
            'kondisi_pengecekan'  => 'required|integer|min:0',
            'kondisi_rusak'       => 'required|integer|min:0',
            'kondisi_maintenance' => 'required|integer|min:0',
            'jenis'               => ['required', 'string', Rule::in(array_keys(self::JENIS_LABEL))],
            'img'                 => 'required|image|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png|max:2048',
        ]);

        $data['img'] = $this->simpanFoto($data['img']);

        // Hitung total Qty secara otomatis
        $data['jumlah_stok'] = (int) $data['kondisi_baik']
            + (int) $data['kondisi_pengecekan']
            + (int) $data['kondisi_rusak']
            + (int) $data['kondisi_maintenance'];

        Alat::create($data);

        return back()->with('success', 'Alat berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Alat $alat)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alat $alat)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('manage', Alat::class);

        $alat = Alat::findOrFail($id);

        $data = $request->validate([
            'nama'                => 'required|string|max:255',
            'description'         => 'required|string|max:2000',
            'kondisi_baik'        => 'required|integer|min:0',
            'code'                => 'required|string|max:50|unique:alats,code,' . $alat->id . ',id',
            'lokasi'              => 'nullable|string|max:100',
            'kondisi_pengecekan'  => 'required|integer|min:0',
            'kondisi_rusak'       => 'required|integer|min:0',
            'kondisi_maintenance' => 'required|integer|min:0',
            'jenis'               => ['required', 'string', Rule::in(array_keys(self::JENIS_LABEL))],
            'img'                 => 'nullable|image|mimes:jpeg,png,jpg|mimetypes:image/jpeg,image/png|max:2048',
        ]);

        $totalFisik = (int) $data['kondisi_baik']
            + (int) $data['kondisi_pengecekan']
            + (int) $data['kondisi_rusak']
            + (int) $data['kondisi_maintenance'];

        // Stok tersedia adalah total unit fisik dikurangi unit yang masih dipinjam,
        // supaya edit data alat tidak mengembalikan stok yang sedang di luar.
        $dipinjam = (int) Pinjam::query()
            ->where('alat_id', $alat->id)
            ->where('status', 'dipinjam')
            ->sum('jumlah_minta');

        $totalQty = max(0, $totalFisik - $dipinjam);

        $alat->fill([
            'nama'                => $data['nama'],
            'description'         => $data['description'],
            'code'                => $data['code'],
            'lokasi'              => $data['lokasi'] ?? null,
            'kondisi_baik'        => $data['kondisi_baik'],
            'kondisi_pengecekan'  => $data['kondisi_pengecekan'],
            'kondisi_rusak'       => $data['kondisi_rusak'],
            'kondisi_maintenance' => $data['kondisi_maintenance'],
            'jenis'               => $data['jenis'],
            'jumlah_stok'         => $totalQty,
        ]);

        // Jika admin mengunggah foto baru, ganti yang lama
        if ($request->hasFile('img')) {
            $this->hapusFoto($alat->img);
            $alat->img = $this->simpanFoto($request->file('img'));
        }

        $alat->save();
        return back()->with('success', 'Data alat berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Gate::authorize('manage', Alat::class);

        try {
            $alat = Alat::findOrFail($id);

            // Jangan pernah hapus alat yang sudah punya riwayat peminjaman.
            // Foreign key lama memakai ON DELETE CASCADE sehingga tanpa cek ini
            // seluruh riwayat siswa bisa ikut terhapus.
            if ($alat->pinjams()->exists()) {
                return back()->withErrors([
                    'hapus' => 'Gagal menghapus! Alat ini masih memiliki riwayat peminjaman yang harus dipertahankan.',
                ]);
            }

            // 1. Cek dan hapus file fisik gambar dari folder public/equipment
            // Pastikan kita memanggil kolom 'img', bukan 'foto'
            $this->hapusFoto($alat->img);

            // 2. Hapus data alat dari database
            $alat->delete();

            return back()->with('success', 'Alat berhasil dihapus secara permanen!');

        } catch (\Illuminate\Database\QueryException $e) {
            // 3. Menangkap error jika barang tidak bisa dihapus karena relasi foreign key (sedang dipinjam)
            return back()->withErrors([
                'hapus' => 'Gagal menghapus! Alat ini tidak bisa dihapus karena masih tercatat di dalam riwayat peminjaman siswa.'
            ]);
        }
    }

    /**
     * Simpan foto alat ke disk publik dengan nama acak dari Laravel.
     * Path disimpan dengan prefiks "storage/" agar helper asset() tetap
     * menghasilkan URL /storage/equipment/... tanpa mengubah view lama.
     */
    private function simpanFoto(UploadedFile $file): string
    {
        return 'storage/' . $file->store('equipment', 'public');
    }

    /**
     * Hapus foto alat, tetapi hanya jika file benar-benar berada di folder equipment.
     */
    private function hapusFoto(?string $path): void
    {
        if (! $path) {
            return;
        }

        $relatif = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($relatif, 'storage/')) {
            $relatif = substr($relatif, strlen('storage/'));
        }

        if (str_starts_with($relatif, 'equipment/') && ! str_contains($relatif, '..')) {
            Storage::disk('public')->delete($relatif);
        }

        $file = realpath(public_path($path));
        $folder = realpath(public_path('equipment'));

        if (! $file || ! $folder || ! is_file($file)) {
            return;
        }

        $file = strtolower(str_replace('\\', '/', $file));
        $folder = rtrim(strtolower(str_replace('\\', '/', $folder)), '/') . '/';

        if (str_starts_with($file, $folder)) {
            @unlink($file);
        }
    }
}

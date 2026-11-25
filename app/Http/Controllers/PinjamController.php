<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\Pinjam;
use App\Support\JadwalSekolah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class PinjamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Tampilkan semua alat, termasuk yang stoknya habis, agar siswa tetap
        // bisa melihat ketersediaan dan status "Habis" pada card.
        $alats = Alat::orderBy('nama')->get();

        // Daftar jenis alat untuk filter, diambil dari data yang benar-benar ada.
        $jenisAlats = $alats
            ->pluck('jenis')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->map(function ($jenis) {
                $label = ucwords(str_replace('_', ' ', $jenis));
                $label = str_replace(['Pc', 'Iot'], ['PC', 'IoT'], $label);

                return [
                    'value' => strtolower($jenis),
                    'label' => $label,
                ];
            });

        // Pulihkan isi keranjang ketika validasi gagal (old input)
        $oldCart = [];
        $oldId = old('alat_id', []);
        $oldQty = old('jumlah_minta', []);

        if (is_array($oldId)) {
            foreach ($oldId as $index => $id) {
                $oldCart[] = [
                    'id'  => (int) $id,
                    'qty' => (int) ($oldQty[$index] ?? 1),
                ];
            }
        }

        // Keranjang dari homepage: format "id:qty,id:qty".
        $cartQuery = $request->query('cart');

        if (count($oldCart) === 0 && is_string($cartQuery) && $cartQuery !== '') {
            $ids = $alats->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach (explode(',', $cartQuery) as $pair) {
                [$id, $qty] = array_pad(explode(':', $pair, 2), 2, 1);
                $id = (int) $id;
                $qty = max(1, (int) $qty);

                if (in_array($id, $ids, true)) {
                    $oldCart[] = ['id' => $id, 'qty' => $qty];
                }
            }
        }

        $gurus = Guru::orderBy('nama', 'asc')->get();
        $mapels = Mapel::orderBy('nama_mapel', 'asc')->get();
        $jadwalSekolah = JadwalSekolah::ringkasan();

        return view('pinjam.create', compact('alats', 'oldCart', 'jenisAlats', 'gurus', 'mapels', 'jadwalSekolah'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->only([
            'alat_id',
            'jumlah_minta',
            'guru_id',
            'mapel_id',
            'dipinjam_saat',
            'ruangan',
            'dikembalikan_saat',
            'deskripsi_minta',
        ]), [
            'alat_id'           => 'required|array|max:50',
            'alat_id.*'         => 'required|integer|distinct|exists:alats,id',
            'jumlah_minta'      => 'required|array|max:50',
            'jumlah_minta.*'    => 'required|integer|min:1|max:100000',
            'guru_id'           => 'required|integer|exists:gurus,id',
            'mapel_id'          => 'required|integer|exists:mapels,id',
            'dipinjam_saat'     => 'required|date',
            'ruangan'           => 'required|string|max:255',
            'dikembalikan_saat' => 'required|date|after_or_equal:dipinjam_saat',
            'deskripsi_minta'   => 'required|string|max:255',
        ], [
            'alat_id.max'       => 'Maksimal 50 jenis alat dalam satu pengajuan.',
            'alat_id.*.distinct' => 'Alat yang sama tidak boleh dipilih dua kali dalam satu pengajuan.',
            'jumlah_minta.max'  => 'Maksimal 50 jenis alat dalam satu pengajuan.',
            'jumlah_minta.*.max' => 'Jumlah alat yang diminta terlalu besar.',
            'guru_id.required'  => 'Pilih guru pengampu terlebih dahulu.',
            'guru_id.exists'    => 'Guru yang dipilih tidak ditemukan. Muat ulang halaman lalu coba lagi.',
            'mapel_id.required' => 'Pilih mata pelajaran terlebih dahulu.',
            'mapel_id.exists'   => 'Mata pelajaran yang dipilih tidak ditemukan. Muat ulang halaman lalu coba lagi.',
        ]);

        $validator->after(function ($validator) use ($request) {
            // --- 1. Jumlah yang diminta tidak boleh melebihi stok tersedia ---
            $diminta = [];
            $jumlahMinta = (array) $request->jumlah_minta;

            foreach ((array) $request->alat_id as $index => $id) {
                if (! array_key_exists($index, $jumlahMinta) || (int) $jumlahMinta[$index] < 1) {
                    $validator->errors()->add(
                        'jumlah_minta',
                        'Jumlah alat tidak valid. Muat ulang halaman lalu coba lagi.'
                    );

                    continue;
                }

                $id = (int) $id;
                $diminta[$id] = ($diminta[$id] ?? 0) + (int) $jumlahMinta[$index];
            }

            $daftarAlat = Alat::whereIn('id', array_keys($diminta))->get()->keyBy('id');

            foreach ($diminta as $id => $jumlah) {
                $alat = $daftarAlat->get($id);

                if (! $alat) {
                    continue;
                }

                if ($jumlah > $alat->jumlah_stok) {
                    $validator->errors()->add(
                        'jumlah_minta',
                        "Jumlah {$alat->nama} melebihi stok tersedia ({$alat->jumlah_stok})."
                    );
                }
            }

            // --- 2. Pengambilan & pengembalian mengikuti jadwal sekolah ---
            foreach (['dipinjam_saat', 'dikembalikan_saat'] as $field) {
                // Lewati field yang sudah gagal di validasi dasar (termasuk input
                // berbentuk array) agar tidak memicu error saat di-parse.
                if ($validator->errors()->has($field)) {
                    continue;
                }

                $nilai = $request->input($field);

                if (! is_string($nilai) || trim($nilai) === '') {
                    continue;
                }

                try {
                    $saat = Carbon::parse($nilai);
                } catch (\Throwable $e) {
                    $validator->errors()->add($field, 'Format tanggal dan jam tidak valid.');

                    continue;
                }

                if (! JadwalSekolah::hariSekolah($saat)) {
                    $validator->errors()->add(
                        $field,
                        ($field === 'dipinjam_saat' ? 'Pengambilan' : 'Pengembalian')
                            . ' hanya dapat dilakukan pada hari Senin sampai Jumat.'
                    );

                    continue;
                }

                $batas = JadwalSekolah::kembaliTerakhir($saat);

                if ($field === 'dipinjam_saat') {
                    $mulai = JadwalSekolah::ambilMulai($saat);

                    if ($saat->lt($mulai)) {
                        $pesan = 'Pengambilan paling cepat pukul '
                            . str_replace(':', '.', config('school.jam_ambil_mulai', '06:30'))
                            . ' pada tanggal yang dipilih.';
                    } elseif ($batas && $saat->gt($batas)) {
                        $pesan = 'Pengambilan paling lambat pukul '
                            . $batas->format('H.i')
                            . ' (jam terakhir pembelajaran hari ' . JadwalSekolah::HARI[$saat->dayOfWeekIso] . ').';
                    } else {
                        continue;
                    }
                } else {
                    if ($batas && $saat->gt($batas)) {
                        $pesan = 'Pengembalian paling lambat pukul '
                            . $batas->format('H.i')
                            . ' (jam terakhir pembelajaran hari ' . JadwalSekolah::HARI[$saat->dayOfWeekIso] . ').';
                    } else {
                        continue;
                    }
                }

                // Hindari pesan yang sama muncul dua kali (waktu ambil & kembali).
                if (! in_array($pesan, $validator->errors()->all(), true)) {
                    $validator->errors()->add($field, $pesan);
                }
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        DB::transaction(function () use ($data) {
            $jumlahMinta = (array) $data['jumlah_minta'];

            foreach ((array) $data['alat_id'] as $index => $id_alat) {
                $pinjam = new Pinjam([
                    'kode_pinjam'       => Pinjam::generateKodePinjam(),
                    'alat_id'           => $id_alat,
                    'guru_id'           => $data['guru_id'],
                    'mapel_id'          => $data['mapel_id'],
                    'jumlah_minta'      => (int) $jumlahMinta[$index],
                    'dipinjam_saat'     => $data['dipinjam_saat'],
                    'ruangan'           => $data['ruangan'],
                    'dikembalikan_saat' => $data['dikembalikan_saat'],
                    'deskripsi_minta'   => $data['deskripsi_minta'],
                ]);

                // Kolom sensitif diisi eksplisit di server, bukan mass assignment.
                $pinjam->forceFill([
                    'qr_ambil_token'   => Pinjam::generateQrToken(),
                    'qr_kembali_token' => Pinjam::generateQrToken(),
                    'user_id'          => Auth::id(),
                    'status'           => 'pending',
                ])->save();
            }
        });


        return redirect()->route('dashboard')->with('success', 'Pengajuan peminjaman berhasil dibuat!');
        
    }

    /**
     * Display the specified resource.
     */
    public function show(Pinjam $pinjam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pinjam $pinjam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pinjam $pinjam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pinjam $pinjam)
    {
        // Pemeriksaan kepemilikan dilakukan di server melalui Policy.
        if (Gate::denies('delete', $pinjam)) {
            return redirect()->route('dashboard')
                ->withErrors(['hapus' => 'Kamu tidak berhak menghapus pengajuan ini.']);
        }

        // Hanya pengajuan yang masih pending yang bisa dibatalkan
        if ($pinjam->status !== 'pending') {
            return redirect()->route('dashboard')
                ->withErrors(['hapus' => 'Pengajuan ini sudah diproses dan tidak bisa dihapus.']);
        }

        $pinjam->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Pengajuan peminjaman berhasil dihapus.');
    }
    
}

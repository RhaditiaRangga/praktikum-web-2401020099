<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/latihan-php', function () {
    $nama = 'Rhaditia Rangga';

    $nilai = [70, 75, 70, 90, 88];

    $hitungRataRata = function (array $data): float {
        $total = 0;

        foreach ($data as $angka) {
            $total += $angka;
        }

        return $total / count($data);
    };

    $rataRata = $hitungRataRata($nilai);

    if ($rataRata >= 75) {
        $status = 'Lulus';
    } else {
        $status = 'Perlu Perbaikan';
    }

    return view('latihan-php', compact(
        'nama',
        'nilai',
        'rataRata',
        'status'
    ));
});

Route::get('/form-mahasiswa', function () {
    return view('form-mahasiswa');
});

Route::post('/form-mahasiswa', function (Request $request) {
    $dataBersih = [
        'nama' => strip_tags(trim((string) $request->input('nama'))),
        'email' => filter_var(
            (string) $request->input('email'),
            FILTER_SANITIZE_EMAIL
        ),
        'usia' => trim((string) $request->input('usia')),
        'nim' => trim((string) $request->input('nim')),
    ];

    $validator = Validator::make($dataBersih, [
        'nama' => ['required', 'min:3', 'max:50'],
        'email' => ['required', 'email'],
        'usia' => ['required', 'integer', 'min:17', 'max:60'],
        'nim' => ['required', 'digits_between:8,12'],
    ], [
        'nama.required' => 'Nama wajib diisi.',
        'nama.min' => 'Nama minimal 3 karakter.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'usia.required' => 'Usia wajib diisi.',
        'usia.integer' => 'Usia harus berupa angka.',
        'usia.min' => 'Usia minimal 17 tahun.',
        'usia.max' => 'Usia maksimal 60 tahun.',
        'nim.required' => 'NIM wajib diisi.',
        'nim.digits_between' => 'NIM harus terdiri dari 8-12 digit.',
    ]);

    if ($validator->fails()) {
        return redirect('/form-mahasiswa')
            ->withErrors($validator)
            ->withInput();
    }

    $data = $validator->validated();
    $data['usia'] = (int) $data['usia'];

    return view('hasil-form', ['data' => $data]);
});


Route::get('/mahasiswa', function (Request $request) {
    $kataKunci = trim((string) $request->query('q', ''));
    $programStudiId = $request->query('program_studi_id', '');

    try {
        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT m.id, m.nim, m.nama, m.email, m.usia,
                       m.program_studi_id, p.nama_prodi
                FROM mahasiswa AS m
                JOIN program_studi AS p
                  ON p.id = m.program_studi_id
                WHERE 1 = 1';

        $parameter = [];

        if ($kataKunci !== '') {
            $sql .= ' AND (m.nim LIKE :kata_kunci_nim
                       OR m.nama LIKE :kata_kunci_nama
                       OR m.email LIKE :kata_kunci_email)';

            $nilaiPencarian = '%' . $kataKunci . '%';

            $parameter['kata_kunci_nim'] = $nilaiPencarian;
            $parameter['kata_kunci_nama'] = $nilaiPencarian;
            $parameter['kata_kunci_email'] = $nilaiPencarian;
        }

        if ($programStudiId !== '') {
            $sql .= ' AND m.program_studi_id = :program_studi_id';
            $parameter['program_studi_id'] = (int) $programStudiId;
        }

        $sql .= ' ORDER BY m.nim';

        $ambilMahasiswa = $pdo->prepare($sql);
        $ambilMahasiswa->execute($parameter);
        $daftarMahasiswa = $ambilMahasiswa->fetchAll(\PDO::FETCH_ASSOC);

        $ambilProdi = $pdo->prepare(
            'SELECT id, nama_prodi
             FROM program_studi
             ORDER BY nama_prodi'
        );

        $ambilProdi->execute();
        $daftarProgramStudi = $ambilProdi->fetchAll(\PDO::FETCH_ASSOC);

        return view('mahasiswa', compact(
            'daftarMahasiswa',
            'daftarProgramStudi',
            'kataKunci',
            'programStudiId'
        ));
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->name('mahasiswa.index');


Route::post('/mahasiswa', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'nim' => ['required', 'string', 'max:20'],
        'nama' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100'],
        'usia' => ['required', 'integer', 'min:15', 'max:100'],
        'program_studi_id' => ['required', 'integer'],
    ], [
        'nim.required' => 'NIM wajib diisi.',
        'nama.required' => 'Nama wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'usia.required' => 'Usia wajib diisi.',
        'usia.integer' => 'Usia harus berupa angka.',
        'program_studi_id.required' => 'Program studi wajib dipilih.',
    ]);

    try {
        $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();

        $cekDuplikat = $pdo->prepare(
            'SELECT COUNT(*) FROM mahasiswa
             WHERE nim = :nim OR email = :email'
        );

        $cekDuplikat->execute([
            'nim' => $data['nim'],
            'email' => $data['email'],
        ]);

        if ((int) $cekDuplikat->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'NIM atau email sudah digunakan.'
            );
        }

        $simpan = $pdo->prepare(
            'INSERT INTO mahasiswa
             (nim, nama, email, usia, program_studi_id)
             VALUES (:nim, :nama, :email, :usia, :program_studi_id)'
        );

        $simpan->execute([
            'nim' => $data['nim'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'usia' => $data['usia'],
            'program_studi_id' => $data['program_studi_id'],
        ]);

        return redirect()->route('mahasiswa.index')->with(
            'berhasil',
            'Data mahasiswa berhasil ditambahkan.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->name('mahasiswa.store');


Route::get('/mahasiswa/{id}/edit', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $ambilMahasiswa = $pdo->prepare(
            'SELECT id, nim, nama, email, usia, program_studi_id
             FROM mahasiswa
             WHERE id = :id'
        );

        $ambilMahasiswa->execute(['id' => $id]);
        $mahasiswa = $ambilMahasiswa->fetch(\PDO::FETCH_ASSOC);

        if (!$mahasiswa) {
            return redirect('/mahasiswa')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        $ambilProdi = $pdo->prepare(
            'SELECT id, nama_prodi
             FROM program_studi
             ORDER BY nama_prodi'
        );

        $ambilProdi->execute();
        $daftarProgramStudi = $ambilProdi->fetchAll(\PDO::FETCH_ASSOC);

        return view('mahasiswa-edit', compact(
            'mahasiswa',
            'daftarProgramStudi'
        ));
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data mahasiswa tidak dapat dibaca.',
            500
        );
    }
})->name('mahasiswa.edit');


Route::put('/mahasiswa/{id}', function (Request $request, $id) {
    $validator = Validator::make($request->all(), [
        'nim' => 'required',
        'nama' => 'required',
        'email' => 'required|email',
        'usia' => 'required|integer|min:1',
        'program_studi_id' => 'required|integer',
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    try {
        $pdo = DB::connection()->getPdo();

        $periksaMahasiswa = $pdo->prepare(
            'SELECT id FROM mahasiswa WHERE id = :id'
        );
        $periksaMahasiswa->execute(['id' => $id]);

        if (!$periksaMahasiswa->fetch(\PDO::FETCH_ASSOC)) {
            return redirect('/mahasiswa')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        $periksaDuplikat = $pdo->prepare(
            'SELECT id FROM mahasiswa
             WHERE (nim = :nim OR email = :email)
             AND id <> :id'
        );

        $periksaDuplikat->execute([
            'nim' => $request->input('nim'),
            'email' => $request->input('email'),
            'id' => $id,
        ]);

        if ($periksaDuplikat->fetch(\PDO::FETCH_ASSOC)) {
            return redirect()->back()
                ->with('gagal', 'NIM atau email sudah digunakan mahasiswa lain.')
                ->withInput();
        }

        $ubahMahasiswa = $pdo->prepare(
            'UPDATE mahasiswa
             SET nim = :nim,
                 nama = :nama,
                 email = :email,
                 usia = :usia,
                 program_studi_id = :program_studi_id
             WHERE id = :id'
        );

        $ubahMahasiswa->execute([
            'nim' => $request->input('nim'),
            'nama' => $request->input('nama'),
            'email' => $request->input('email'),
            'usia' => $request->input('usia'),
            'program_studi_id' => $request->input('program_studi_id'),
            'id' => $id,
        ]);

        return redirect('/mahasiswa')
            ->with('berhasil', 'Data mahasiswa berhasil diperbarui.');
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data mahasiswa gagal diperbarui.',
            500
        );
    }
})->name('mahasiswa.update');


Route::delete('/mahasiswa/{id}', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $hapusMahasiswa = $pdo->prepare(
            'DELETE FROM mahasiswa WHERE id = :id'
        );

        $hapusMahasiswa->execute(['id' => $id]);

        if ($hapusMahasiswa->rowCount() === 0) {
            return redirect('/mahasiswa')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        return redirect('/mahasiswa')
            ->with('berhasil', 'Data mahasiswa berhasil dihapus.');
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data mahasiswa gagal dihapus.',
            500
        );
    }
})->name('mahasiswa.destroy');





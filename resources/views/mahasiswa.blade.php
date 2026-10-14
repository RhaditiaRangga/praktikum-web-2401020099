<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mahasiswa</title>
</head>
<body>
    <h1>Data Mahasiswa</h1>

    @if ($nim !== null)
        <p>Menampilkan data mahasiswa dengan NIM: {{ $nim }}</p>
    @endif

    @if (count($daftarMahasiswa) > 0)
        <table border="1" cellpadding="8" cellspacing="0">
            <thead>
                <tr>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Usia</th>
                    <th>Program Studi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daftarMahasiswa as $mahasiswa)
                    <tr>
                        <td>{{ $mahasiswa['nim'] }}</td>
                        <td>{{ $mahasiswa['nama'] }}</td>
                        <td>{{ $mahasiswa['email'] }}</td>
                        <td>{{ $mahasiswa['usia'] }}</td>
                        <td>{{ $mahasiswa['nama_prodi'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Data mahasiswa tidak ditemukan.</p>
    @endif
</body>
</html>
```
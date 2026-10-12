
USE praktikum_web_2401020099;

-- Memasukkan 2 program studi
INSERT INTO program_studi (nama_prodi) VALUES
('Teknik Informatika'),
('Sistem Informasi');

-- Memasukkan 4 data mahasiswa
INSERT INTO mahasiswa
    (nim, nama, email, usia, program_studi_id)
VALUES
    ('2401020001', 'Dina Maharani', 'dina@example.com', 20, 1),
    ('2401020002', 'Fajar Ramadhan', 'fajar@example.com', 21, 1),
    ('2401020003', 'Nabila Putri', 'nabila@example.com', 19, 2),
    ('2401020004', 'Rizky Pratama', 'rizky@example.com', 22, 2);

-- Mengubah email salah satu mahasiswa
UPDATE mahasiswa
SET email = 'dina.maharani@example.com'
WHERE nim = '2401020001';

-- Menghapus data sementara
INSERT INTO mahasiswa
    (nim, nama, email, usia, program_studi_id)
VALUES
    ('2401020099', 'Data Sementara', 'sementara@example.com', 18, 2);

DELETE FROM mahasiswa
WHERE nim = '2401020099';

-- Menampilkan mahasiswa beserta program studinya
SELECT m.nim, m.nama, m.email, m.usia, p.nama_prodi
FROM mahasiswa AS m
JOIN program_studi AS p
    ON p.id = m.program_studi_id
ORDER BY m.nim;

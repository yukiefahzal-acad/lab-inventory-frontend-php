<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['dummy_users'])) {
    $_SESSION['dummy_users'] = [
        [
            'id' => 1,
            'nama' => 'Rizka Ismailia Putri',
            'nim' => '224111006',
            'email' => 'rizka@gmail.com',
            'password' => md5('password'),
            'role' => 'user'
        ],
        [
            'id' => 2,
            'nama' => 'Admin UNIBI',
            'nim' => '-',
            'email' => 'admin@gmail.com',
            'password' => md5('admin'),
            'role' => 'admin'
        ]
    ];
}

if (!isset($_SESSION['dummy_alat'])) {
    $_SESSION['dummy_alat'] = [
        [
            'id' => 1,
            'kode' => 'UNI-001',
            'nama' => 'Monitor LG 24"',
            'deskripsi' => 'Monitor Full HD untuk kebutuhan praktikum laboratorium.',
            'stok' => 20,
            'denda_hari' => 10000,
            'denda_rusak' => 100000,
            'denda_hilang' => 500000,
            'img' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=150&q=70'
        ],
        [
            'id' => 2,
            'kode' => 'UNI-002',
            'nama' => 'Kabel Tester',
            'deskripsi' => 'Alat pengecekan kabel jaringan.',
            'stok' => 15,
            'denda_hari' => 5000,
            'denda_rusak' => 50000,
            'denda_hilang' => 150000,
            'img' => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?w=150&q=70'
        ],
        [
            'id' => 3,
            'kode' => 'UNI-003',
            'nama' => 'Laptop',
            'deskripsi' => 'Laptop Praktikum untuk pengerjaan tugas lab.',
            'stok' => 10,
            'denda_hari' => 25000,
            'denda_rusak' => 500000,
            'denda_hilang' => 5000000,
            'img' => 'https://images.unsplash.com/photo-1517430816045-df4b7de11d1d?w=150&q=70'
        ],
        [
            'id' => 4,
            'kode' => 'UNI-004',
            'nama' => 'Toolkit Jaringan',
            'deskripsi' => 'Toolkit lengkap networking untuk crimping dan testing.',
            'stok' => 5,
            'denda_hari' => 8000,
            'denda_rusak' => 80000,
            'denda_hilang' => 250000,
            'img' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=150&q=70'
        ]
    ];
}

if (!isset($_SESSION['dummy_peminjaman'])) {
    $_SESSION['dummy_peminjaman'] = [
        [
            'id' => 1,
            'user_id' => 1,
            'user_nama' => 'Rizka Ismailia Putri',
            'user_nim' => '224111006',
            'alat_id' => 1,
            'kode_alat' => 'UNI-001',
            'nama_alat' => 'Monitor LG 24"',
            'tgl_pinjam' => '2026-06-01',
            'tgl_kembali' => '2026-06-07',
            'jumlah' => 1,
            'denda' => 250000,
            'status' => 'Belum Lunas',
            'terlambat' => 3
        ],
        [
            'id' => 2,
            'user_id' => 1,
            'user_nama' => 'Rizka Ismailia Putri',
            'user_nim' => '224111006',
            'alat_id' => 2,
            'kode_alat' => 'UNI-002',
            'nama_alat' => 'Kabel Tester',
            'tgl_pinjam' => '2026-05-31',
            'tgl_kembali' => '2026-06-05',
            'jumlah' => 1,
            'denda' => 0,
            'status' => 'Selesai',
            'terlambat' => 0
        ],
        [
            'id' => 3,
            'user_id' => 1,
            'user_nama' => 'Rizka Ismailia Putri',
            'user_nim' => '224111006',
            'alat_id' => 4,
            'kode_alat' => 'UNI-004',
            'nama_alat' => 'Toolkit Jaringan',
            'tgl_pinjam' => '2026-05-25',
            'tgl_kembali' => '2026-06-01',
            'jumlah' => 1,
            'denda' => 0,
            'status' => 'Selesai',
            'terlambat' => 0
        ]
    ];
}

if (!isset($_SESSION['dummy_verifikasi'])) {
    $_SESSION['dummy_verifikasi'] = [
        [
            'id' => 1,
            'user_nama' => 'Rizka Ismailia Putri',
            'user_nim' => '224111006',
            'alat_nama' => 'Monitor LG 24"',
            'img' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=150&q=70',
            'tgl_pinjam' => '2026-06-05',
            'jumlah' => 1
        ]
    ];
}

function get_dummy_user($email, $password_md5) {
    $email = trim(strtolower($email));
    
    if (substr($email, 0, 6) === 'admin@') {
        return [
            'id' => 999,
            'nama' => 'Admin UNIBI',
            'nim' => '-',
            'email' => $email,
            'password' => $password_md5,
            'role' => 'admin'
        ];
    } else {
        foreach ($_SESSION['dummy_users'] as $user) {
            if (strtolower($user['email']) === $email) {
                return $user;
            }
        }
        
        $parts = explode('@', $email);
        $name = ucwords(str_replace(['.', '_', '-'], ' ', $parts[0]));
        return [
            'id' => rand(1000, 9999),
            'nama' => $name,
            'nim' => '224111' . rand(100, 999),
            'email' => $email,
            'password' => $password_md5,
            'role' => 'user'
        ];
    }
}

function register_dummy_user($nama, $nim, $email, $password_md5) {
    $new_id = count($_SESSION['dummy_users']) + 1;
    $_SESSION['dummy_users'][] = [
        'id' => $new_id,
        'nama' => $nama,
        'nim' => $nim,
        'email' => $email,
        'password' => $password_md5,
        'role' => 'user'
    ];
    return true;
}
?>
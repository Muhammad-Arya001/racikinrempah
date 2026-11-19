-- Jalankan jika database lama sudah terlanjur dibuat.
ALTER TABLE pesanan
MODIFY status ENUM(
    'menunggu',
    'dibayar',
    'diproses',
    'disiapkan',
    'dikirim',
    'dalam_pengantaran',
    'selesai',
    'batal'
) NOT NULL DEFAULT 'menunggu';

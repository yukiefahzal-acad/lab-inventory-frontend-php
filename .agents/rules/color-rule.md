---
trigger: always_on
---

# Aturan Pemilihan Warna (Color Guidelines)

Semua pemilihan warna di dalam proyek ini (termasuk pada CSS *inline*, kode *JavaScript*, *plugin* pihak ketiga seperti *SweetAlert*, dsb) **WAJIB** menggunakan referensi warna yang sudah didefinisikan secara global pada file `assets/css/style.css`. 

## 🚫 ATURAN UTAMA
Dilarang keras menggunakan kode warna kustom sembarangan (seperti `#0ea5e9`, `#cc0000`, `blue`, `red`, dsb) yang tidak tercantum di `style.css`.

## 🎨 Palet Warna Resmi
Gunakan nilai Hex berikut yang secara langsung merujuk pada variabel CSS utama kita:

1. **Warna Utama (Primary)**
   - **`#7dd3fc`** (Primary Default) - Digunakan untuk tombol utama (seperti *Submit*, *OK* pada *Alert*, dan tombol *Detail*).
   - **`#60a5fa`** (Primary Dark) - Digunakan untuk efek *hover* pada tombol utama.
   - **`#f0f9ff`** (Primary Light) - Digunakan untuk *background* elemen kartu atau area sorotan.

2. **Warna Teks & Monokrom (Grayscale)**
   - **`#0f172a`** (Text Utama / Slate) - Untuk *heading*, judul, dan teks tebal.
   - **`#64748b`** (Text Muted) - Untuk sub-teks, deskripsi, placeholder, atau warna tombol Batal (*Cancel*).
   - **`#ffffff`** (White) - *Background* *card*, teks di atas warna *primary*, dll.
   - **`#e2e8f0`** (Border) - Untuk garis tepi, *divider*, atau pembatas.

3. **Warna Peringatan & Status**
   - **`#ef4444`** (Danger / Red) - Digunakan untuk tombol *Hapus*, tanda silang, atau denda.
   - **`#ff9800`** (Warning / Orange) - Digunakan untuk status terlambat, ditunda.
   - **`#10b981`** (Success / Green) - Digunakan untuk status berhasil, dikembalikan, atau lunas.

### Contoh Implementasi di JavaScript (SweetAlert2)
Jika mengkonfigurasi *plugin* pihak ketiga yang meminta kode warna Hex:
```javascript
Swal.fire({
    title: 'Apakah Anda yakin?',
    icon: 'warning',
    confirmButtonColor: '#ef4444', // Gunakan Red (Danger)
    cancelButtonColor: '#7dd3fc',  // Gunakan Primary Default
})
```

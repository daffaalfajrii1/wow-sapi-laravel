# PROMPT TWEAK MENU BCS — WOW SAPI

Saya ingin **menyempurnakan fitur BCS pada WOW SAPI yang sudah ada**, bukan membuat ulang modul atau desain aplikasi.

Saat ini halaman/tab BCS sudah tersedia dan sudah memiliki form penyimpanan BCS. Pertahankan struktur aplikasi, route, layout, sidebar, header, dan desain WOW SAPI yang sekarang.

Fokus hanya pada **menu BCS sampai menghasilkan rekomendasi kondisi tubuh sapi**.

## TUJUAN

Ubah halaman BCS yang sekarang menjadi alur:

```text
Pilih / buka sapi
      ↓
Lihat bobot terakhir
      ↓
Penilaian Kondisi Tubuh (BCS)
      ↓
Peternak memilih nilai BCS
      ↓
Sistem menentukan kategori otomatis
      ↓
Sistem menghasilkan rekomendasi
      ↓
Simpan
      ↓
Tampil dalam riwayat BCS sapi
```

BCS saat ini **bukan AI**.

Jangan membuat model AI BCS palsu.

BCS diisi oleh peternak/petugas berdasarkan pengamatan kondisi fisik sapi.

---

## 1. UBAH JUDUL FORM

Ubah:

```text
BCS manual (1–5)
```

menjadi:

```text
Penilaian Kondisi Tubuh (BCS)
```

Tambahkan deskripsi:

```text
Nilai kondisi tubuh sapi berdasarkan pengamatan fisik.
Perhatikan tulang rusuk, tulang belakang, pinggul,
pangkal ekor, dan lapisan lemak tubuh.
```

Hapus tulisan:

```text
AI BCS belum diaktifkan.
```

Tidak perlu menonjolkan bahwa fitur ini bukan AI.

---

## 2. TAMPILKAN INFORMASI SAPI

Di bagian atas card BCS tampilkan informasi ringkas:

```text
SAPI-0002
Nama: ...
Ras: Bali
Jenis Kelamin: Betina
Umur: 1 tahun 6 bulan
Tujuan Pemeliharaan: Penggemukan
```

Kemudian tampilkan:

```text
Bobot Terakhir
216,7 kg

Sumber
Estimasi AI

Tanggal
21 September 2026
```

Jika bobot belum tersedia:

```text
Belum ada data bobot untuk sapi ini.
```

Berikan link:

```text
Lakukan Estimasi Bobot
```

yang menuju tab Pemeriksaan AI sapi tersebut.

BCS **tidak boleh dihitung otomatis hanya berdasarkan bobot**.

Bobot hanya menjadi informasi pendukung rekomendasi.

---

## 3. JANGAN GUNAKAN INPUT ANGKA KOSONG

Form BCS sekarang terlihat seperti input biasa.

Ubah menjadi pilihan visual yang mudah digunakan.

Gunakan pilihan:

```text
1.0
1.5
2.0
2.5
3.0
3.5
4.0
4.5
5.0
```

Gunakan salah satu UI yang paling cocok dengan desain existing:

- segmented buttons;
- radio card;
- atau slider step `0.5`.

Saya lebih menyukai **slider + nilai aktif besar**.

Contoh:

```text
Kondisi Tubuh

1 ──────────●────────── 5

          2.5

        IDEAL
```

Ketika user mengubah slider, kategori langsung berubah tanpa reload.

Namun kategori **tetap harus dihitung ulang server-side** saat disimpan.

---

## 4. KATEGORI BCS

Gunakan aturan:

```text
1.0 – 1.9  = Sangat Kurus
2.0 – 2.4  = Kurus
2.5 – 3.5  = Ideal
3.6 – 4.0  = Gemuk
4.1 – 5.0  = Sangat Gemuk
```

Karena pilihan menggunakan step 0.5, contoh:

```text
1.0 → Sangat Kurus
1.5 → Sangat Kurus

2.0 → Kurus

2.5 → Ideal
3.0 → Ideal
3.5 → Ideal

4.0 → Gemuk

4.5 → Sangat Gemuk
5.0 → Sangat Gemuk
```

---

## 5. BUAT PANDUAN PENILAIAN

Di samping atau bawah selector BCS tampilkan card:

```text
Panduan Penilaian
```

Isinya:

### BCS 1 — Sangat Kurus

```text
Tulang rusuk, tulang belakang, dan pinggul
sangat jelas terlihat. Lapisan lemak sangat sedikit.
```

### BCS 2 — Kurus

```text
Tulang rusuk dan pinggul masih terlihat jelas,
namun tubuh mulai memiliki sedikit jaringan penutup.
```

### BCS 3 — Ideal

```text
Tulang tidak terlalu menonjol dan kondisi tubuh
terlihat proporsional.
```

### BCS 4 — Gemuk

```text
Tulang sulit terlihat dan lapisan lemak
mulai terlihat jelas.
```

### BCS 5 — Sangat Gemuk

```text
Tubuh memiliki timbunan lemak yang tinggi
dan struktur tulang hampir tidak terlihat.
```

Gunakan accordion atau card kecil agar tidak memenuhi halaman.

---

## 6. INPUT TAMBAHAN

Pertahankan:

```text
Catatan
Foto kondisi tubuh
```

Tambahkan:

```text
Tanggal Penilaian
```

Default hari ini tetapi dapat diubah.

Tambahkan informasi otomatis:

```text
Dinilai oleh
{{ auth()->user()->name }}
```

Tidak perlu user mengetik nama penilai.

---

## 7. TOMBOL

Gunakan:

```text
Simpan Penilaian BCS
```

Setelah berhasil simpan:

```text
Penilaian kondisi tubuh berhasil disimpan.
```

---

## 8. HASIL SETELAH SIMPAN

Setelah BCS berhasil disimpan, tampilkan hasil dalam card yang bagus:

```text
Hasil Penilaian Kondisi Tubuh
```

Contoh:

```text
BCS
2.0 / 5

Kategori
Kurus

Bobot Terakhir
216,7 kg

Tanggal Penilaian
21 September 2026
```

Di bawahnya tampilkan:

```text
Rekomendasi
```

---

## 9. BUAT SERVICE REKOMENDASI

Audit dulu apakah service serupa sudah ada.

Jika belum, buat:

```text
app/Services/CattleConditionRecommendationService.php
```

Jangan menaruh seluruh rule rekomendasi di controller.

Method:

```php
public function generate(
    Cattle $cattle,
    BcsRecord $bcs,
    ?WeightRecord $latestWeight = null
): array
```

Output:

```php
[
    'summary' => '...',
    'items' => [
        '...',
        '...',
    ],
]
```

---

## 10. REKOMENDASI BCS SANGAT KURUS

Untuk `BCS < 2.0`:

```text
Kondisi tubuh sapi berada jauh di bawah kisaran ideal.
```

Rekomendasi:

```text
• Evaluasi kualitas dan kecukupan pakan.
• Pastikan kebutuhan nutrisi ternak terpenuhi.
• Pantau perubahan bobot secara lebih rutin.
• Periksa kondisi kesehatan apabila berat badan tidak meningkat atau kondisi tubuh terus menurun.
• Lakukan penilaian kondisi tubuh kembali dalam 14–30 hari.
```

---

## 11. REKOMENDASI BCS KURUS

Untuk `BCS >= 2.0 dan < 2.5`:

```text
Kondisi tubuh sapi masih berada di bawah kisaran ideal.
```

Rekomendasi:

```text
• Evaluasi kualitas dan jumlah pakan.
• Perhatikan kecukupan energi dan protein.
• Pantau perkembangan bobot sapi.
• Perhatikan kemungkinan gangguan kesehatan bila bobot sulit meningkat.
• Lakukan evaluasi BCS kembali dalam 14–30 hari.
```

---

## 12. REKOMENDASI BCS IDEAL

Untuk `BCS >= 2.5 dan <= 3.5`:

```text
Kondisi tubuh sapi berada pada kisaran ideal.
```

Rekomendasi:

```text
• Pertahankan pola pemberian pakan.
• Pantau bobot secara berkala.
• Pertahankan kebersihan dan kesehatan ternak.
• Lakukan penilaian kondisi tubuh secara rutin.
```

---

## 13. REKOMENDASI BCS GEMUK

Untuk `BCS > 3.5 dan <= 4.0`:

```text
Kondisi tubuh sapi berada di atas kisaran ideal.
```

Rekomendasi:

```text
• Evaluasi pola pemberian pakan.
• Pantau peningkatan bobot secara berkala.
• Sesuaikan manajemen pakan dengan tujuan pemeliharaan.
• Lakukan penilaian BCS kembali secara rutin.
```

---

## 14. REKOMENDASI BCS SANGAT GEMUK

Untuk `BCS > 4.0`:

```text
Kondisi tubuh sapi berada cukup jauh di atas kisaran ideal.
```

Rekomendasi:

```text
• Evaluasi kembali manajemen pemberian pakan.
• Pantau perkembangan bobot.
• Hindari peningkatan kondisi tubuh yang berlebihan.
• Sesuaikan pemeliharaan dengan tujuan ternak.
• Lakukan evaluasi kondisi tubuh secara berkala.
```

---

## 15. SESUAIKAN REKOMENDASI DENGAN PROFIL SAPI

Jika data tersedia, Recommendation Service harus mempertimbangkan:

```text
BCS
Bobot terakhir
Perubahan bobot
Umur
Ras
Jenis kelamin
Tujuan pemeliharaan
Status reproduksi
```

Contoh jika `BCS = 2.0` dan tujuan `Penggemukan`:

```text
Pantau peningkatan bobot secara bertahap sesuai tujuan penggemukan.
```

Jika sapi betina bunting dan BCS rendah:

```text
Perhatikan kondisi tubuh selama masa kebuntingan dan lakukan konsultasi dengan petugas jika kondisi terus menurun.
```

Jangan memberikan dosis pakan spesifik seperti:

```text
Berikan konsentrat 4 kg setiap hari.
```

Karena aplikasi belum menghitung formulasi ransum secara lengkap.

---

## 16. HUBUNGKAN BOBOT DENGAN BCS

Ambil weight record terbaru dan record sebelumnya jika tersedia.

Contoh:

```text
Bobot Sekarang
216,7 kg

Bobot Sebelumnya
201,2 kg

Perubahan
+15,5 kg
```

Gabungkan dengan BCS:

```text
Bobot naik 15,5 kg
BCS meningkat dari 2.0 menjadi 2.5

Kondisi tubuh menunjukkan perkembangan yang lebih baik.
```

Jangan membuat klaim medis.

---

## 17. HISTORY BCS

Pada bagian bawah tab BCS tampilkan tabel:

```text
Tanggal | Bobot | BCS | Kategori | Penilai | Aksi
```

Contoh:

```text
21 Sep 2026 | 216,7 kg | 2.5 | Ideal | Daffa Alfajri | Detail
05 Sep 2026 | 201,2 kg | 2.0 | Kurus | Daffa Alfajri | Detail
```

Jika belum ada:

```text
Belum ada penilaian kondisi tubuh.
```

Beri CTA:

```text
Buat Penilaian Pertama
```

---

## 18. SIMPAN SNAPSHOT BOBOT

Agar histori BCS tidak berubah ketika ada bobot baru, simpan:

```text
weight_kg_snapshot nullable
```

pada `bcs_records`.

Saat membuat BCS:

```text
weight_kg_snapshot = latest weight saat itu
```

---

## 19. SIMPAN REKOMENDASI

Jika belum tersedia, tambahkan pada `bcs_records`:

```text
recommendation_summary text nullable
recommendations json nullable
```

Simpan hasil Recommendation Service pada saat BCS dibuat.

Jangan hanya generate ulang setiap kali halaman dibuka.

---

## 20. TAMPILAN DETAIL HISTORY

Jika user klik `Detail`, tampilkan:

```text
Penilaian Kondisi Tubuh

SAPI-0002
21 September 2026

Bobot Saat Pemeriksaan
216,7 kg

BCS
2.5 / 5

Kategori
Ideal

Catatan
...

Rekomendasi
Kondisi tubuh sapi berada pada kisaran ideal.

• Pertahankan pola pemberian pakan.
• Pantau bobot secara berkala.
• Pertahankan kesehatan ternak.
• Lakukan evaluasi kondisi tubuh secara rutin.
```

Jika foto ada, tampilkan foto tersebut.

---

## 21. HUBUNGKAN DENGAN AI BOBOT

Pada tab `Pemeriksaan AI`, setelah estimasi bobot berhasil, tambahkan tombol:

```text
Lanjutkan Penilaian BCS
```

Button menuju tab BCS pada sapi yang sama.

Jangan mengubah endpoint FastAPI.

---

## 22. TAMPILKAN DI RINGKASAN SAPI

Pada tab Ringkasan tampilkan:

```text
Bobot Terakhir
216,7 kg

BCS Terakhir
2.5 — Ideal

Kesehatan
Tidak Terindikasi Lumpy Skin
```

Jika belum ada BCS:

```text
BCS
Belum Dinilai

[ Nilai Sekarang ]
```

---

## 23. API FLUTTER

Pertahankan endpoint:

```text
GET  /api/v1/cattle/{cattle}/bcs
POST /api/v1/cattle/{cattle}/bcs
```

POST contoh:

```json
{
  "score": 2.5,
  "assessed_at": "2026-09-21",
  "notes": "Kondisi tubuh membaik."
}
```

Backend otomatis mencari latest weight dan menghasilkan response:

```json
{
  "success": true,
  "message": "Penilaian kondisi tubuh berhasil disimpan.",
  "data": {
    "score": 2.5,
    "category": "Ideal",
    "weight_kg": 216.7,
    "recommendation": {
      "summary": "Kondisi tubuh sapi berada pada kisaran ideal.",
      "items": [
        "Pertahankan pola pemberian pakan.",
        "Pantau bobot secara berkala.",
        "Lakukan penilaian kondisi tubuh secara rutin."
      ]
    }
  }
}
```

Jika foto dikirim, gunakan multipart.

---

## 24. VALIDASI

Score:

```text
required
numeric
min:1
max:5
```

Hanya izinkan interval 0.5:

```text
1
1.5
2
2.5
3
3.5
4
4.5
5
```

Jangan menerima kategori dari client.

Kategori dihitung Laravel.

---

## 25. AUTHORIZATION

Tetap gunakan Policy existing.

Peternak hanya boleh melihat/membuat/mengubah BCS sapi miliknya sendiri.

Admin boleh melihat seluruh BCS.

Pastikan tidak terjadi IDOR dengan mengganti ID sapi di URL.

---

## 26. DESAIN

JANGAN redesign seluruh halaman.

Gunakan desain WOW SAPI yang sudah ada.

Rapikan khusus card BCS.

Target desain:

```text
┌─────────────────────────────────┐
│ Penilaian Kondisi Tubuh         │
│                                 │
│ Bobot terakhir       216,7 kg   │
│                                 │
│          BCS                    │
│                                 │
│ 1 ───────●────────────── 5      │
│          2.5                    │
│                                 │
│          IDEAL                  │
│                                 │
│ Panduan Penilaian               │
│ ...                             │
│                                 │
│ Catatan                         │
│ Foto                            │
│                                 │
│ [ Simpan Penilaian BCS ]        │
└─────────────────────────────────┘
```

Setelah disimpan:

```text
┌─────────────────────────────────┐
│ Hasil Penilaian                 │
│                                 │
│ BCS               2.5 / 5       │
│ Kondisi           Ideal         │
│ Bobot             216,7 kg      │
│                                 │
│ Rekomendasi                     │
│ Kondisi tubuh berada pada       │
│ kisaran ideal.                  │
│                                 │
│ ✓ Pertahankan pola pakan        │
│ ✓ Pantau bobot                  │
│ ✓ Evaluasi BCS berkala          │
└─────────────────────────────────┘
```

Gunakan icon SVG/Lucide yang sudah dipakai project.

Jangan gunakan emoji sebagai icon utama.

---

## 27. TEST WAJIB

Tambahkan test:

```text
BCS 1.0 → Sangat Kurus
BCS 2.0 → Kurus
BCS 2.5 → Ideal
BCS 3.5 → Ideal
BCS 4.0 → Gemuk
BCS 4.5 → Sangat Gemuk
BCS 5.0 → Sangat Gemuk
```

Test juga:

- score 0 ditolak
- score 6 ditolak
- interval selain 0.5 ditolak
- peternak tidak dapat menambah BCS untuk sapi orang lain
- weight snapshot tersimpan
- recommendation tersimpan
- rekomendasi tetap tampil setelah ada bobot baru
- history urut terbaru
- API response benar

---

## 28. JANGAN UBAH MODUL LAIN

Jangan merusak atau refactor besar:

- AI Estimasi Bobot
- AI Lumpy Skin
- Analisis Gabungan
- Data Sapi
- Kesehatan
- Vaksinasi
- Pakan
- Reproduksi
- Kematian
- Laporan

Hanya lakukan perubahan yang diperlukan untuk integrasi BCS.

---

# HASIL YANG SAYA INGINKAN

Kerjakan sampai benar-benar berjalan:

```text
Bobot terakhir
      ↓
Input BCS terpandu
      ↓
Kategori otomatis
      ↓
Rekomendasi otomatis
      ↓
Simpan
      ↓
History BCS
      ↓
Tampil di detail/ringkasan sapi
```

Sebelum coding, audit implementasi BCS existing dan sebutkan file yang akan diubah.

Setelah implementasi:

1. jalankan migration,
2. jalankan test,
3. jalankan build frontend jika diperlukan,
4. laporkan file yang berubah,
5. laporkan hasil test,
6. jangan berhenti pada pseudocode.

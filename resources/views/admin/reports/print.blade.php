<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan TB Care - {{ strtoupper($type) }} ({{ date('d-m-Y') }})</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            font-size: 13px;
        }
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .kop-surat h4, .kop-surat h5, .kop-surat p {
            margin: 0;
            text-align: center;
        }
        .kop-surat h4 { font-size: 18px; font-weight: bold; letter-spacing: 1px; }
        .kop-surat h5 { font-size: 16px; font-weight: bold; margin-top: 2px; }
        .kop-surat p { font-size: 12px; margin-top: 4px; font-style: italic; }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
            padding: 6px 8px;
        }
        .table-bordered thead th {
            background-color: #f2f2f2 !important;
            color: #000;
            text-align: center;
            font-weight: bold;
        }
        .no-print-bar {
            background: #343a40;
            padding: 10px 20px;
            color: white;
            margin-bottom: 25px;
        }
        @media print {
            .no-print-bar { display: none !important; }
            @page { margin: 15mm; size: A4 portrait; }
        }
    </style>
</head>
<body>

<div class="no-print-bar d-flex justify-content-between align-items-center">
    <div><strong>Pratinjau Cetak Laporan Resmi TB Care</strong> (Gunakan opsi Simpan PDF atau Print)</div>
    <div>
        <button onclick="window.print()" class="btn btn-sm btn-primary font-weight-bold">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" class="btn btn-sm btn-light ml-2">Tutup Halaman</button>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- Kop Surat Resmi -->
    <div class="kop-surat">
        <h4>PEMERINTAH DAERAH KABUPATEN TASIKMALAYA</h4>
        <h5>DINAS KESEHATAN - SISTEM INFORMASI TB CARE</h5>
        <p>Jl. Perkantoran Sukapura No. 12, Tasikmalaya, Jawa Barat &bull; Email: dinkes@tasikmalayakab.go.id</p>
    </div>

    <!-- Judul Dokumen -->
    <div class="text-center mb-4">
        <h5 style="text-decoration: underline; font-weight: bold; margin-bottom: 4px;">
            @if($type == 'screening') LAPORAN REKAPITULASI SKRINING TB MANDIRI
            @elseif($type == 'patient') LAPORAN REGISTER PASIEN TUBERKULOSIS
            @elseif($type == 'regional') LAPORAN DISTRIBUSI WILAYAH FASKES PEMBINA TB
            @else LAPORAN DATA PENGGUNA TERDAFTAR SISTEM
            @endif
        </h5>
        <div style="font-size: 13px;">
            Periode: <strong>{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}</strong> s.d <strong>{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</strong>
            @if($pkmSelected) &bull; Wilayah Faskes: <strong>{{ $pkmSelected->name }}</strong> @endif
            @if($riskLevel) &bull; Kategori Risiko: <strong>{{ strtoupper($riskLevel) }}</strong> @endif
        </div>
    </div>

    <!-- Tabel Konten -->
    @if($type == 'screening')
        <table class="table table-bordered mb-4">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Kode Skrining</th>
                    <th>Nama Peserta</th>
                    <th>JK / Usia</th>
                    <th>Puskesmas / Wilayah</th>
                    <th>Skor</th>
                    <th>Kategori Risiko</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $item->code }}</td>
                    <td>{{ $item->name }}</td>
                    <td class="text-center">{{ $item->gender == 'male' ? 'L' : 'P' }} / {{ $item->age }} Th</td>
                    <td>{{ $item->puskesmas->name ?? '-' }} ({{ $item->subdistrict->name ?? '-' }})</td>
                    <td class="text-center">{{ $item->total_score }}</td>
                    <td class="text-center font-weight-bold">{{ strtoupper($item->risk_level) }}</td>
                    <td class="text-center">{{ strtoupper($item->status) }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($item->screening_date)->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-3">Tidak ada data skrining tercatat pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif($type == 'patient')
        <table class="table table-bordered mb-4">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>No Reg TB</th>
                    <th>Nama Pasien</th>
                    <th>NIK</th>
                    <th>JK/Usia</th>
                    <th>Puskesmas</th>
                    <th>Desa / Kelurahan</th>
                    <th>Status Pengobatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $pat)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $pat->patient_number ?? ('TB-' . $pat->id) }}</td>
                    <td>{{ $pat->user->name ?? $pat->name ?? 'Pasien' }}</td>
                    <td class="text-center">{{ $pat->nik ?? ($pat->user->nik ?? '-') }}</td>
                    <td class="text-center">{{ ($pat->gender ?? ($pat->user->gender ?? '')) == 'male' ? 'L' : 'P' }} / {{ $pat->age ?? '-' }} Th</td>
                    <td>{{ $pat->puskesmas->name ?? '-' }}</td>
                    <td>{{ $pat->village->name ?? ($pat->subdistrict->name ?? '-') }}</td>
                    <td class="text-center">{{ $pat->activeTreatment->treatment_status ?? 'Dalam Pemantauan' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-3">Tidak ada data pasien tercatat pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif($type == 'regional')
        <table class="table table-bordered mb-4">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Puskesmas</th>
                    <th>Kecamatan</th>
                    <th>Kabupaten/Kota</th>
                    <th>Total Pasien</th>
                    <th>Total Skrining</th>
                    <th>Risiko Tinggi</th>
                    <th>Risiko Sedang</th>
                    <th>Risiko Rendah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($regionalSummary as $idx => $pkm)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td><strong>{{ $pkm->name }}</strong></td>
                    <td>{{ $pkm->subdistrict->name ?? '-' }}</td>
                    <td>{{ $pkm->subdistrict->district->name ?? '-' }}</td>
                    <td class="text-center">{{ $pkm->patients_count }}</td>
                    <td class="text-center">{{ $pkm->screenings_count }}</td>
                    <td class="text-center font-weight-bold">{{ $pkm->high_risk_count }}</td>
                    <td class="text-center font-weight-bold">{{ $pkm->medium_risk_count }}</td>
                    <td class="text-center font-weight-bold">{{ $pkm->low_risk_count }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-3">Tidak ada data wilayah faskes tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Tanda Tangan Resmi -->
    <div class="row mt-5" style="page-break-inside: avoid;">
        <div class="col-8"></div>
        <div class="col-4 text-center">
            <p class="mb-1">Tasikmalaya, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
            <p class="font-weight-bold mb-5">Administrator Sistem TB Care / PJ Program TB</p>
            <br><br>
            <p class="font-weight-bold mb-0" style="text-decoration: underline;">( .................................................. )</p>
            <p class="small text-muted mb-0">NIP. ..................................................</p>
        </div>
    </div>
</div>

<script>
    // Trigger print dialog on load
    window.onload = function() {
        // window.print();
    };
</script>
</body>
</html>

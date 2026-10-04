@extends('admin.layouts.app')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
    <!-- Clinical Priority Alert Strip -->
    @if($perluTindakLanjut > 0)
        <div class="alert alert-danger alert-dismissible shadow-sm fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                <div>
                    <h5 class="alert-heading mb-1 font-weight-bold">Perhatian: Terdapat {{ $perluTindakLanjut }} Kasus Skrining Perlu Tindak Lanjut!</h5>
                    <span>Responden teridentifikasi memiliki gejala klinis TB batuk >= 2 minggu atau batuk darah yang membutuhkan pemeriksaan dahak TCM di Puskesmas.</span>
                </div>
                <div class="ml-auto">
                    <a href="{{ route('admin.screenings.action_needed') }}" class="btn btn-sm btn-light font-weight-bold text-danger text-nowrap">
                        <i class="fas fa-arrow-right mr-1"></i> Tindak Lanjuti Sekarang
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- 8 Main Metric Info Boxes -->
    <div class="row">
        <!-- 1. Total Pengguna -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Total Pengguna</span>
                    <span class="info-box-number text-xl font-weight-bold text-dark">{{ number_format($totalPengguna) }}</span>
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-primary font-weight-bold">Lihat Semua Pengguna <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 2. Total Pasien -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-user-injured"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Total Pasien TB</span>
                    <span class="info-box-number text-xl font-weight-bold text-dark">{{ number_format($totalPasien) }}</span>
                    <a href="{{ route('admin.patients.index') }}" class="text-xs text-danger font-weight-bold">Lihat Rekam Pasien <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 3. Total Skrining -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-stethoscope"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Total Skrining</span>
                    <span class="info-box-number text-xl font-weight-bold text-dark">{{ number_format($totalSkrining) }}</span>
                    <a href="{{ route('admin.screenings.index') }}" class="text-xs text-info font-weight-bold">Lihat Riwayat Skrining <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 4. Risiko Rendah -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shield-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Risiko Rendah</span>
                    <span class="info-box-number text-xl font-weight-bold text-success">{{ number_format($risikoRendah) }}</span>
                    <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Rendah']) }}" class="text-xs text-success font-weight-bold">Filter Rendah <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 5. Risiko Sedang -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-warning elevation-1 text-white"><i class="fas fa-exclamation"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Risiko Sedang</span>
                    <span class="info-box-number text-xl font-weight-bold text-warning">{{ number_format($risikoSedang) }}</span>
                    <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Sedang']) }}" class="text-xs text-warning font-weight-bold">Filter Sedang <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 6. Risiko Tinggi -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm border-danger">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-biohazard"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Risiko Tinggi</span>
                    <span class="info-box-number text-xl font-weight-bold text-danger">{{ number_format($risikoTinggi) }}</span>
                    <a href="{{ route('admin.screenings.index', ['risk' => 'Risiko Tinggi']) }}" class="text-xs text-danger font-weight-bold">Filter Risiko Tinggi <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 7. Total Puskesmas -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-teal elevation-1 text-white"><i class="fas fa-hospital"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Total Puskesmas</span>
                    <span class="info-box-number text-xl font-weight-bold text-dark">{{ number_format($totalPuskesmas) }}</span>
                    <a href="{{ route('admin.puskesmas.index') }}" class="text-xs text-teal font-weight-bold">Daftar Faskes <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- 8. Total Konten Edukasi -->
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box bg-white shadow-sm">
                <span class="info-box-icon bg-indigo elevation-1"><i class="fas fa-book-medical"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Konten Edukasi</span>
                    <span class="info-box-number text-xl font-weight-bold text-dark">{{ number_format($totalEdukasi) }}</span>
                    <a href="{{ route('admin.education.index') }}" class="text-xs text-indigo font-weight-bold">Kelola Materi <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Treatment Progress & Compliance Card -->
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-gradient-info">
                <div class="inner">
                    <h3>{{ $pasienAktif }} <sup style="font-size: 20px">Pasien</sup></h3>
                    <p class="font-weight-bold mb-0">Sedang Dalam Pengobatan Aktif</p>
                    <small>Fase Intensif & Lanjutan (Kategori 1, 2, RO, TPT)</small>
                </div>
                <div class="icon">
                    <i class="fas fa-pills"></i>
                </div>
                <a href="{{ route('admin.treatments.index', ['status' => 'Berjalan']) }}" class="small-box-footer">
                    Lihat Pasien Pengobatan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-gradient-success">
                <div class="inner">
                    <h3>{{ $tingkatKepatuhan }}<sup style="font-size: 20px">%</sup></h3>
                    <p class="font-weight-bold mb-0">Tingkat Kepatuhan Minum Obat</p>
                    <small>Berdasarkan verifikasi bukti minum obat harian</small>
                </div>
                <div class="icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <a href="{{ route('admin.treatments.monitoring') }}" class="small-box-footer">
                    Buka Monitoring Pengobatan <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-gradient-secondary">
                <div class="inner">
                    <h3>{{ $pasienSelesai }} <sup style="font-size: 20px">Pasien</sup></h3>
                    <p class="font-weight-bold mb-0">Pengobatan Tuntas / Sembuh</p>
                    <small>Evaluasi hasil klinis dan konversi dahak negatif</small>
                </div>
                <div class="icon">
                    <i class="fas fa-award"></i>
                </div>
                <a href="{{ route('admin.treatments.index', ['status' => 'Selesai']) }}" class="small-box-footer">
                    Daftar Pasien Tuntas <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 DATABASE-DRIVEN CHARTS -->
    <div class="row">
        <!-- Grafik 1: Status / Kategori Risiko Skrining -->
        <div class="col-lg-6">
            <div class="card card-primary card-outline">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-chart-pie mr-1 text-primary"></i> Grafik 1 — Distribusi Risiko Skrining TB
                    </h3>
                    <span class="badge badge-light border">Aktual Database</span>
                </div>
                <div class="card-body">
                    <div class="position-relative mb-3">
                        <canvas id="chart-risk" height="220"></canvas>
                    </div>
                    <div class="d-flex flex-row justify-content-around text-center border-top pt-2">
                        <div>
                            <span class="text-bold text-success"><i class="fas fa-circle mr-1"></i> Rendah</span>
                            <h5 class="mb-0 font-weight-bold">{{ $risikoRendah }}</h5>
                        </div>
                        <div>
                            <span class="text-bold text-warning"><i class="fas fa-circle mr-1"></i> Sedang</span>
                            <h5 class="mb-0 font-weight-bold">{{ $risikoSedang }}</h5>
                        </div>
                        <div>
                            <span class="text-bold text-danger"><i class="fas fa-circle mr-1"></i> Tinggi</span>
                            <h5 class="mb-0 font-weight-bold">{{ $risikoTinggi }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik 2: Tren Skrining Bulanan -->
        <div class="col-lg-6">
            <div class="card card-info card-outline">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-chart-line mr-1 text-info"></i> Grafik 2 — Tren Skrining TB Bulanan
                    </h3>
                    <span class="badge badge-light border">6 Bulan Terakhir</span>
                </div>
                <div class="card-body">
                    <div class="position-relative mb-3">
                        <canvas id="chart-trend" height="220"></canvas>
                    </div>
                    <p class="text-muted text-xs text-center mb-0 border-top pt-2">
                        Garis biru menunjukkan total skrining mandiri, garis merah menandai jumlah kasus berisiko tinggi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Grafik 3: Distribusi Wilayah (Kecamatan) -->
        <div class="col-lg-8">
            <div class="card card-warning card-outline">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-map-marker-alt mr-1 text-warning"></i> Grafik 3 — Distribusi Kasus & Skrining per Wilayah (Top Kecamatan)
                    </h3>
                    <a href="{{ route('admin.regions.index') }}" class="text-sm font-weight-bold">Detail Wilayah &rarr;</a>
                </div>
                <div class="card-body">
                    <div class="position-relative">
                        <canvas id="chart-region" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik 4: Jenis Kelamin -->
        <div class="col-lg-4">
            <div class="card card-success card-outline">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold">
                        <i class="fas fa-venus-mars mr-1 text-success"></i> Grafik 4 — Jenis Kelamin
                    </h3>
                    <span class="badge badge-light border">Responden</span>
                </div>
                <div class="card-body">
                    <div class="position-relative mb-2">
                        <canvas id="chart-gender" height="200"></canvas>
                    </div>
                    <div class="d-flex justify-content-around text-center border-top pt-2">
                        <div>
                            <span class="text-primary font-weight-bold"><i class="fas fa-mars mr-1"></i> Laki-laki</span>
                            <h5 class="mb-0 font-weight-bold">{{ $chartGender['data'][0] }}</h5>
                        </div>
                        <div>
                            <span class="text-pink font-weight-bold" style="color: #e83e8c;"><i class="fas fa-venus mr-1"></i> Perempuan</span>
                            <h5 class="mb-0 font-weight-bold">{{ $chartGender['data'][1] }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Data & Activity Stream -->
    <div class="row">
        <!-- Latest Screenings -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-transparent d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-clock mr-1 text-primary"></i> 5 Skrining Terbaru</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.screenings.index') }}" class="btn btn-tool text-primary font-weight-bold">Lihat Semua</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="dashboardScreeningsTable" class="table table-bordered table-hover data-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;" class="text-center">No</th>
                                    <th>Kode</th>
                                    <th>Nama Pasien</th>
                                    <th>Puskesmas / Wilayah</th>
                                    <th>Kategori Risiko</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th style="width: 80px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($latestScreenings as $scr)
                                    <tr>
                                        <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                        <td>
                                            <a href="{{ route('admin.screenings.show', $scr->encrypted_id) }}" class="font-weight-bold text-primary">
                                                {{ $scr->code }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="font-weight-bold">{{ $scr->person_name }}</span>
                                            <div class="text-muted text-xs">{{ $scr->age }} Th &bull; {{ $scr->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ optional($scr->puskesmas)->name ?? 'Puskesmas' }}</div>
                                            <div class="text-muted text-xs">Kec. {{ optional($scr->subdistrict)->name ?? '-' }}</div>
                                        </td>
                                        <td>
                                            @if($scr->risk_level == 'Risiko Tinggi')
                                                <span class="badge badge-tb-tinggi"><i class="fas fa-radiation mr-1"></i> Risiko Tinggi</span>
                                            @elseif($scr->risk_level == 'Risiko Sedang')
                                                <span class="badge badge-tb-sedang"><i class="fas fa-exclamation mr-1"></i> Risiko Sedang</span>
                                            @else
                                                <span class="badge badge-tb-rendah"><i class="fas fa-check mr-1"></i> Risiko Rendah</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($scr->status == 'Perlu Tindak Lanjut')
                                                <span class="badge badge-danger font-weight-bold">Perlu Tindak Lanjut</span>
                                            @elseif($scr->status == 'Dalam Pemantauan')
                                                <span class="badge badge-warning font-weight-bold text-dark">Dalam Pemantauan</span>
                                            @else
                                                <span class="badge badge-success font-weight-bold">Selesai</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-muted" data-order="{{ $scr->screened_at ? $scr->screened_at->timestamp : $scr->created_at->timestamp }}">
                                            {{ $scr->screened_at ? $scr->screened_at->format('d/m/Y H:i') : $scr->created_at->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.screenings.show', $scr->encrypted_id) }}" class="btn btn-xs btn-primary font-weight-bold">
                                                <i class="fas fa-eye"></i> Detail
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-transparent d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-1 text-secondary"></i> Log Aktivitas Sistem</h3>
                    <a href="{{ route('admin.activity_logs.index') }}" class="btn btn-tool text-primary font-weight-bold">Semua Log</a>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-3 pr-3">
                        @forelse($recentActivities as $act)
                            <li class="item">
                                <div class="product-info ml-0">
                                    <span class="product-title font-weight-bold text-dark">
                                        {{ $act->activity }}
                                        <span class="badge badge-info float-right text-xs">{{ $act->module }}</span>
                                    </span>
                                    <span class="product-description text-muted text-xs">
                                        {{ $act->description }}
                                    </span>
                                    <span class="text-xs text-muted">
                                        <i class="far fa-clock mr-1"></i> {{ $act->created_at->diffForHumans() }} &bull; Oleh {{ $act->user_name ?? 'Sistem' }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="item text-muted text-center py-3">Belum ada catatan aktivitas sistem.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        // Chart 1: Status / Risiko Skrining
        var ctxRisk = document.getElementById('chart-risk').getContext('2d');
        new Chart(ctxRisk, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartRisk['labels']) !!},
                datasets: [{
                    data: {!! json_encode($chartRisk['data']) !!},
                    backgroundColor: {!! json_encode($chartRisk['colors']) !!},
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 10 }
                }
            }
        });

        // Chart 2: Tren Skrining Bulanan
        var ctxTrend = document.getElementById('chart-trend').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartTrend['labels']) !!},
                datasets: [
                    {
                        label: 'Total Skrining',
                        data: {!! json_encode($chartTrend['total']) !!},
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.15)',
                        fill: true,
                        lineTension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Kasus Risiko Tinggi',
                        data: {!! json_encode($chartTrend['highRisk']) !!},
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.2)',
                        fill: false,
                        borderDash: [5, 5],
                        borderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true, stepSize: 5 }
                    }]
                },
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12 }
                }
            }
        });

        // Chart 3: Distribusi Wilayah
        var ctxRegion = document.getElementById('chart-region').getContext('2d');
        new Chart(ctxRegion, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartDistribusi['labels']) !!},
                datasets: [
                    {
                        label: 'Skrining TB',
                        backgroundColor: '#ffc107',
                        data: {!! json_encode($chartDistribusi['screenings']) !!}
                    },
                    {
                        label: 'Pasien Terdaftar',
                        backgroundColor: '#1b75bb',
                        data: {!! json_encode($chartDistribusi['patients']) !!}
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: { beginAtZero: true }
                    }],
                    xAxes: [{
                        gridLines: { display: false }
                    }]
                },
                legend: { position: 'bottom' }
            }
        });

        // Chart 4: Jenis Kelamin
        var ctxGender = document.getElementById('chart-gender').getContext('2d');
        new Chart(ctxGender, {
            type: 'pie',
            data: {
                labels: {!! json_encode($chartGender['labels']) !!},
                datasets: [{
                    data: {!! json_encode($chartGender['data']) !!},
                    backgroundColor: {!! json_encode($chartGender['colors']) !!},
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            }
        });
    });
</script>
@endpush

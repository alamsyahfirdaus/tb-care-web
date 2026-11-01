@extends('admin.layouts.app')

@section('content')
<!-- Row 1: Charts (Risk Distribution & Monthly Trend) -->
<div class="row">
    <!-- Chart 1: Risk Distribution -->
    <div class="col-md-5">
        <div class="card card-outline card-danger shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-chart-pie mr-1 text-danger"></i> Distribusi Kategori Risiko
                </h3>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="riskChart" height="230"></canvas>
                </div>
                <div class="d-flex flex-row justify-content-around text-center border-top pt-3">
                    <div>
                        <span class="text-danger font-weight-bold h5 mb-0 d-block">{{ $riskTinggi }}</span>
                        <small class="text-muted"><i class="fas fa-circle text-danger mr-1"></i>Tinggi</small>
                    </div>
                    <div>
                        <span class="text-warning font-weight-bold h5 mb-0 d-block">{{ $riskSedang }}</span>
                        <small class="text-muted"><i class="fas fa-circle text-warning mr-1"></i>Sedang</small>
                    </div>
                    <div>
                        <span class="text-success font-weight-bold h5 mb-0 d-block">{{ $riskRendah }}</span>
                        <small class="text-muted"><i class="fas fa-circle text-success mr-1"></i>Rendah</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Monthly Trend -->
    <div class="col-md-7">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-chart-line mr-1 text-primary"></i> Tren Skrining TB (6 Bulan Terakhir)
                </h3>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="trendChart" height="230"></canvas>
                </div>
                <div class="text-muted small text-center border-top pt-3">
                    <i class="fas fa-info-circle mr-1 text-info"></i> Data dihimpun dari skrining mandiri masyarakat dan skrining aktif kader wilayah.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Charts (Top Puskesmas & Age Demographics) -->
<div class="row">
    <!-- Chart 3: Top Puskesmas -->
    <div class="col-md-7">
        <div class="card card-outline card-teal shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-hospital-alt mr-1 text-teal"></i> Top 5 Puskesmas dengan Aktivitas Skrining Tertinggi
                </h3>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="pkmChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 4: Age Demographics -->
    <div class="col-md-5">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-users mr-1 text-warning"></i> Kelompok Usia Skrining
                </h3>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="ageChart" height="220"></canvas>
                </div>
                <div class="d-flex flex-row justify-content-between text-center border-top pt-2 small">
                    <div><b>Anak (&lt;15):</b> {{ $ageAnak }}</div>
                    <div><b>Remaja (15-24):</b> {{ $ageRemaja }}</div>
                    <div><b>Produktif (25-54):</b> {{ $ageProduktif }}</div>
                    <div><b>Lansia (55+):</b> {{ $ageLansia }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Charts (Gender Distribution & Clinical Lab Results) -->
<div class="row">
    <!-- Chart 5: Gender Distribution -->
    <div class="col-md-6">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-venus-mars mr-1 text-info"></i> Distribusi Jenis Kelamin
                </h3>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="genderChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 6: Clinical Diagnostics -->
    <div class="col-md-6">
        <div class="card card-outline card-purple shadow-sm" style="border-top-color: #6f42c1;">
            <div class="card-header">
                <h3 class="card-title font-weight-bold" style="color: #6f42c1;">
                    <i class="fas fa-microscope mr-1"></i> Hasil Pemeriksaan Lab TCM / Diagnostik
                </h3>
            </div>
            <div class="card-body">
                <div class="position-relative mb-4">
                    <canvas id="labChart" height="200"></canvas>
                </div>
                <div class="d-flex flex-row justify-content-around text-center border-top pt-2">
                    <div>
                        <span class="text-danger font-weight-bold">{{ $examPositive }}</span>
                        <div class="small text-muted">Positif</div>
                    </div>
                    <div>
                        <span class="text-success font-weight-bold">{{ $examNegative }}</span>
                        <div class="small text-muted">Negatif</div>
                    </div>
                    <div>
                        <span class="text-secondary font-weight-bold">{{ $examWaiting }}</span>
                        <div class="small text-muted">Menunggu / Lainnya</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // 1. Risk Chart (Doughnut)
    new Chart(document.getElementById('riskChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Risiko Tinggi', 'Risiko Sedang', 'Risiko Rendah'],
            datasets: [{
                data: [{{ $riskTinggi }}, {{ $riskSedang }}, {{ $riskRendah }}],
                backgroundColor: ['#dc3545', '#ffc107', '#28a745'],
                borderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });

    // 2. Trend Chart (Line)
    new Chart(document.getElementById('trendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: {!! json_encode($months) !!},
            datasets: [{
                label: 'Jumlah Skrining',
                data: {!! json_encode($monthlyTrend) !!},
                backgroundColor: 'rgba(0, 123, 255, 0.15)',
                borderColor: '#007bff',
                borderWidth: 2,
                pointBackgroundColor: '#007bff',
                pointRadius: 4,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: { beginAtZero: true, stepSize: 5 }
                }]
            }
        }
    });

    // 3. Top Puskesmas Chart (Bar)
    new Chart(document.getElementById('pkmChart').getContext('2d'), {
        type: 'horizontalBar',
        data: {
            labels: {!! json_encode($pkmNames) !!},
            datasets: [{
                label: 'Jumlah Skrining',
                data: {!! json_encode($pkmCounts) !!},
                backgroundColor: '#20c997'
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                xAxes: [{ ticks: { beginAtZero: true } }]
            }
        }
    });

    // 4. Age Demographics Chart (Pie)
    new Chart(document.getElementById('ageChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: ['Anak (<15)', 'Remaja (15-24)', 'Produktif (25-54)', 'Lansia (55+)'],
            datasets: [{
                data: [{{ $ageAnak }}, {{ $ageRemaja }}, {{ $ageProduktif }}, {{ $ageLansia }}],
                backgroundColor: ['#17a2b8', '#6610f2', '#fd7e14', '#6c757d']
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });

    // 5. Gender Chart (Bar)
    new Chart(document.getElementById('genderChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Laki-laki', 'Perempuan'],
            datasets: [{
                label: 'Peserta',
                data: [{{ $screeningMale }}, {{ $screeningFemale }}],
                backgroundColor: ['#007bff', '#e83e8c']
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true } }]
            }
        }
    });

    // 6. Lab Diagnostics Chart (Doughnut)
    new Chart(document.getElementById('labChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Positif', 'Negatif', 'Menunggu / Lainnya'],
            datasets: [{
                data: [{{ $examPositive }}, {{ $examNegative }}, {{ $examWaiting }}],
                backgroundColor: ['#dc3545', '#28a745', '#6c757d']
            }]
        },
        options: {
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });
});
</script>
@endpush

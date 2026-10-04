@extends('admin.layouts.app')

@section('content')
<div class="card card-outline card-primary card-tabs shadow-sm">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="masterTabs">
            <li class="nav-item">
                <a class="nav-link font-weight-bold {{ $tab == 'questions' ? 'active' : '' }}" href="{{ route('admin.master.index', ['tab' => 'questions']) }}">
                    <i class="fas fa-question-circle mr-1 text-primary"></i> Pertanyaan Skrining TB ({{ $questions->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link font-weight-bold {{ $tab == 'treatments' ? 'active' : '' }}" href="{{ route('admin.master.index', ['tab' => 'treatments']) }}">
                    <i class="fas fa-pills mr-1 text-success"></i> Standar Regimen Pengobatan ({{ $treatmentTypes->count() }})
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        @if($tab == 'questions')
            <div class="alert alert-info py-2">
                <i class="fas fa-info-circle mr-1"></i> Instrumen pertanyaan skrining gejala TB mengacu pada standar pedoman nasional penanggulangan tuberkulosis Kemenkes RI.
            </div>
            <div class="table-responsive">
                <table id="questionsTable" class="table table-bordered table-hover data-table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>ID</th>
                            <th>Teks Pertanyaan Gejala / Faktor Risiko</th>
                            <th>Kategori Instrumen</th>
                            <th class="text-center">Gejala Kritis (Kunci)</th>
                            <th style="width: 100px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($questions as $idx => $q)
                        <tr>
                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                            <td><code>#{{ $q->id }}</code></td>
                            <td>
                                <strong class="text-dark">{{ $q->question_text }}</strong>
                            </td>
                            <td>
                                <span class="badge badge-light border text-muted px-2 py-1">
                                    {{ $q->category->name ?? 'Gejala TB' }}
                                </span>
                            </td>
                            <td class="text-center" data-order="{{ $q->is_critical ? 1 : 0 }}">
                                @if($q->is_critical)
                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i>Kritis / Utama</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1">Gejala Tambahan</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal{{ $q->id }}" title="Edit Teks Pertanyaan">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Edit Pertanyaan -->
                        <div class="modal fade" id="editModal{{ $q->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('admin.master.questions.update', $q->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning">
                                            <h5 class="modal-title font-weight-bold"><i class="fas fa-pencil-alt mr-1"></i> Edit Pertanyaan #{{ $q->id }}</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Teks Pertanyaan Gejala <span class="text-danger">*</span></label>
                                                <textarea name="question_text" rows="3" class="form-control" required>{{ $q->question_text }}</textarea>
                                            </div>
                                            <div class="form-group">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" name="is_critical" value="1" class="custom-control-input" id="crit{{ $q->id }}" {{ $q->is_critical ? 'checked' : '' }}>
                                                    <label class="custom-control-label font-weight-bold text-danger" for="crit{{ $q->id }}">
                                                        Tandai sebagai Gejala Kritis (misal batuk berdahak &gt; 2 minggu)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <!-- Regimen Pengobatan -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0"><i class="fas fa-pills mr-1 text-success"></i> Daftar paket terapi obat tuberkulosis (OAT) standar Kemenkes.</p>
                <button type="button" class="btn btn-success btn-sm font-weight-bold" data-toggle="modal" data-target="#modalAddTreatment">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Regimen Baru
                </button>
            </div>

            <div class="table-responsive">
                <table id="treatmentTypesTable" class="table table-bordered table-hover data-table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th>Nama Regimen / Jenis Pengobatan</th>
                            <th class="text-center">Durasi Terapi</th>
                            <th>Keterangan / Panduan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($treatmentTypes as $idx => $tt)
                        <tr>
                            <td class="text-center text-muted font-weight-bold">{{ $loop->iteration }}</td>
                            <td><strong class="text-dark">{{ $tt->treatment_type }}</strong></td>
                            <td class="text-center" data-order="{{ $tt->treatment_duration }}">
                                <span class="badge badge-info px-2 py-1">{{ $tt->treatment_duration }} {{ ucfirst($tt->duration_unit) }}</span>
                            </td>
                            <td><small class="text-muted">{{ $tt->description ?: 'Regimen terapi OAT lini pertama/kedua standar' }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Modal Tambah Regimen -->
            <div class="modal fade" id="modalAddTreatment" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('admin.master.treatments.store') }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-1"></i> Tambah Regimen Pengobatan</h5>
                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Nama Regimen <span class="text-danger">*</span></label>
                                    <input type="text" name="treatment_type" class="form-control" placeholder="Contoh: Kategori 1 (2RHZE / 4RH)" required>
                                </div>
                                <div class="form-row">
                                    <div class="col-8 form-group">
                                        <label>Durasi Pengobatan <span class="text-danger">*</span></label>
                                        <input type="number" name="treatment_duration" class="form-control" value="6" min="1" required>
                                    </div>
                                    <div class="col-4 form-group">
                                        <label>Satuan <span class="text-danger">*</span></label>
                                        <select name="duration_unit" class="form-control">
                                            <option value="bulan">Bulan</option>
                                            <option value="minggu">Minggu</option>
                                            <option value="hari">Hari</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Deskripsi Tambahan</label>
                                    <textarea name="description" rows="2" class="form-control" placeholder="Catatan dosis atau indikasi..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success font-weight-bold"><i class="fas fa-save mr-1"></i> Simpan Regimen</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

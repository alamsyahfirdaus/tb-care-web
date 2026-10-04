@extends('admin.layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas {{ $isEdit ? 'fa-pencil-alt' : 'fa-paper-plane' }} mr-1 text-primary"></i>
                    {{ $isEdit ? 'Edit Notifikasi Broadcast' : 'Buat Notifikasi / Broadcast Baru' }}
                </h3>
            </div>
            <form action="{{ $isEdit ? route('admin.notifications.update', $notification->encrypted_id) : route('admin.notifications.store') }}" method="POST">
                @csrf
                @if($isEdit)
                    @method('PUT')
                    <input type="hidden" name="encrypted_id" value="{{ $notification->encrypted_id }}">
                @endif

                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <h5><i class="icon fas fa-ban"></i> Terjadi Kesalahan Input</h5>
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Tipe Notifikasi <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="type" class="form-control select2" required style="width: 100%;">
                                @foreach(['Pengumuman' => 'Pengumuman Umum', 'Pengingat Minum Obat' => 'Pengingat Minum Obat Pasien', 'Peringatan Kasus' => 'Peringatan Kasus / Follow-up Klinis', 'Edukasi TB' => 'Edukasi TB & Penyuluhan'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('type', $notification->type) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Target Audience <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="target_role" id="target_role" class="form-control select2" required style="width: 100%;">
                                @foreach(['Semua' => 'Semua Pengguna Aplikasi', 'Pasien' => 'Seluruh Pasien TB Terdaftar', 'Petugas' => 'Petugas Kesehatan & PJTB Puskesmas'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ old('target_role', $notification->target_role) == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Tentukan segmen penerima pesan yang akan mendapatkan notifikasi.</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Faskes / Puskesmas Spesifik</label>
                        <div class="col-sm-9">
                            <select name="target_puskesmas_id" class="form-control select2" style="width: 100%;">
                                <option value="">-- Semua Wilayah Puskesmas (Nasional / Daerah) --</option>
                                @foreach($puskesmasList as $pkm)
                                    <option value="{{ $pkm->id }}" {{ old('target_puskesmas_id', $notification->target_puskesmas_id) == $pkm->id ? 'selected' : '' }}>
                                        {{ $pkm->name }} ({{ $pkm->subdistrict->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Opsional: Batasi penerima hanya untuk pasien/petugas di puskesmas tertentu.</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Judul Pesan <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" 
                                   value="{{ old('title', $notification->title) }}" 
                                   placeholder="Contoh: Jadwal Pengambilan Obat Paket Tahap Lanjutan" required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Isi Pesan Notifikasi <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror" 
                                      placeholder="Tuliskan isi pesan notifikasi secara jelas dan komunikatif..." required>{{ old('message', $notification->message) }}</textarea>
                            @error('message')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Status Eksekusi <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="status_send" name="status" value="Terkirim" class="custom-control-input"
                                       {{ old('status', $notification->status ?? 'Terkirim') == 'Terkirim' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success" for="status_send">
                                    <i class="fas fa-paper-plane mr-1"></i> Kirim Sekarang
                                </label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="status_draft" name="status" value="Draft" class="custom-control-input"
                                       {{ old('status', $notification->status) == 'Draft' ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-secondary" for="status_draft">
                                    <i class="fas fa-pencil-alt mr-1"></i> Simpan Sebagai Draft
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-light d-flex justify-content-between">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary font-weight-bold">
                        <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
                    </a>
                    <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Proses Notifikasi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\Patient;
use App\Models\Officer;
use App\Models\Screening;
use App\Models\ClinicalExamination;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\DB;

class PuskesmasController extends Controller
{
    public function index(Request $request)
    {
        $faskesType = $request->query('type'); // 'klinik', 'rs', or null (puskesmas)
        $subdistrictFilter = $request->query('subdistrict_id');
        $keyword = $request->query('q');

        $query = Puskesmas::with(['subdistrict.district.province'])
            ->withCount(['officers', 'patients', 'screenings']);

        if ($subdistrictFilter) {
            $query->where('subdistrict_id', $subdistrictFilter);
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%")
                  ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        $puskesmas = $query->orderBy('name', 'asc')->get();
        $subdistricts = Subdistrict::orderBy('name')->get();

        $pageTitle = 'Fasilitas Kesehatan: Puskesmas';
        if ($faskesType == 'klinik') $pageTitle = 'Fasilitas Kesehatan: Klinik Pratama';
        if ($faskesType == 'rs') $pageTitle = 'Fasilitas Kesehatan: Rumah Sakit Rujukan';

        return view('admin.puskesmas.index', compact('puskesmas', 'subdistricts', 'faskesType', 'subdistrictFilter', 'keyword', 'pageTitle'))
            ->with([
                'title'        => $pageTitle,
                'pageSubtitle' => 'Pengelolaan unit faskes pembina, penanggung jawab TB (PJTB), dan pemantauan wilayah binaan.'
            ]);
    }

    public function show($id)
    {
        $id = decrypt_id($id);
        $pkm = Puskesmas::with([
            'subdistrict.district.province',
            'officers.user',
            'patients.user',
            'screenings' => function($q) {
                $q->orderByDesc('id');
            }
        ])->withCount(['officers', 'patients', 'screenings'])->findOrFail($id);

        return view('admin.puskesmas.show', compact('pkm'))->with([
            'title'        => 'Detail Puskesmas: ' . $pkm->name,
            'pageTitle'    => 'Puskesmas: ' . $pkm->name,
            'pageSubtitle' => 'Rincian petugas PJTB/kader, sebaran pasien pengobatan, dan aktivitas skrining wilayah.'
        ]);
    }

    public function form(?string $encryptedId = null)
    {
        $isEdit = false;
        $puskesmas = new Puskesmas();

        if ($encryptedId) {
            $id = decrypt_id($encryptedId);
            $puskesmas = Puskesmas::findOrFail($id);
            $isEdit = true;
        }

        $subdistricts = Subdistrict::orderBy('name')->get();

        return view('admin.puskesmas.form', compact('puskesmas', 'subdistricts', 'isEdit'))->with([
            'title'        => $isEdit ? ('Edit Puskesmas: ' . $puskesmas->name) : 'Tambah Fasilitas Kesehatan',
            'pageTitle'    => $isEdit ? 'Edit Data Fasilitas Kesehatan' : 'Tambah Fasilitas Kesehatan Baru',
            'pageSubtitle' => $isEdit ? 'Perbarui data nama, kode, dan alamat Puskesmas.' : 'Registrasi unit Puskesmas atau faskes layanan TB baru.',
        ]);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($id)
    {
        return $this->form($id);
    }

    public function save(Request $request, ?string $encryptedId = null)
    {
        $resolvedEncryptedId = $request->input('encrypted_id') ?? $encryptedId;
        $isEdit = !empty($resolvedEncryptedId);
        $puskesmas = $isEdit ? Puskesmas::findOrFail(decrypt_id($resolvedEncryptedId)) : new Puskesmas();

        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => ['nullable', 'string', 'max:50', $isEdit ? Rule::unique('puskesmas')->ignore($puskesmas->id) : 'unique:puskesmas,code'],
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'address'        => 'nullable|string',
        ], [
            'name.required'           => 'Nama Puskesmas/Faskes wajib diisi.',
            'code.unique'             => 'Kode faskes sudah terdaftar.',
            'subdistrict_id.required' => 'Kecamatan wajib dipilih.',
        ]);

        return DB::transaction(function () use ($request, $puskesmas, $isEdit) {
            if (!$isEdit) {
                $code = $request->code ?: ('PKM-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $request->name), 0, 3)) . '-' . rand(100, 999));
                $puskesmas->fill([
                    'code'           => $code,
                    'name'           => $request->name,
                    'subdistrict_id' => $request->subdistrict_id,
                    'address'        => $request->address,
                ])->save();

                ActivityLog::log('Tambah Puskesmas', 'Faskes', "Menambahkan Puskesmas {$puskesmas->name} (Kode {$code}).");
                $message = 'Fasilitas kesehatan berhasil ditambahkan.';
            } else {
                $puskesmas->update([
                    'name'           => $request->name,
                    'code'           => $request->code ?? $puskesmas->code,
                    'subdistrict_id' => $request->subdistrict_id,
                    'address'        => $request->address,
                ]);

                ActivityLog::log('Perbarui Puskesmas', 'Faskes', "Memperbarui data Puskesmas {$puskesmas->name}.");
                $message = 'Data Puskesmas berhasil diperbarui.';
            }

            return redirect()->route('admin.puskesmas.show', $puskesmas)->with('success', $message);
        });
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $puskesmas = Puskesmas::findOrFail($id);
        $name = $puskesmas->name;

        // Check for active dependencies before deleting
        $hasPatients = Patient::where('puskesmas_id', $id)->exists();
        $hasOfficers = Officer::where('puskesmas_id', $id)->exists();
        $hasScreenings = Screening::where('puskesmas_id', $id)->exists();
        $hasExaminations = ClinicalExamination::where('puskesmas_id', $id)->exists();

        if ($hasPatients || $hasOfficers || $hasScreenings || $hasExaminations) {
            return redirect()->route('admin.puskesmas.index')
                ->with('error', "Puskesmas {$name} tidak dapat dihapus karena masih memiliki relasi data pasien, petugas, atau riwayat pelayanan.");
        }

        DB::transaction(function () use ($puskesmas) {
            $puskesmas->delete();
        });

        ActivityLog::log('Hapus Puskesmas', 'Faskes', "Menghapus Puskesmas {$name}.");

        return redirect()->route('admin.puskesmas.index')->with('success', "Puskesmas {$name} berhasil dihapus.");
    }
}

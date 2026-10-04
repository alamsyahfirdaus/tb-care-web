<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function create()
    {
        $subdistricts = Subdistrict::orderBy('name')->get();

        return view('admin.puskesmas.form', compact('subdistricts'))->with([
            'title'        => 'Tambah Fasilitas Kesehatan',
            'pageTitle'    => 'Tambah Fasilitas Kesehatan Baru',
            'pageSubtitle' => 'Registrasi unit Puskesmas atau faskes layanan TB baru.',
            'puskesmas'    => new Puskesmas(),
            'isEdit'       => false,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'nullable|string|max:50|unique:puskesmas,code',
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'address'        => 'nullable|string',
        ], [
            'name.required'           => 'Nama Puskesmas/Faskes wajib diisi.',
            'code.unique'             => 'Kode faskes sudah terdaftar.',
            'subdistrict_id.required' => 'Kecamatan wajib dipilih.',
        ]);

        $code = $request->code ?: ('PKM-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $request->name), 0, 3)) . '-' . rand(100, 999));

        $pkm = Puskesmas::create([
            'code'           => $code,
            'name'           => $request->name,
            'subdistrict_id' => $request->subdistrict_id,
            'address'        => $request->address,
        ]);

        ActivityLog::log('Tambah Puskesmas', 'Faskes', "Menambahkan Puskesmas {$pkm->name} (Kode {$code}).");

        return redirect()->route('admin.puskesmas.show', $pkm)->with('success', 'Fasilitas kesehatan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $id = decrypt_id($id);
        $puskesmas = Puskesmas::findOrFail($id);
        $subdistricts = Subdistrict::orderBy('name')->get();

        return view('admin.puskesmas.form', compact('puskesmas', 'subdistricts'))->with([
            'title'        => 'Edit Puskesmas: ' . $puskesmas->name,
            'pageTitle'    => 'Edit Data Fasilitas Kesehatan',
            'pageSubtitle' => 'Perbarui data nama, kode, dan alamat Puskesmas.',
            'isEdit'       => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = decrypt_id($id);
        $puskesmas = Puskesmas::findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => ['nullable', 'string', 'max:50', Rule::unique('puskesmas')->ignore($puskesmas->id)],
            'subdistrict_id' => 'required|exists:subdistricts,id',
            'address'        => 'nullable|string',
        ]);

        $puskesmas->update([
            'name'           => $request->name,
            'code'           => $request->code ?? $puskesmas->code,
            'subdistrict_id' => $request->subdistrict_id,
            'address'        => $request->address,
        ]);

        ActivityLog::log('Perbarui Puskesmas', 'Faskes', "Memperbarui data Puskesmas {$puskesmas->name}.");

        return redirect()->route('admin.puskesmas.show', $puskesmas)->with('success', 'Data Puskesmas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $puskesmas = Puskesmas::findOrFail($id);
        $name = $puskesmas->name;
        $puskesmas->delete();

        ActivityLog::log('Hapus Puskesmas', 'Faskes', "Menghapus Puskesmas {$name}.");

        return redirect()->route('admin.puskesmas.index')->with('success', "Puskesmas {$name} berhasil dihapus.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'subdistrict');
        $q = $request->query('q');

        // Summary Counts
        $totalProvinces    = Province::count();
        $totalDistricts    = District::count();
        $totalSubdistricts = Subdistrict::count();
        $totalVillages     = Village::count();

        // Data based on selected tab
        $provinces = null;
        $districts = null;
        $subdistricts = null;
        $villages = null;

        if ($tab == 'province') {
            $query = Province::withCount('districts');
            if ($q) $query->where('name', 'like', "%{$q}%");
            $provinces = $query->orderBy('name')->get();
        } elseif ($tab == 'district') {
            $query = District::with('province')->withCount('subdistricts');
            if ($q) $query->where('name', 'like', "%{$q}%");
            $districts = $query->orderBy('name')->get();
        } elseif ($tab == 'village') {
            $query = Village::with(['subdistrict.district.province'])->withCount('patients');
            if ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhereHas('subdistrict', function($sq) use ($q) {
                          $sq->where('name', 'like', "%{$q}%");
                      });
            }
            $villages = $query->orderBy('name')->get();
        } else {
            // Default: subdistrict (Kecamatan)
            $tab = 'subdistrict';
            $query = Subdistrict::with(['district.province'])->withCount(['villages', 'puskesmas', 'patients', 'screenings']);
            if ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhereHas('district', function($dq) use ($q) {
                          $dq->where('name', 'like', "%{$q}%");
                      });
            }
            $subdistricts = $query->orderBy('name')->get();
        }

        // List for modals/creation
        $allDistricts = District::orderBy('name')->get();
        $allSubdistricts = Subdistrict::orderBy('name')->get();

        return view('admin.regions.index', compact(
            'tab', 'q', 'totalProvinces', 'totalDistricts', 'totalSubdistricts', 'totalVillages',
            'provinces', 'districts', 'subdistricts', 'villages', 'allDistricts', 'allSubdistricts'
        ))->with([
            'title'        => 'Data Wilayah Administratif TB Care',
            'pageTitle'    => 'Hierarki Wilayah TB Care',
            'pageSubtitle' => 'Pengelolaan hierarki bertingkat: Provinsi, Kabupaten/Kota, Kecamatan, dan Desa/Kelurahan.'
        ]);
    }

    public function storeSubdistrict(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'district_id' => 'required|exists:districts,id',
        ]);

        $sub = Subdistrict::create([
            'name'        => $request->name,
            'district_id' => $request->district_id,
        ]);

        ActivityLog::log('Tambah Wilayah', 'Wilayah', "Menambahkan Kecamatan {$sub->name}.");

        return redirect()->route('admin.regions.index', ['tab' => 'subdistrict'])->with('success', "Kecamatan {$sub->name} berhasil ditambahkan.");
    }

    public function storeVillage(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'subdistrict_id' => 'required|exists:subdistricts,id',
        ]);

        $vil = Village::create([
            'name'           => $request->name,
            'subdistrict_id' => $request->subdistrict_id,
        ]);

        ActivityLog::log('Tambah Wilayah', 'Wilayah', "Menambahkan Desa/Kelurahan {$vil->name}.");

        return redirect()->route('admin.regions.index', ['tab' => 'village'])->with('success', "Desa/Kelurahan {$vil->name} berhasil ditambahkan.");
    }

    public function updateSubdistrict(Request $request, $id)
    {
        $id = decrypt_id($id);
        $sub = Subdistrict::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:100',
            'district_id' => 'required|exists:districts,id',
        ]);

        \DB::transaction(function () use ($sub, $request) {
            $sub->update([
                'name'        => $request->name,
                'district_id' => $request->district_id,
            ]);
        });

        ActivityLog::log('Perbarui Wilayah', 'Wilayah', "Memperbarui Kecamatan {$sub->name}.");

        return redirect()->route('admin.regions.index', ['tab' => 'subdistrict'])->with('success', "Kecamatan {$sub->name} berhasil diperbarui.");
    }

    public function destroySubdistrict($id)
    {
        $id = decrypt_id($id);
        $sub = Subdistrict::findOrFail($id);
        $name = $sub->name;

        \DB::transaction(function () use ($sub) {
            $sub->delete();
        });

        ActivityLog::log('Hapus Wilayah', 'Wilayah', "Menghapus Kecamatan {$name}.");

        return redirect()->route('admin.regions.index', ['tab' => 'subdistrict'])->with('success', "Kecamatan {$name} berhasil dihapus.");
    }

    public function updateVillage(Request $request, $id)
    {
        $id = decrypt_id($id);
        $vil = Village::findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:100',
            'subdistrict_id' => 'required|exists:subdistricts,id',
        ]);

        \DB::transaction(function () use ($vil, $request) {
            $vil->update([
                'name'           => $request->name,
                'subdistrict_id' => $request->subdistrict_id,
            ]);
        });

        ActivityLog::log('Perbarui Wilayah', 'Wilayah', "Memperbarui Desa/Kelurahan {$vil->name}.");

        return redirect()->route('admin.regions.index', ['tab' => 'village'])->with('success', "Desa/Kelurahan {$vil->name} berhasil diperbarui.");
    }

    public function destroyVillage($id)
    {
        $id = decrypt_id($id);
        $vil = Village::findOrFail($id);
        $name = $vil->name;

        \DB::transaction(function () use ($vil) {
            $vil->delete();
        });

        ActivityLog::log('Hapus Wilayah', 'Wilayah', "Menghapus Desa/Kelurahan {$name}.");

        return redirect()->route('admin.regions.index', ['tab' => 'village'])->with('success', "Desa/Kelurahan {$name} berhasil dihapus.");
    }

    // AJAX API for Dependent Dropdown
    public function getDistricts(Request $request)
    {
        $provinceId = $request->get('province_id');
        $districts = District::when($provinceId, function($q) use ($provinceId) {
            $q->where('province_id', $provinceId);
        })->orderBy('name')->get(['id', 'name']);

        return response()->json($districts);
    }

    public function getSubdistricts(Request $request)
    {
        $districtId = $request->get('district_id');
        $subdistricts = Subdistrict::when($districtId, function($q) use ($districtId) {
            $q->where('district_id', $districtId);
        })->orderBy('name')->get(['id', 'name']);

        return response()->json($subdistricts);
    }

    public function getVillages(Request $request)
    {
        $subdistrictId = $request->get('subdistrict_id');
        $villages = Village::when($subdistrictId, function($q) use ($subdistrictId) {
            $q->where('subdistrict_id', $subdistrictId);
        })->orderBy('name')->get(['id', 'name']);

        return response()->json($villages);
    }
}

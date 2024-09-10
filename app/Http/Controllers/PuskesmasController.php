<?php

namespace App\Http\Controllers;

use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\HealthOffice;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class PuskesmasController extends Controller
{
    public function index()
    {
        $role = session('role');
        $puskesmasQuery = Puskesmas::with('subdistrict.district.province')->orderByDesc('id');

        if ($role == 1) {
            // Jika role adalah 1 (admin), ambil semua data Puskesmas dan Subdistricts
            $puskesmas = $puskesmasQuery->get();
            $subdistricts = Subdistrict::with('district.province')->orderBy('name', 'asc')->get();
        } elseif ($role == 2) {
            // Jika role adalah 2 (Health Office), ambil data HealthOffice berdasarkan user_id
            $healthOffice = HealthOffice::with('district.province')->where('user_id', Auth::id())->first();

            if ($healthOffice) {
                if ($healthOffice->office_type == 'Provinsi') {
                    // Filter Puskesmas berdasarkan province_id
                    $puskesmas = $puskesmasQuery->whereHas('subdistrict.district', function ($query) use ($healthOffice) {
                        $query->where('province_id', $healthOffice->district->province_id);
                    })->get();

                    // Ambil Subdistricts berdasarkan province_id
                    $subdistricts = Subdistrict::with('district.province')
                        ->whereHas('district', function ($query) use ($healthOffice) {
                            $query->where('province_id', $healthOffice->district->province_id);
                        })
                        ->orderBy('name', 'asc')
                        ->get();
                } else {
                    // Filter Puskesmas berdasarkan district_id
                    $puskesmas = $puskesmasQuery->whereHas('subdistrict', function ($query) use ($healthOffice) {
                        $query->where('district_id', $healthOffice->district_id);
                    })->get();

                    // Ambil Subdistricts berdasarkan district_id
                    $subdistricts = Subdistrict::with('district.province')
                        ->where('district_id', $healthOffice->district_id)
                        ->orderBy('name', 'asc')
                        ->get();
                }
            } else {
                // Jika HealthOffice tidak ditemukan, tampilkan pesan error
                return redirect()->back()->with('error', 'Data tidak ditemukan');
            }
        } else {
            return redirect()->back()->with('error', 'Akses tidak sah');
        }

        $data = [
            'title'        => 'Puskesmas',
            'puskesmas'    => $puskesmas,
            'subdistricts' => $subdistricts,
        ];

        return view('puskesmas-index', $data);
    }


    public function edit($id)
    {
        $puskesmas = Puskesmas::with('subdistrict.district.province')->find(base64_decode($id));

        $data = [
            'title'         => 'Puskesmas',
            'data'          => $puskesmas,
            'subdistricts'  => Subdistrict::with('district.province')->orderBy('name', 'asc')->get(),
        ];

        return view('puskesmas-index', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $puskesmas = Puskesmas::find(base64_decode($id));
        if (!$puskesmas) {
            $puskesmas = new Puskesmas();
        }

        $validatedData = $request->validate([
            'code' => [
                'nullable',
                Rule::unique('puskesmas', 'code')->ignore($puskesmas->id)->where(function ($query) {
                    return $query->whereNotNull('code')->where('code', '!=', '0');
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'subdistrict_id' => ['required', 'exists:subdistricts,id'],
        ]);

        $puskesmas->code = $validatedData['code'];
        $puskesmas->name = $validatedData['name'];
        $puskesmas->address = $validatedData['address'];
        $puskesmas->subdistrict_id = $validatedData['subdistrict_id'];

        $puskesmas->save();

        $data = array(
            'status' => true,
            'message' => 'Data puskesmas berhasil disimpan.',
        );

        if (!$puskesmas->wasRecentlyCreated) {
            $data['previous'] = true;
        }

        return response()->json($data, 200);
    }

    public function destroy($id): RedirectResponse
    {
        $puskesmas = Puskesmas::find(base64_decode($id));

        if (!$puskesmas) {
            return redirect()->route('pkm')->with('error', 'Data puskesmas tidak ditemukan.');
        }

        $puskesmas->delete();

        return redirect()->route('pkm')->with('success', 'Data puskesmas berhasil dihapus.');
    }
}

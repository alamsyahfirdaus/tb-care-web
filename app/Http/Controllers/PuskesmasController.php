<?php

namespace App\Http\Controllers;

use App\Models\Puskesmas;
use App\Models\Subdistrict;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PuskesmasController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Puskesmas',
            'puskesmas' => Puskesmas::with('subdistrict.district.province')->orderByDesc('id')->get()
        ];

        return view('pkm-index', $data);
    }

    public function create()
    {
        $data = array(
            'title' => 'Puskesmas',
            'nextKode' => $this->_nextKode(),
            'subdistricts' => Subdistrict::all(),
        );

        return view('pkm-add-edit', $data);
    }

    private function _nextKode()
    {
        $latestPuskesmas = Puskesmas::count();
        $nextKode = sprintf('P%010d', $latestPuskesmas + 1);

        while (Puskesmas::where('kode', $nextKode)->exists()) {
            $latestPuskesmas++;
            $nextKode = sprintf('P%010d', $latestPuskesmas + 1);
        }

        return $nextKode;
    }


    public function edit($id)
    {
        $query = Puskesmas::with('subdistrict.district.province')->find(base64_decode($id));

        $data = [
            'title' => 'Puskesmas',
            'nextKode' => $this->_nextKode(),
            'data' => $query,
            'subdistricts' => Subdistrict::all(),
        ];


        return view('pkm-add-edit', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $query = Puskesmas::find(base64_decode($id));
        if (!$query) {
            $query = new Puskesmas();
        }

        $validatedData = $request->validate([
            'kode' => ['required', 'string', 'max:255', isset($query->id) ? Rule::unique('puskesmas', 'kode')->ignore($query->id) : 'unique:puskesmas,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'lokasi' => ['required', 'string'],
            'subdistrict_id' => ['required', 'exists:subdistricts,id'],
        ]);

        $query->kode = $validatedData['kode'];
        $query->nama = $validatedData['nama'];
        $query->lokasi = $validatedData['lokasi'];
        $query->subdistrict_id = $validatedData['subdistrict_id'];

        $query->save();

        return response()->json(
            [
                'success' => true,
                'message' => 'Data Puskesmas berhasil disimpan.',
            ],
            200
        );
    }

    public function destroy($id): RedirectResponse
    {
        $query = Puskesmas::find(base64_decode($id));

        if (!$query) {
            return redirect()->route('pkm')->with('error', 'Data Puskesmas tidak ditemukan.');
        }

        $query->delete();

        return redirect()->route('pkm')->with('success', 'Data Puskesmas berhasil dihapus.');
    }

    public function getAddress(Request $request): JsonResponse
    {
        $subdistrict_id = $request->subdistrict_id;
        $subdistrict = Subdistrict::with('district.province')->find($subdistrict_id);

        $data = [
            'district_name' => $subdistrict->district->district_name ?? '',
            'province_name' => $subdistrict->district->province->province_name ?? '',
        ];

        return response()->json($data);
    }

    public function getRegion(Request $request): JsonResponse
    {
        $puskesmas_id   = $request->puskesmas_id;
        $puskesmas      = Puskesmas::with('subdistrict.district.province')->find($puskesmas_id);

        if (isset($puskesmas)) {
            $subdistrict_name =
                $puskesmas->subdistrict->subdistrict_name;
            $district_name =
                $puskesmas->subdistrict->district->district_name;
            $province_name =
                $puskesmas->subdistrict->district->province
                ->province_name;
            $wilayah =
                $subdistrict_name .
                ', ' .
                $district_name .
                ', ' .
                $province_name;
        } else {
            $wilayah = '';
        }

        return response()->json(['wilayah' => $wilayah]);
    }
}

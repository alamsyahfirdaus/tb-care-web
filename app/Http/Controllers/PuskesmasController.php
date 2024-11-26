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
        $data = [
            'title'        => 'Puskesmas',
            'puskesmas'    => Puskesmas::getAllPuskesmas(),
            'subdistricts' => Subdistrict::getAllSubdistricts(),
        ];

        return view('puskesmas-index', $data);
    }

    public function edit($id)
    {
        $puskesmas = Puskesmas::getPuskesmasById(base64_decode($id));

        if (!$puskesmas) {
            return redirect()->back();
        }

        $data = [
            'title'         => 'Puskesmas',
            'data'          => Puskesmas::getPuskesmasById(base64_decode($id)),
            'subdistricts'  => Subdistrict::getAllSubdistricts(),
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
            'message' => 'Data Puskesmas berhasil disimpan.',
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
            return redirect()->route('pkm')->with('error', 'Data Puskesmas tidak ditemukan.');
        }

        $puskesmas->delete();

        return redirect()->route('pkm')->with('success', 'Data Puskesmas berhasil dihapus.');
    }
}

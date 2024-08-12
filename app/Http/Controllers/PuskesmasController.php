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
            'title'         => 'Puskesmas',
            'puskesmas'     => Puskesmas::with('subdistrict.district.province')->orderByDesc('id')->get(),
            'subdistricts'  => Subdistrict::with('district.province')->orderBy('name', 'asc')->get(),
        ];

        return view('puskesmas', $data);
    }

    public function edit($id)
    {
        $puskesmas = Puskesmas::with('subdistrict.district.province')->find(base64_decode($id));

        $data = [
            'title'         => 'Puskesmas',
            'data'          => $puskesmas,
            'subdistricts'  => Subdistrict::with('district.province')->orderBy('name', 'asc')->get(),
        ];

        return view('puskesmas', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $puskesmas = Puskesmas::find(base64_decode($id));
        if (!$puskesmas) {
            $puskesmas = new Puskesmas();
        }

        $validatedData = $request->validate([
            'code' => ['required', 'string', 'max:255', isset($puskesmas->id) ? Rule::unique('puskesmas', 'code')->ignore($puskesmas->id) : 'unique:puskesmas,code'],
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
            'success' => true,
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

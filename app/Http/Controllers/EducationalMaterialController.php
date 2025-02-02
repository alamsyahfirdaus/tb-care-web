<?php

namespace App\Http\Controllers;

use App\Models\EducationalMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EducationalMaterialController extends Controller
{
    public function index()
    {
        $data = [
            'title'     => 'Materi Edukasi',
            'materials' => EducationalMaterial::getAllMaterials(),
        ];

        return view('educational-material-index', $data);
    }

    public function edit($id)
    {
        $material = EducationalMaterial::getMaterialById(base64_decode($id));

        $data = [
            'title'     => 'Materi Edukasi',
            'data'      => $material,
        ];

        return view('educational-material-index', $data);
    }

    public function show($id)
    {
        $material = EducationalMaterial::getMaterialById(base64_decode($id));

        $data = [
            'title'     => 'Materi Edukasi',
            'data'      => $material,
        ];

        return view('educational-material-detail', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $material = EducationalMaterial::find(base64_decode($id)) ?? new EducationalMaterial();

        $rules = [
            'title_material' => ['required', 'string'],
            'material_type'  => ['required', 'in:file,url'],
            'description'    => ['required', 'string'],
        ];

        if ($request->input('material_type') === 'file') {
            $rules['material_file'] = [
                $material->id && $material->material_file ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ];
            $rules['material_url'] = ['nullable', 'url'];
        } elseif ($request->input('material_type') === 'url') {
            $rules['material_file'] = ['nullable', 'file'];
            $rules['material_url'] = [
                'required',
                'url',
                function ($attribute, $value, $fail) {
                    $pattern = '/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)/';
                    if (!preg_match($pattern, $value)) {
                        $fail('Tautan Video harus berupa URL YouTube yang valid.');
                    }
                },
            ];
            $rules['thumbnail'] = [
                $material->id && $material->thumbnail ? 'nullable' : 'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ];
        }

        if ($material->id) {
            $rules['is_publish'] = ['required', 'in:0,1'];
        }

        $validatedData = $request->validate($rules);

        $material->fill([
            'title_material' => $validatedData['title_material'],
            'material_type'  => $validatedData['material_type'],
            'description'    => $validatedData['description'],
        ]);

        if ($material->material_type === 'file' && $request->hasFile('material_file')) {
            $file = $request->file('material_file');
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('materials', $fileName, 'public');

            if ($material->id && $material->material_file) {
                Storage::disk('public')->delete('materials/' . $material->material_file);
            }

            $material->material_file = $fileName;
            $material->material_url = null;
        }

        if ($material->material_type === 'url' && $validatedData['material_url']) {
            $material->material_url = $validatedData['material_url'];

            if ($material->id && $material->material_file) {
                Storage::disk('public')->delete('materials/' . $material->material_file);
                $material->material_file = null;
            }

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $thumbnailName = uniqid() . '.' . $thumbnail->getClientOriginalExtension();
                $thumbnail->storeAs('materials', $thumbnailName, 'public');

                if ($material->id && $material->thumbnail) {
                    Storage::disk('public')->delete('materials/' . $material->thumbnail);
                }

                $material->thumbnail = $thumbnailName;
            }
        }

        if (!$material->id) {
            $material->user_id = Auth::id();
        } else {
            $material->is_publish = $validatedData['is_publish'];
        }

        $material->save();

        $data = array(
            'status' => true,
            'message' => 'Materi Edukasi berhasil disimpan.',
        );

        if (!$material->wasRecentlyCreated) {
            $data['previous'] = true;
        }

        return response()->json($data, 200);
    }

    public function destroy($id): RedirectResponse
    {
        $material = EducationalMaterial::find(base64_decode($id));

        if (!$material) {
            return redirect()->route('materials')->with('error', 'Materi Edukasi tidak ditemukan.');
        }

        if ($material->material_file) {
            $materialPath = 'materials/' . $material->material_file;
            if (Storage::disk('public')->exists($materialPath)) {
                Storage::disk('public')->delete($materialPath);
            }
        }

        if ($material->thumbnail) {
            $thumbnailPath = 'materials/' . $material->thumbnail;
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
        }

        $material->delete();

        return redirect()->route('materials')->with('success', 'Materi Edukasi berhasil dihapus.');
    }
}

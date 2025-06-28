<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducationalMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EducationController extends Controller
{
    public function index()
    {
        // Ambil semua materi yang dipublish, urut dari yang terbaru
        $materials = EducationalMaterial::where('is_publish', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Jika tidak ada data, kirim response kosong
        if ($materials->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada materi edukasi yang tersedia.',
                'data'    => []
            ], 404);
        }

        // Format data untuk dikirim ke frontend
        $data = $materials->map(function ($material) {
            return [
                'id'             => $material->id,
                'title_material' => $material->title_material,
                'description'    => $material->description,
                'material_type'  => $material->material_type, // image atau video

                // URL file gambar jika tipe 'image'
                'image_url' => $material->material_type === 'image' && $material->image_path
                    ? asset('storage/images/' . $material->image_path)
                    : null,

                // URL video jika tipe 'video'
                'video_url' => $material->material_type === 'video'
                    ? $material->video_url
                    : null,

                'is_publish' => $material->is_publish,
                'created_at' => $material->created_at->format('Y-m-d'),
            ];
        });

        // Kirim response sukses
        return response()->json([
            'message' => 'Daftar materi edukasi berhasil diambil.',
            'data'    => $data
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Cegah user yang tidak memiliki hak akses (misal pasien)
        if ($user->user_type_id === 2) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menambahkan atau memperbarui materi edukasi.'
            ], 403);
        }

        // Validasi data masukan
        $request->validate([
            'id'             => 'nullable|exists:educational_materials,id',
            'title_material' => 'required|string|max:255',
            'description'    => 'nullable|string',
            'material_type'  => 'required|in:image,video',
            'image_file'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'video_url'      => 'nullable|url',
        ], [
            'id.exists'               => 'Materi edukasi tidak ditemukan.',
            'title_material.required' => 'Judul materi wajib diisi.',
            'material_type.required'  => 'Jenis materi wajib dipilih.',
            'material_type.in'        => 'Jenis materi harus image atau video.',
            'video_url.url'           => 'URL video tidak valid.',
            'image_file.image'        => 'File harus berupa gambar.',
            'image_file.mimes'        => 'Format gambar harus JPG, JPEG, atau PNG.',
            'image_file.max'          => 'Ukuran gambar maksimal 2 MB.',
        ]);

        $isUpdate = $request->filled('id');
        $material = $isUpdate
            ? EducationalMaterial::findOrFail($request->id)
            : new EducationalMaterial();

        // Data dasar untuk disimpan
        $data = [
            'title_material' => $request->title_material,
            'description'    => $request->description,
            'material_type'  => $request->material_type,
            'video_url'      => $request->material_type === 'video' ? $request->video_url : null,
            'is_publish'     => true,
            'created_by'     => $user->id,
        ];

        // Upload gambar jika tipe = image
        if ($request->hasFile('image_file') && $request->material_type === 'image') {
            // Hapus gambar lama jika update
            if ($isUpdate && $material->image_path) {
                $filePath = 'images/' . $material->image_path;
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            // Simpan gambar baru
            $fileName = Str::random(20) . '.' . $request->file('image_file')->getClientOriginalExtension();
            $request->file('image_file')->storeAs('images', $fileName, 'public');
            $data['image_path'] = $fileName;
            $data['video_url'] = null; // kosongkan jika sebelumnya adalah video
        }

        // Simpan data ke database
        $material->fill($data)->save();

        return response()->json([
            'message' => $isUpdate
                ? 'Materi edukasi berhasil diperbarui.'
                : 'Materi edukasi berhasil ditambahkan.',
            'data'    => $material
        ], $isUpdate ? 200 : 201);
    }

    public function show($id)
    {
        // Cari materi berdasarkan ID
        $material = EducationalMaterial::find($id);

        // Jika tidak ditemukan, kirim respons 404
        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.',
                'data'    => null
            ], 404);
        }

        // Format data untuk response
        $data = [
            'id'             => $material->id,
            'title_material' => $material->title_material,
            'description'    => $material->description,
            'material_type'  => $material->material_type,

            // URL file gambar jika tipe 'image'
            'image_url' => $material->material_type === 'image' && $material->image_path
                ? asset('storage/images/' . $material->image_path)
                : null,

            // URL video jika tipe 'video'
            'video_url' => $material->material_type === 'video'
                ? $material->video_url
                : null,

            'is_publish'     => $material->is_publish,
            'created_by'     => $material->created_by,
            'created_at'     => $material->created_at->format('Y-m-d H:i:s'),
            'updated_at'     => $material->updated_at->format('Y-m-d H:i:s'),
        ];

        return response()->json([
            'message' => 'Detail materi edukasi berhasil diambil.',
            'data'    => $data
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        // Cek hak akses (misal user_type_id 2 = pasien)
        if ($user->user_type_id === 2) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menghapus materi edukasi.'
            ], 403);
        }

        // Ambil materi
        $material = EducationalMaterial::find($id);
        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.'
            ], 404);
        }

        // Hapus file gambar jika tipe image dan file-nya ada
        if (
            $material->material_type === 'image' &&
            $material->image_path &&
            Storage::disk('public')->exists('images/' . $material->image_path)
        ) {
            Storage::disk('public')->delete('images/' . $material->image_path);
        }

        // Hapus dari database
        $material->delete();

        return response()->json([
            'message' => 'Materi edukasi berhasil dihapus.'
        ]);
    }

    public function togglePublish($id)
    {
        $user = Auth::user();

        // Cek hak akses
        if ($user->user_type_id == 4) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk mengubah status publikasi.'
            ], 403);
        }

        // Cek apakah materi ada
        $material = EducationalMaterial::find($id);
        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.'
            ], 404);
        }

        // Ubah status publikasi
        $material->is_publish = !$material->is_publish;
        $material->save();

        return response()->json([
            'message'    => 'Status publikasi berhasil diperbarui.',
            'is_publish' => $material->is_publish
        ]);
    }
}

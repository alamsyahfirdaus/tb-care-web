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
        $user = Auth::user();
        $query = EducationalMaterial::orderBy('created_at', 'desc');

        // Pasien (user_type_id == 2) hanya boleh melihat materi yang dipublikasikan
        if ($user && $user->user_type_id === 2) {
            $query->where('is_publish', 1);
        }

        $materials = $query->get();

        // Jika tidak ada data, kirim response kosong dengan status 200
        if ($materials->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada materi edukasi yang tersedia.',
                'data'    => []
            ], 200);
        }

        // Format data untuk dikirim ke frontend
        $data = $materials->map(function ($material) {
            return [
                'id'             => $material->id,
                'title_material' => $material->title_material,
                'description'    => $material->description,
                'material_type'  => $material->material_type, // image atau video

                // URL file gambar jika tipe 'image'
                'photo' => $material->material_type === 'image' && $material->image_path
                    ? $material->image_path
                    : null,

                // URL video jika tipe 'video'
                'video_url' => $material->material_type === 'video'
                    ? $material->video_url
                    : null,

                'is_publish' => $material->is_publish,
                'created_by' => $material->created_by,
                'created_at' => $material->created_at->format('Y-m-d H:i:s'),
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

        $wasPublished = $isUpdate ? ($material->is_publish == 1) : false;

        $isPublishInput = 1;
        if ($request->has('is_publish')) {
            $val = $request->is_publish;
            $isPublishInput = ($val == '1' || $val == 'true' || $val === true) ? 1 : 0;
        }

        // Data dasar untuk disimpan
        $data = [
            'title_material' => $request->title_material,
            'description'    => $request->description,
            'material_type'  => $request->material_type,
            'video_url'      => $request->material_type === 'video' ? $request->video_url : null,
            'is_publish'     => $isPublishInput,
            'created_by'     => $user->id,
        ];

        if ($request->material_type === 'video') {
            $data['image_path'] = null;
        }

        // Upload gambar jika tipe = image (DISAMAKAN DENGAN UPLOAD MINUM OBAT)
        if ($request->material_type === 'image' && $request->hasFile('image_file')) {

            // 1. Hapus gambar lama jika update
            if ($isUpdate && $material->image_path) {
                $oldPath = public_path('images/' . $material->image_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // 2. Pastikan folder public/images ada
            $destinationPath = public_path('images');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // 3. Generate nama file (xxx.jpg)
            $fileName = Str::random(20) . '.' . $request->file('image_file')->getClientOriginalExtension();

            // 4. SIMPAN KE public/images (INI YANG BENAR)
            $request->file('image_file')->move($destinationPath, $fileName);

            // 5. Simpan NAMA FILE SAJA ke database
            $data['image_path'] = $fileName;
            $data['video_url']  = null;
        }

        // Simpan data ke database
        $material->fill($data)->save();

        // Kirim notifikasi jika baru dipublikasikan
        if ($material->is_publish == 1 && !$wasPublished) {
            $this->sendNewMaterialNotification($material);
        }

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

        // Pasien (user_type_id == 2) tidak boleh melihat materi draft (is_publish = 0)
        $user = Auth::user();
        if ($user && $user->user_type_id === 2 && $material->is_publish !== 1) {
            return response()->json([
                'message' => 'Materi edukasi ini belum dipublikasikan.',
                'data'    => null
            ], 403);
        }

        // Format data untuk response
        $data = [
            'id'             => $material->id,
            'title_material' => $material->title_material,
            'description'    => $material->description,
            'material_type'  => $material->material_type,

            // URL file gambar jika tipe 'image'
            'photo' => $material->material_type === 'image' && $material->image_path
                ? $material->image_path
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

        // 1. Cek hak akses (user_type_id 2 = pasien)
        if ($user->user_type_id === 2) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menghapus materi edukasi.'
            ], 403);
        }

        // 2. Ambil materi
        $material = EducationalMaterial::find($id);
        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.'
            ], 404);
        }

        // 3. Hapus file gambar jika tipe image dan file tersedia
        if ($material->material_type === 'image' && !empty($material->image_path)) {

            $filePath = public_path('images/' . $material->image_path);

            // Pastikan file benar-benar ada sebelum dihapus
            if (is_file($filePath)) {
                try {
                    unlink($filePath);
                } catch (\Throwable $e) {
                    // Optional: logging jika diperlukan
                    // \Log::error('Gagal menghapus file edukasi: ' . $e->getMessage());
                }
            }
        }

        // 4. Hapus data dari database
        $material->delete();

        // 5. Response sukses
        return response()->json([
            'message' => 'Materi edukasi berhasil dihapus.'
        ], 200);
    }

    public function togglePublish($id)
    {
        $user = Auth::user();

        // Cek hak akses (Pasien = 2, Kader = 4 tidak diizinkan mengubah status publikasi)
        if ($user->user_type_id == 2 || $user->user_type_id == 4) {
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

        $wasPublished = $material->is_publish == 1;

        // Ubah status publikasi
        $material->is_publish = !$material->is_publish;
        $material->save();

        if ($material->is_publish == 1 && !$wasPublished) {
            $this->sendNewMaterialNotification($material);
        }

        return response()->json([
            'message'    => 'Status publikasi berhasil diperbarui.',
            'is_publish' => $material->is_publish
        ]);
    }

    private function sendNewMaterialNotification($material)
    {
        $tokens = \App\Models\User::whereNotNull('fcm_token')
            ->where('user_type_id', 2) // Kirim ke pasien saja
            ->pluck('fcm_token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $title = 'Materi Edukasi Baru';
        $body = $material->title_material;
        $data = [
            'material_id' => (string) $material->id,
            'type' => 'education',
        ];

        \App\Services\FcmService::sendNotification($tokens, $title, $body, $data);
    }
}

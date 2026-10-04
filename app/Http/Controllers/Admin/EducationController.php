<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalMaterial;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        $typeFilter   = $request->query('type');
        $statusFilter = $request->query('status');
        $keyword      = $request->query('q');

        $query = EducationalMaterial::with('author');

        if ($typeFilter) {
            $query->where('material_type', $typeFilter);
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('is_publish', $statusFilter == '1');
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('title_material', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $materials = $query->orderByDesc('id')->get();

        // Stats
        $totalAll     = EducationalMaterial::count();
        $totalVideo   = EducationalMaterial::where('material_type', 'video')->count();
        $totalImage   = EducationalMaterial::where('material_type', 'image')->count();
        $totalPublish = EducationalMaterial::where('is_publish', 1)->count();

        return view('admin.education.index', compact(
            'materials', 'typeFilter', 'statusFilter', 'keyword',
            'totalAll', 'totalVideo', 'totalImage', 'totalPublish'
        ))->with([
            'title'        => 'Materi Edukasi TB Care',
            'pageTitle'    => 'Pusat Materi Edukasi & Informasi TB',
            'pageSubtitle' => 'Kelola modul video edukasi, poster, infografis, dan panduan kesehatan masyarakat.'
        ]);
    }

    public function show($id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::with('author')->findOrFail($id);

        return view('admin.education.show', compact('material'))->with([
            'title'        => 'Detail Edukasi: ' . $material->title_material,
            'pageTitle'    => 'Pratinjau Materi Edukasi',
            'pageSubtitle' => 'Tinjau tampilan materi video atau poster infografis sebelum/sesudah dipublikasikan.'
        ]);
    }

    public function create()
    {
        return view('admin.education.form', [
            'material'     => new EducationalMaterial(),
            'isEdit'       => false,
            'title'        => 'Tambah Materi Edukasi',
            'pageTitle'    => 'Buat Konten Edukasi Baru',
            'pageSubtitle' => 'Publikasikan video YouTube, poster kesehatan, atau infografis TB.'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title_material' => 'required|string|max:255',
            'material_type'  => 'required|in:image,video',
            'video_url'      => 'nullable|required_if:material_type,video|url',
            'image'          => 'nullable|required_if:material_type,image|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description'    => 'nullable|string',
            'is_publish'     => 'required|boolean',
        ], [
            'title_material.required' => 'Judul materi wajib diisi.',
            'video_url.required_if'   => 'URL video wajib diisi untuk tipe materi Video.',
            'image.required_if'       => 'File gambar wajib diunggah untuk tipe materi Poster/Gambar.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('education', 'public');
        }

        $material = EducationalMaterial::create([
            'title_material' => $request->title_material,
            'material_type'  => $request->material_type,
            'video_url'      => $request->material_type == 'video' ? $request->video_url : null,
            'image_path'     => $imagePath,
            'description'    => $request->description,
            'is_publish'     => $request->is_publish,
            'created_by'     => Auth::id() ?? 1,
        ]);

        ActivityLog::log('Tambah Edukasi', 'Edukasi', "Menambahkan materi edukasi: {$material->title_material}.");

        return redirect()->route('admin.education.show', $material)->with('success', 'Materi edukasi berhasil diterbitkan.');
    }

    public function edit($id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::findOrFail($id);

        return view('admin.education.form', [
            'material'     => $material,
            'isEdit'       => true,
            'title'        => 'Edit Materi: ' . $material->title_material,
            'pageTitle'    => 'Edit Konten Edukasi',
            'pageSubtitle' => 'Perbarui data teks materi, tautan video, atau ganti poster.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::findOrFail($id);

        $request->validate([
            'title_material' => 'required|string|max:255',
            'material_type'  => 'required|in:image,video',
            'video_url'      => 'nullable|required_if:material_type,video|url',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description'    => 'nullable|string',
            'is_publish'     => 'required|boolean',
        ]);

        $imagePath = $material->image_path;
        if ($request->hasFile('image')) {
            // Delete old file if exists in storage
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('education', 'public');
        }

        $material->update([
            'title_material' => $request->title_material,
            'material_type'  => $request->material_type,
            'video_url'      => $request->material_type == 'video' ? $request->video_url : null,
            'image_path'     => $imagePath,
            'description'    => $request->description,
            'is_publish'     => $request->is_publish,
        ]);

        ActivityLog::log('Perbarui Edukasi', 'Edukasi', "Memperbarui materi edukasi {$material->title_material}.");

        return redirect()->route('admin.education.show', $material)->with('success', 'Materi edukasi berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::findOrFail($id);
        $title = $material->title_material;

        if ($material->image_path && Storage::disk('public')->exists($material->image_path)) {
            Storage::disk('public')->delete($material->image_path);
        }

        $material->delete();

        ActivityLog::log('Hapus Edukasi', 'Edukasi', "Menghapus materi edukasi {$title}.");

        return redirect()->route('admin.education.index')->with('success', "Materi '{$title}' berhasil dihapus.");
    }

    public function togglePublish($id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::findOrFail($id);
        $material->is_publish = !$material->is_publish;
        $material->save();

        $statusStr = $material->is_publish ? 'dipublikasikan' : 'diarsipkan (draft)';
        ActivityLog::log('Ubah Status Edukasi', 'Edukasi', "Mengubah status materi {$material->title_material} menjadi {$statusStr}.");

        return back()->with('success', "Status materi edukasi berhasil diubah menjadi {$statusStr}.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EducationalMaterial;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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

    public function form(?string $encryptedId = null)
    {
        $isEdit = false;
        $material = new EducationalMaterial();

        if ($encryptedId) {
            $id = decrypt_id($encryptedId);
            $material = EducationalMaterial::findOrFail($id);
            $isEdit = true;
        }

        return view('admin.education.form', [
            'material'     => $material,
            'isEdit'       => $isEdit,
            'title'        => $isEdit ? ('Edit Materi: ' . $material->title_material) : 'Tambah Materi Edukasi',
            'pageTitle'    => $isEdit ? 'Edit Konten Edukasi' : 'Buat Konten Edukasi Baru',
            'pageSubtitle' => $isEdit ? 'Perbarui data teks materi, tautan video, atau ganti poster.' : 'Publikasikan video YouTube, poster kesehatan, atau infografis TB.',
        ]);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($id)
    {
        return $this->form($id);
    }

    public function save(Request $request, ?string $encryptedId = null)
    {
        $resolvedEncryptedId = $request->input('encrypted_id') ?? $encryptedId;
        $isEdit = !empty($resolvedEncryptedId);
        $material = $isEdit ? EducationalMaterial::findOrFail(decrypt_id($resolvedEncryptedId)) : new EducationalMaterial();

        $request->validate([
            'title_material' => 'required|string|max:255',
            'material_type'  => 'required|in:image,video',
            'video_url'      => 'nullable|required_if:material_type,video|url',
            'image'          => [
                'nullable',
                Rule::requiredIf(!$isEdit && $request->material_type === 'image'),
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120'
            ],
            'description'    => 'nullable|string',
            'is_publish'     => 'required|boolean',
        ], [
            'title_material.required' => 'Judul materi wajib diisi.',
            'video_url.required_if'   => 'URL video wajib diisi untuk tipe materi Video.',
            'image.required_if'       => 'File gambar wajib diunggah untuk tipe materi Poster/Gambar.',
        ]);

        return DB::transaction(function () use ($request, $material, $isEdit) {
            $imagePath = $material->image_path;

            if ($request->hasFile('image')) {
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = $request->file('image')->store('education', 'public');
            }

            if (!$isEdit) {
                $material->fill([
                    'title_material' => $request->title_material,
                    'material_type'  => $request->material_type,
                    'video_url'      => $request->material_type == 'video' ? $request->video_url : null,
                    'image_path'     => $imagePath,
                    'description'    => $request->description,
                    'is_publish'     => $request->is_publish,
                    'created_by'     => Auth::id() ?? 1,
                ])->save();

                // Trigger notifikasi pasien jika materi dipublikasikan
                if ($material->is_publish) {
                    $material->sendPublishNotification();
                }

                ActivityLog::log('Tambah Edukasi', 'Edukasi', "Menambahkan materi edukasi: {$material->title_material}.");
                $message = 'Materi edukasi berhasil diterbitkan.';
            } else {
                $material->update([
                    'title_material' => $request->title_material,
                    'material_type'  => $request->material_type,
                    'video_url'      => $request->material_type == 'video' ? $request->video_url : null,
                    'image_path'     => $imagePath,
                    'description'    => $request->description,
                    'is_publish'     => $request->is_publish,
                ]);

                // Trigger notifikasi jika status diubah dari draft menjadi publish
                if ($material->is_publish) {
                    $material->sendPublishNotification();
                }

                ActivityLog::log('Perbarui Edukasi', 'Edukasi', "Memperbarui materi edukasi {$material->title_material}.");
                $message = 'Materi edukasi berhasil diperbarui.';
            }

            return redirect()->route('admin.education.show', $material)->with('success', $message);
        });
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::findOrFail($id);
        $title = $material->title_material;

        DB::transaction(function () use ($material) {
            if ($material->image_path && Storage::disk('public')->exists($material->image_path)) {
                Storage::disk('public')->delete($material->image_path);
            }
            $material->delete();
        });

        ActivityLog::log('Hapus Edukasi', 'Edukasi', "Menghapus materi edukasi {$title}.");

        return redirect()->route('admin.education.index')->with('success', "Materi '{$title}' berhasil dihapus.");
    }

    public function togglePublish($id)
    {
        $id = decrypt_id($id);
        $material = EducationalMaterial::findOrFail($id);
        $material->is_publish = !$material->is_publish;
        $material->save();

        if ($material->is_publish) {
            $material->sendPublishNotification();
        }

        $statusStr = $material->is_publish ? 'dipublikasikan' : 'diarsipkan (draft)';
        ActivityLog::log('Ubah Status Edukasi', 'Edukasi', "Mengubah status materi {$material->title_material} menjadi {$statusStr}.");

        return back()->with('success', "Status materi edukasi berhasil diubah menjadi {$statusStr}.");
    }
}

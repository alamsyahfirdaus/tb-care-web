<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EducationalMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EducationController extends Controller
{
    /**
     * Menampilkan daftar materi edukasi dengan RBAC dan aturan kepemilikan.
     *
     * Ketentuan:
     * 1. Seluruh materi dengan status published (is_publish = 1) ditampilkan kepada
     *    semua pengguna (Admin, Petugas, Kader, Pasien) TANPA memandang siapa pembuatnya.
     * 2. Draft hanya dapat dilihat oleh Admin (seluruh draft) dan Petugas (hanya draft miliknya).
     * 3. Pasien dan Kader dilarang mengakses draft.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = EducationalMaterial::with('author')->orderBy('created_at', 'desc');

        $statusFilter = $request->query('status'); // 'published', 'draft', or null

        // Cek wewenang pengguna
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user && $user->user_type_id === 1);
        $isPetugas = $user && method_exists($user, 'isPetugas') ? $user->isPetugas() : ($user && $user->user_type_id === 3 && optional($user->officer)->officer_type_id != 4);
        $canManage = $isAdmin || $isPetugas;

        if ($statusFilter === 'draft') {
            // Pasien dan Kader tidak memiliki izin melihat draft
            if (!$canManage) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk melihat draft materi edukasi.'
                ], 403);
            }

            // Admin melihat semua draft; Petugas hanya melihat draft miliknya
            if ($isAdmin) {
                $query->where('is_publish', 0);
            } else {
                $query->where('is_publish', 0)->where('created_by', $user->id);
            }
        } elseif ($statusFilter === 'published') {
            // Semua role dapat melihat semua materi published tanpa filter pemilik
            $query->where('is_publish', 1);
        } else {
            // Default tanpa filter query 'status'
            if (!$canManage) {
                // Pasien dan Kader HANYA boleh melihat materi published
                $query->where('is_publish', 1);
            } elseif ($isPetugas && !$isAdmin) {
                // Petugas melihat seluruh published + draft miliknya sendiri
                $query->where(function ($q) use ($user) {
                    $q->where('is_publish', 1)
                      ->orWhere(function ($sub) use ($user) {
                          $sub->where('is_publish', 0)->where('created_by', $user->id);
                      });
                });
            }
            // Admin melihat seluruh materi tanpa pembatasan
        }

        if ($request->has('limit')) {
            $query->take((int) $request->query('limit'));
        }

        $materials = $query->get();

        if ($materials->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada materi edukasi yang tersedia.',
                'data'    => []
            ], 200);
        }

        $currentUserId = $user ? $user->id : null;
        $readMaterialIds = $currentUserId
            ? \App\Models\EducationalMaterialRead::where('user_id', $currentUserId)->pluck('educational_material_id')->flip()->all()
            : [];

        // Format data untuk dikirim ke frontend dengan informasi hak aksi
        $data = $materials->map(function ($material) use ($isAdmin, $isPetugas, $currentUserId, $readMaterialIds) {
            $isOwner = $currentUserId && ($material->created_by == $currentUserId);
            $canEdit = $isAdmin || ($isPetugas && $isOwner);
            $canDelete = $isAdmin || ($isPetugas && $isOwner);

            $authorName = $material->author ? $material->author->name : 'Petugas TB Care';
            $isRead = isset($readMaterialIds[$material->id]);
            $publishedAt = $material->is_publish
                ? ($material->updated_at ? $material->updated_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : ($material->created_at ? $material->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null))
                : null;

            return [
                'id'             => $material->id,
                'title_material' => $material->title_material,
                'description'    => $material->description,
                'material_type'  => $material->material_type, // 'image' atau 'video'

                'photo' => $material->material_type === 'image'
                    ? ($material->image_path ?: null)
                    : null,

                'video_url' => $material->material_type === 'video'
                    ? $material->video_url
                    : null,

                'is_publish'   => (int) ($material->is_publish ? 1 : 0),
                'is_read'      => $isRead,
                'published_at' => $publishedAt,
                'created_by'   => $material->created_by,
                'author_name'  => $authorName,
                'can_edit'     => $canEdit,
                'can_delete'   => $canDelete,
                'created_at'   => $material->created_at ? $material->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'message' => 'Daftar materi edukasi berhasil diambil.',
            'data'    => $data
        ]);
    }

    /**
     * Tambah atau perbarui materi edukasi dengan verifikasi RBAC dan kepemilikan.
     *
     * Aturan:
     * - Admin dan Petugas berhak menambah materi baru.
     * - Admin berhak mengedit materi siapapun (created_by TIDAK boleh ditimpa).
     * - Petugas HANYA berhak mengedit materi miliknya sendiri.
     * - Kader dan Pasien dilarang (403 Forbidden).
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user && $user->user_type_id === 1);
        $isPetugas = $user && method_exists($user, 'isPetugas') ? $user->isPetugas() : ($user && $user->user_type_id === 3 && optional($user->officer)->officer_type_id != 4);

        if (!$isAdmin && !$isPetugas) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menambahkan atau memperbarui materi edukasi.'
            ], 403);
        }

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

        // Validasi kepemilikan saat update:
        // Petugas hanya boleh mengedit materi buatannya sendiri
        if ($isUpdate) {
            if (!$isAdmin && $material->created_by != $user->id) {
                return response()->json([
                    'message' => 'Anda hanya berhak mengedit materi edukasi yang Anda buat sendiri.'
                ], 403);
            }
        }

        $wasPublished = $isUpdate ? ($material->is_publish == 1) : false;

        $isPublishInput = 1;
        if ($request->has('is_publish')) {
            $val = $request->is_publish;
            $isPublishInput = ($val == '1' || $val == 'true' || $val === true) ? 1 : 0;
        }

        $data = [
            'title_material' => $request->title_material,
            'description'    => $request->description,
            'material_type'  => $request->material_type,
            'video_url'      => $request->material_type === 'video' ? $request->video_url : null,
            'is_publish'     => $isPublishInput,
        ];

        // PENTING: Saat update oleh Admin atau Petugas, created_by TIDAK BOLEH ditimpa!
        // created_by hanya diset ketika materi baru dibuat.
        if (!$isUpdate) {
            $data['created_by'] = $user->id;
        }

        if ($request->material_type === 'video') {
            $data['image_path'] = null;
        }

        // Upload gambar jika tipe = image
        if ($request->material_type === 'image' && $request->hasFile('image_file')) {
            if ($isUpdate && $material->image_path) {
                $oldPath = public_path('images/' . $material->image_path);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $destinationPath = public_path('images');
            if (!is_dir($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fileName = Str::random(20) . '.' . $request->file('image_file')->getClientOriginalExtension();
            $request->file('image_file')->move($destinationPath, $fileName);

            $data['image_path'] = $fileName;
            $data['video_url']  = null;
        }

        $material->fill($data)->save();

        // Kirim notifikasi jika baru dipublikasikan (idempotent)
        if ($material->is_publish == 1 && !$wasPublished) {
            $material->sendPublishNotification();
        }

        return response()->json([
            'message' => $isUpdate
                ? 'Materi edukasi berhasil diperbarui.'
                : 'Materi edukasi berhasil ditambahkan.',
            'data'    => $material
        ], $isUpdate ? 200 : 201);
    }

    /**
     * Menampilkan detail materi edukasi.
     */
    public function show($id)
    {
        $material = EducationalMaterial::with('author')->find($id);

        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.',
                'data'    => null
            ], 404);
        }

        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user && $user->user_type_id === 1);
        $isPetugas = $user && method_exists($user, 'isPetugas') ? $user->isPetugas() : ($user && $user->user_type_id === 3 && optional($user->officer)->officer_type_id != 4);

        // Jika materi belum dipublikasikan (draft), hanya Admin dan pemilik materi yang boleh melihat
        if (!$material->is_publish) {
            $isOwner = $user && ($material->created_by == $user->id);
            if (!$isAdmin && (!$isPetugas || !$isOwner)) {
                return response()->json([
                    'message' => 'Materi edukasi ini belum dipublikasikan.',
                    'data'    => null
                ], 403);
            }
        }

        $isOwner = $user && ($material->created_by == $user->id);
        $canEdit = $isAdmin || ($isPetugas && $isOwner);
        $canDelete = $isAdmin || ($isPetugas && $isOwner);

        // Tandai status materi edukasi telah diakses / dibaca oleh pengguna ini (tersimpan di database)
        $isRead = false;
        if ($user) {
            try {
                \App\Models\EducationalMaterialRead::firstOrCreate(
                    [
                        'user_id'                 => $user->id,
                        'educational_material_id' => $material->id,
                    ],
                    [
                        'read_at' => now(),
                    ]
                );
                $isRead = true;
            } catch (\Throwable $readEx) {
                \Illuminate\Support\Facades\Log::warning('Gagal mencatat EducationalMaterialRead: ' . $readEx->getMessage());
            }

            // Tandai notifikasi terkait materi edukasi ini sebagai sudah dibaca (read_at terisi)
            try {
                $user->unreadNotifications
                    ->filter(function ($n) use ($material) {
                        $edId = $n->data['education_id'] ?? $n->data['material_id'] ?? null;
                        return (int) $edId === (int) $material->id;
                    })
                    ->markAsRead();
            } catch (\Throwable $notifEx) {
                \Illuminate\Support\Facades\Log::warning('Gagal menandai notifikasi edukasi sebagai dibaca: ' . $notifEx->getMessage());
            }
        }

        $authorName = $material->author ? $material->author->name : 'Petugas TB Care';
        $publishedAt = $material->is_publish
            ? ($material->updated_at ? $material->updated_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : ($material->created_at ? $material->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i:s') : null))
            : null;

        $data = [
            'id'             => $material->id,
            'title_material' => $material->title_material,
            'description'    => $material->description,
            'material_type'  => $material->material_type,

            'photo' => $material->material_type === 'image'
                ? ($material->image_path ?: null)
                : null,

            'video_url' => $material->material_type === 'video'
                ? $material->video_url
                : null,

            'is_publish'   => (int) ($material->is_publish ? 1 : 0),
            'is_read'      => $isRead,
            'published_at' => $publishedAt,
            'created_by'   => $material->created_by,
            'author_name'  => $authorName,
            'can_edit'     => $canEdit,
            'can_delete'   => $canDelete,
            'created_at'   => $material->created_at ? $material->created_at->format('Y-m-d H:i:s') : null,
            'updated_at'   => $material->updated_at ? $material->updated_at->format('Y-m-d H:i:s') : null,
        ];

        return response()->json([
            'message' => 'Detail materi edukasi berhasil diambil.',
            'data'    => $data
        ]);
    }

    /**
     * Menghapus materi edukasi dengan pemeriksaan hak kepemilikan.
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user && $user->user_type_id === 1);
        $isPetugas = $user && method_exists($user, 'isPetugas') ? $user->isPetugas() : ($user && $user->user_type_id === 3 && optional($user->officer)->officer_type_id != 4);

        if (!$isAdmin && !$isPetugas) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk menghapus materi edukasi.'
            ], 403);
        }

        $material = EducationalMaterial::find($id);
        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.'
            ], 404);
        }

        // Petugas hanya boleh menghapus materi buatannya sendiri
        if (!$isAdmin && $material->created_by != $user->id) {
            return response()->json([
                'message' => 'Anda hanya berhak menghapus materi edukasi yang Anda buat sendiri.'
            ], 403);
        }

        if ($material->material_type === 'image' && !empty($material->image_path)) {
            $filePath = public_path('images/' . $material->image_path);
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        $material->delete();

        return response()->json([
            'message' => 'Materi edukasi berhasil dihapus.'
        ], 200);
    }

    /**
     * Mengubah status publikasi (toggle publish).
     */
    public function togglePublish($id)
    {
        $user = Auth::user();

        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : ($user && $user->user_type_id === 1);
        $isPetugas = $user && method_exists($user, 'isPetugas') ? $user->isPetugas() : ($user && $user->user_type_id === 3 && optional($user->officer)->officer_type_id != 4);

        if (!$isAdmin && !$isPetugas) {
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk mengubah status publikasi materi edukasi.'
            ], 403);
        }

        $material = EducationalMaterial::find($id);
        if (!$material) {
            return response()->json([
                'message' => 'Materi edukasi tidak ditemukan.'
            ], 404);
        }

        // Petugas hanya boleh mengubah status publikasi materi miliknya sendiri
        if (!$isAdmin && $material->created_by != $user->id) {
            return response()->json([
                'message' => 'Anda hanya berhak mengubah status publikasi materi edukasi yang Anda buat sendiri.'
            ], 403);
        }

        $wasPublished = ($material->is_publish == 1);
        $material->is_publish = !$material->is_publish;
        $material->save();

        if ($material->is_publish == 1 && !$wasPublished) {
            $material->sendPublishNotification();
        }

        return response()->json([
            'message'    => 'Status publikasi berhasil diperbarui.',
            'is_publish' => (int) ($material->is_publish ? 1 : 0)
        ]);
    }
}

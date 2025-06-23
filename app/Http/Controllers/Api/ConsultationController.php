<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ConsultationReply;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConsultationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Ambil konsultasi publik, dikirim oleh user, atau ditujukan ke user
        $consultations = Consultation::with(['user', 'recipient', 'replies.user'])
            ->where(function ($query) use ($user) {
                $query->whereNull('recipient_id')
                    ->orWhere('user_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Format hasil response
        $data = $consultations->map(function ($item) {
            return [
                'id'             => $item->id,
                'title'          => $item->title,
                'message'        => $item->message,
                'is_answered'    => $item->is_answered,
                'attachment'     => $item->attachment
                    ? asset('storage/images/' . $item->attachment)
                    : null,
                'created_at'     => $item->created_at->format('Y-m-d H:i'),

                // Pengirim
                'user_id'        => $item->user_id,
                'sender_name'    => $item->user->name,

                // Penerima
                'recipient_id'   => $item->recipient?->id,
                'recipient_name' => $item->recipient?->name,

                // Balasan
                'replies' => $item->replies->map(function ($reply) {
                    return [
                        'id'          => $reply->id,
                        'message'     => $reply->message,
                        'attachment'  => $reply->attachment
                            ? asset('storage/images/' . $reply->attachment)
                            : null,
                        'is_read'     => $reply->is_read,
                        'created_at'  => $reply->created_at->format('Y-m-d H:i'),
                        'user_id'     => $reply->user->id,
                        'sender_name' => $reply->user->name,
                    ];
                })
            ];
        });

        return response()->json([
            'message' => 'Daftar konsultasi berhasil diambil.',
            'data'    => $data
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'id'           => 'nullable|exists:consultations,id',
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'recipient_id' => 'nullable|exists:users,id',
            'attachment'   => 'nullable|image|mimes:jpeg,png,jpg,gif,bmp,webp|max:2048',
        ], [
            'id.exists'            => 'Data konsultasi tidak ditemukan.',
            'title.required'       => 'Judul konsultasi wajib diisi.',
            'message.required'     => 'Pesan konsultasi wajib diisi.',
            'attachment.image'     => 'Lampiran harus berupa gambar.',
            'attachment.mimes'     => 'Format gambar yang diperbolehkan: jpeg, png, jpg, gif, bmp, webp.',
            'attachment.max'       => 'Ukuran lampiran maksimal 2MB.',
        ]);

        // Ambil atau buat objek konsultasi
        $consultation = $request->filled('id')
            ? Consultation::findOrFail($request->id)
            : new Consultation();

        $consultation->user_id      = Auth::id();
        $consultation->recipient_id = $request->recipient_id;
        $consultation->title        = $request->title;
        $consultation->message      = $request->message;

        // Simpan lampiran jika ada
        if ($request->hasFile('attachment')) {
            $fileName = Str::random(20) . '.' . $request->file('attachment')->getClientOriginalExtension();
            $request->file('attachment')->storeAs('images', $fileName, 'public');
            $consultation->attachment = $fileName;
        }

        // Jika buat baru, tandai belum dijawab
        if (!$request->filled('id')) {
            $consultation->is_answered = false;
        }

        $consultation->save();

        return response()->json([
            'message' => $request->filled('id')
                ? 'Konsultasi berhasil diperbarui.'
                : 'Konsultasi berhasil dikirim.',
            'data'    => $consultation
        ], $request->filled('id') ? 200 : 201);
    }

    public function destroy($id)
    {
        $consultation = Consultation::find($id);

        if (!$consultation) {
            return response()->json([
                'message' => 'Data konsultasi tidak ditemukan.'
            ], 404);
        }

        // Hapus file lampiran jika ada
        if ($consultation->attachment && Storage::disk('public')->exists('images/' . $consultation->attachment)) {
            Storage::disk('public')->delete('images/' . $consultation->attachment);
        }

        $consultation->delete();

        return response()->json([
            'message' => 'Konsultasi berhasil dihapus.'
        ]);
    }

    public function saveReply(Request $request)
    {
        // Validasi input balasan
        $request->validate([
            'reply_id'        => 'nullable|exists:consultation_replies,id',
            'consultation_id' => 'required|exists:consultations,id',
            'message'         => 'required|string',
            'attachment'      => 'nullable|image|mimes:jpeg,png,jpg,gif,bmp,webp|max:2048',
        ], [
            'reply_id.exists'        => 'Data balasan tidak ditemukan.',
            'consultation_id.exists' => 'Konsultasi tidak ditemukan.',
            'message.required'       => 'Pesan balasan wajib diisi.',
            'attachment.image'       => 'Lampiran harus berupa gambar.',
            'attachment.mimes'       => 'Format yang diperbolehkan: jpeg, png, jpg, gif, bmp, webp.',
            'attachment.max'         => 'Ukuran lampiran maksimal 2MB.',
        ]);

        $consultation = Consultation::find($request->consultation_id);

        $reply = $request->filled('reply_id')
            ? ConsultationReply::findOrFail($request->reply_id)
            : new ConsultationReply();

        $reply->consultation_id = $consultation->id;
        $reply->user_id         = Auth::id();
        $reply->message         = $request->message;

        // Upload lampiran jika ada
        if ($request->hasFile('attachment')) {
            $fileName = Str::random(20) . '.' . $request->file('attachment')->getClientOriginalExtension();
            $request->file('attachment')->storeAs('images', $fileName, 'public');
            $reply->attachment = $fileName;
        }

        if (!$request->filled('reply_id')) {
            $reply->is_read = false;
        }

        $reply->save();

        // Tandai konsultasi sebagai sudah dijawab
        $consultation->is_answered = true;
        $consultation->save();

        return response()->json([
            'message' => $request->filled('reply_id')
                ? 'Balasan berhasil diperbarui.'
                : 'Balasan berhasil dikirim.',
            'data'    => $reply
        ], $request->filled('reply_id') ? 200 : 201);
    }

    public function deleteReply($id)
    {
        $reply = ConsultationReply::find($id);

        if (!$reply) {
            return response()->json([
                'message' => 'Data balasan tidak ditemukan.'
            ], 404);
        }

        // Hapus lampiran jika ada
        if ($reply->attachment && Storage::disk('public')->exists('images/' . $reply->attachment)) {
            Storage::disk('public')->delete('images/' . $reply->attachment);
        }

        $reply->delete();

        return response()->json([
            'message' => 'Balasan berhasil dihapus.'
        ]);
    }

    public function getRecipients()
    {
        $user = Auth::user();

        // Pasien hanya bisa memilih petugas di wilayah puskesmasnya
        if ($user->user_type_id == 2) {
            $patient = Patient::where('user_id', $user->id)->first();

            if (!$patient) {
                return response()->json(['message' => 'Data pasien tidak ditemukan.'], 404);
            }

            // Cari petugas (PJTB/Kader) di Puskesmas yang sama
            $officers = Officer::with('user')
                ->where('puskesmas_id', $patient->puskesmas_id)
                ->get()
                ->map(function ($officer) {
                    return [
                        'id'    => $officer->user->id,
                        'name'  => $officer->user->name,
                        'email' => $officer->user->email,
                        'role'  => 'Petugas',
                    ];
                });

            return response()->json([
                'message' => 'Daftar petugas berhasil diambil.',
                'data'    => $officers
            ]);
        }

        // Petugas hanya bisa memilih pasien di wilayah kerjanya (Puskesmas atau Kecamatan)
        elseif ($user->user_type_id == 3) {
            $officer = Officer::where('user_id', $user->id)->first();

            if (!$officer) {
                return response()->json(['message' => 'Data petugas tidak ditemukan.'], 404);
            }

            $patientsQuery = Patient::with('user');

            if (in_array($officer->officer_type_id, [3, 4])) {
                // Petugas Puskesmas: ambil pasien dari Puskesmas yang sama
                $patientsQuery->where('puskesmas_id', $officer->puskesmas_id);
            } else {
                // Petugas Kab/Kota: ambil pasien dari kecamatan dalam kabupaten yang sama
                $patientsQuery->whereHas('subdistrict', function ($q) use ($officer) {
                    $q->where('district_id', $officer->district_id);
                });
            }

            $patients = $patientsQuery->get()->map(function ($patient) {
                return [
                    'id'    => $patient->user->id,
                    'name'  => $patient->user->name,
                    'email' => $patient->user->email,
                    'role'  => 'Pasien',
                ];
            });

            return response()->json([
                'message' => 'Daftar pasien berhasil diambil.',
                'data'    => $patients
            ]);
        }

        // Admin atau selainnya bisa memilih semua user
        else {
            $users = User::where('id', '!=', $user->id)->get()->map(function ($u) {
                return [
                    'id'    => $u->id,
                    'name'  => $u->name,
                    'email' => $u->email,
                    'role'  => match ($u->user_type_id) {
                        1 => 'Admin',
                        2 => 'Pasien',
                        3 => 'Petugas',
                        default => 'Lainnya',
                    },
                ];
            });

            return response()->json([
                'message' => 'Daftar pengguna berhasil diambil.',
                'data'    => $users
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Pjtb;
use App\Models\User;
use App\Models\Puskesmas;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class PjtbController extends Controller
{

    public function index()
    {
        $data = [
            'title' => 'PJ TB',
            'pjtb' => Pjtb::with(['user', 'puskesmas'])->orderByDesc('id')->get()
        ];

        return view('pjtb-index', $data);
    }

    public function create()
    {
        $data = array(
            'title' => 'PJ TB',
            'users' => User::all(),
            'pkm'   => Puskesmas::all(),
        );

        return view('pjtb-add-edit', $data);
    }

    public function show($id)
    {
        $query = Pjtb::with('user', 'puskesmas.subdistrict.district.province')->find(base64_decode($id));

        if (empty($query->id)) {
            return redirect()->route('pjtb')->with('error', 'Data  PJ TB tidak ditemukan.');
        }
    
        $data = [
            'title' => 'PJ TB',
            'data'  => $query,
        ];

        return view('pjtb-detail', $data);
    }
    

    public function edit($id)
    {
        $query = Pjtb::find(base64_decode($id));

        $data = array(
            'title' => 'PJ TB',
            'data'  => $query,
            'users' => User::all(),
            'pkm'   => Puskesmas::all(),
        );

        return view('pjtb-add-edit', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        // Mencari entri dengan id yang diberikan
        $query = Pjtb::find(base64_decode($id));

        // Jika tidak ada entri dengan id yang diberikan, buat entri baru
        if (!$query) {
            $query = new Pjtb();
        }

        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'puskesmas_id' => ['required', 'exists:puskesmas,id'],
        ], [
            'user_id.required' => 'Bidang PJ TB wajib diisi.',
            'user_id.exists' => 'PJ TB yang dipilih tidak valid.',
            'puskesmas_id.required' => 'Bidang puskesmas wajib diisi.',
            'puskesmas_id.exists' => 'Puskesmas yang dipilih tidak valid.',
        ]);

        // Memeriksa keberadaan pasangan user_id dan puskesmas_id
        if (!$id || !$query->exists()) {
            // Jika sedang membuat entri baru, periksa keberadaan pasangan baru
            $exists = Pjtb::where('user_id', $validatedData['user_id'])
                ->where('puskesmas_id', $validatedData['puskesmas_id'])
                ->exists();

            // Jika pasangan sudah ada, kembalikan respons error
            if ($exists) {
                return response()->json(
                    [
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'user_id' => ['PJ TB telah terdaftar sebelumnya.'],
                            'puskesmas_id' => ['Puskesmas telah memiliki PJ TB sebelumnya.'],
                        ],
                    ],
                    422
                );
            }
        } else {
            // Jika sedang mengedit entri, periksa keberadaan pasangan baru kecuali untuk entri yang sedang diedit
            $exists = Pjtb::where('user_id', $validatedData['user_id'])
                ->where('puskesmas_id', $validatedData['puskesmas_id'])
                ->where('id', '!=', $query->id)
                ->exists();

            // Jika pasangan sudah ada, kembalikan respons error
            if ($exists) {
                return response()->json(
                    [
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'user_id' => ['PJ TB telah terdaftar sebelumnya.'],
                            'puskesmas_id' => ['Puskesmas telah memiliki PJ TB sebelumnya.'],
                        ],
                    ],
                    422
                );
            }
        }

        // Simpan data PJ TB yang telah divalidasi
        $query->user_id = $validatedData['user_id'];
        $query->puskesmas_id = $validatedData['puskesmas_id'];
        $query->save();

        // Kembalikan respons sukses jika penyimpanan berhasil
        return response()->json(
            [
                'success' => true,
                'message' => 'Data PJ TB berhasil disimpan.',
            ],
            200
        );
    }

    public function destroy($id): RedirectResponse
    {
        $pjtb = Pjtb::find(base64_decode($id));
    
        if (!$pjtb) {
            return redirect()->route('pjtb')->with('error', 'Data PJ TB tidak ditemukan.');
        }
    
        $pjtb->delete();
    
        return redirect()->route('pjtb')->with('success', 'Data PJ TB berhasil dihapus.');
    }
}

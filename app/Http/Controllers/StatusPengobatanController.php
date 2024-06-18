<?php

namespace App\Http\Controllers;

use App\Models\StatusPengobatan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StatusPengobatanController extends Controller
{
    public function index()
    {
        $data = [
            'title'  => 'Status Pengobatan',
            'status' => StatusPengobatan::orderByDesc('id')->get()
        ];

        return view('status-index', $data);
    }

    public function save(Request $request): JsonResponse
    {
        $id     = base64_decode($request->id);
        $query  = StatusPengobatan::find($id);

        if (!$query) {
            $query = new StatusPengobatan();
        }

        $validatedData = $request->validate([
            'nama' => [
                'required', 'string', 'max:255',
                isset($query->id) ? Rule::unique('status_pengobatan', 'nama')->ignore($query->id) : 'unique:status_pengobatan,nama'
            ],
        ]);

        $query->nama = $validatedData['nama'];
        $query->save();

        return response()->json([
            'success'   => true,
            'message'   => 'Data Status Pengobatan berhasil disimpan.',
            'previous'  => false
        ], 200);
    }


    public function destroy($id): RedirectResponse
    {
        $query = StatusPengobatan::find(base64_decode($id));

        if (!$query) {
            return redirect()->route('status')->with('error', 'Data Status Pengobatan tidak ditemukan.');
        }

        $query->delete();

        return redirect()->route('status')->with('success', 'Data Status Pengobatan berhasil dihapus.');
    }
}

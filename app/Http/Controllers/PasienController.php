<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use App\Models\User;
use App\Models\Subdistrict;
use App\Models\StatusPengobatan;
use App\Models\Puskesmas;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PasienController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Pasien',
            'pasien' => Pasien::with(['user', 'statuspengobatan', 'puskesmas'])->orderByDesc('id')->get()
        ];

        return view('pasien-index', $data);
    }

    public function create()
    {
        $data = array(
            'title'                 => 'Pasien',
            'subdistricts'          => Subdistrict::all(),
            'status_pengobatan'     => StatusPengobatan::all(),
            'puskesmas'             => Puskesmas::all(),
            'nextNik'               => $this->_generateNik()
        );

        return view('pasien-add-edit', $data);
    }

    private function _generateNik()
    {
        $latestNik = Pasien::count();
        $nextNik = sprintf('%016d', $latestNik + 1);

        while (Pasien::where('nik', $nextNik)->exists()) {
            $latestNik++;
            $nextNik = sprintf('P%016d', $latestNik + 1);
        }

        return $nextNik;
    }

    public function show($id)
    {
        $query = Pasien::with(['user', 'subdistrict.district.province', 'StatusPengobatan', 'puskesmas.subdistrict.district.province'])->find(base64_decode($id));

        if (empty($query->id)) {
            return redirect()->route('patient')->with('error', 'Data Pasien tidak ditemukan.');
        }

        $data = array(
            'title' => 'Pasien',
            'data'  => $query,
        );

        return view('pasien-detail', $data);
    }

    public function edit($id)
    {
        $query = Pasien::with(['user', 'subdistrict.district.province', 'StatusPengobatan', 'puskesmas.subdistrict.district.province'])->find(base64_decode($id));

        $data = array(
            'title'                 => 'Pasien',
            'data'                  => $query,
            'subdistricts'          => Subdistrict::all(),
            'status_pengobatan'     => StatusPengobatan::all(),
            'puskesmas'             => Puskesmas::all(),
            'nextNik'               => $this->_generateNik()
        );

        return view('pasien-add-edit', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $patient = Pasien::with(['user'])->find(base64_decode($id));
        if (!$patient) {
            $patient = new Pasien();
        }

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/'],
            'email' => [
                'required', 'email',
                isset($patient->user->id) ? Rule::unique('users', 'email')->ignore($patient->user->id) : 'unique:users,email'
            ],
            'jenis_kelamin' => ['required', 'string', 'in:Laki-laki,Perempuan'],
            'tempat_lahir' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]*$/'],
            'tanggal_lahir' => ['required', 'date', 'date_format:d/m/Y'],
            'ponsel' => [
                'nullable', 'string', 'min:10', 'numeric',
                isset($patient->user->id) ? Rule::unique('users', 'ponsel')->ignore($patient->user->id) : 'unique:users,ponsel'
            ],
            'password' => [isset($patient->user->id) ? 'nullable' : 'required', 'string', 'min:6'],
            'nik' => [
                'required', 'string', 'max:16', 'numeric',
                isset($patient->id) ? Rule::unique('patients', 'nik')->ignore($patient->id) : 'unique:patients,nik'
            ],
            'subdistrict_id' => ['required', 'exists:subdistricts,id'],
            'tanggal_diagnosis' => ['required', 'date', 'date_format:d/m/Y'],
            'status_pengobatan_id' => ['required', 'exists:status_pengobatan,id'],
            'tanggal_mulai_pengobatan' => ['required', 'date', 'date_format:d/m/Y'],
            'tanggal_selesai_pengobatan' => ['required', 'date', 'date_format:d/m/Y'],
            'puskesmas_id' => ['required', 'exists:puskesmas,id'],
        ]);

        $user = $patient->user ?: new User();
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->jenis_kelamin = $validatedData['jenis_kelamin'];
        $user->tempat_lahir = $validatedData['tempat_lahir'];
        $user->tanggal_lahir = Carbon::createFromFormat('d/m/Y', $validatedData['tanggal_lahir'])->format('Y-m-d');
        $user->ponsel = $validatedData['ponsel'];
        $user->password = isset($validatedData['password']) ? Hash::make($validatedData['password']) : $patient->user->password;
        $user->save();

        $patient->nik = $validatedData['nik'];
        $patient->subdistrict_id = $validatedData['subdistrict_id'];
        $patient->tanggal_diagnosis = Carbon::createFromFormat('d/m/Y', $validatedData['tanggal_diagnosis'])->format('Y-m-d');
        $patient->status_pengobatan_id = $validatedData['status_pengobatan_id'];
        $patient->tanggal_mulai_pengobatan = Carbon::createFromFormat('d/m/Y', $validatedData['tanggal_mulai_pengobatan'])->format('Y-m-d');
        $patient->tanggal_selesai_pengobatan = Carbon::createFromFormat('d/m/Y', $validatedData['tanggal_selesai_pengobatan'])->format('Y-m-d');
        $patient->puskesmas_id = $validatedData['puskesmas_id'];
        $patient->user_id = $user->id;
        $patient->save();

        return response()->json([
            'success' => true,
            'message' => 'Data Pasien berhasil disimpan.',
        ], 200);
    }

    public function destroy($id): RedirectResponse
    {
        $pasien = Pasien::with('user')->find(base64_decode($id));

        if (!$pasien) {
            return redirect()->route('patient')->with('error', 'Data Pasien tidak ditemukan.');
        }

        $pasien->delete();

        if ($pasien->user) {
            $pasien->user->delete();
        }

        return redirect()->route('patient')->with('success', 'Data Pasien berhasil dihapus.');
    }
}

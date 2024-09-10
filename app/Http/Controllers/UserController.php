<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use App\Models\User;
use App\Models\UserType;
use App\Models\District;
use App\Models\Puskesmas;
use App\Models\Pjtb;
use App\Models\HealthOffice;
use App\Models\Patient;
use App\Models\Subdistrict;
use App\Models\Coordinator;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function index()
    {
        //
    }

    public function list($id)
    {
        $userType = UserType::find(base64_decode($id));

        if (empty($userType->id)) {
            return redirect()->back();
        }

        $data = [
            'title'         => $userType->name,
            'users'         => User::with('userType')->where('user_type_id', $userType->id)->whereNotIn('id', [1])->orderByDesc('id')->get(),
            'user_types'    => UserType::all(),
            'user_type_id'  => $userType->id
        ];

        return view('user-index', $data);
    }

    public function create($id)
    {
        $userType = UserType::find(base64_decode($id));

        if (empty($userType->id)) {
            return redirect()->back();
        }

        $data = [
            'title'         => $userType->name,
            'users'         => User::with('userType')->whereNotIn('id', [1])->orderByDesc('id')->get(),
            'user_types'    => UserType::all(),
            'user_type_id'  => $userType->id,
            'puskesmas'     => Puskesmas::getAllPuskesmas(),
            'districts'     => District::getAllDistricts(),
            'office_types'  => HealthOffice::getOfficeTypes(),
            'coord_types'   => Coordinator::getCoordTypes(),
        ];

        return view('user-add-edit', $data);
    }

    public function edit($id)
    {
        $user = User::with('userType')->find(base64_decode($id));

        if (empty($user->id)) {
            return redirect()->back();
        }

        $data = [
            'title'         => $user->userType->name,
            'users'         => User::with('userType')->orderByDesc('id')->get(),
            'data'          => $user,
            'user_type_id'  => $user->user_type_id,
            'puskesmas'     => Puskesmas::getAllPuskesmas(),
            'districts'     => District::getAllDistricts(),
            'office_types'  => HealthOffice::getOfficeTypes(),
            'coord_types'   => Coordinator::getCoordTypes(),
        ];

        if ($user->user_type_id == 1) {
            return view('user-index', $data);
        } else {
            if ($user->user_type_id == 2) {
                $data['healthOffice'] = HealthOffice::where('user_id', $user->id)->first();
            } elseif ($user->user_type_id == 3) {
                $pjtb = Pjtb::where('user_id', $user->id)->first();
                $data['pjtb'] = $pjtb;
                $data['puskesmas_id'] = isset($pjtb->puskesmas_id) ? $pjtb->puskesmas_id : 0;
            } elseif ($user->user_type_id == 4) {
                $patient = Patient::where('user_id', $user->id)->first();
                $data['patient'] = $patient;
                $data['puskesmas_id'] = isset($patient->puskesmas_id) ? $patient->puskesmas_id : 0;
            }

            return view('user-add-edit', $data);
        }
    }

    public function show($id)
    {
        $userId = base64_decode($id);
        $user = User::with('userType')->find($userId);

        if (!$user) {
            return redirect()->back();
        }

        $userTypeId = $user->userType->id;

        if ($userTypeId == 1 && $user->id != Auth::id()) {
            return redirect()->route('user.list', ['id' => base64_encode($userTypeId)]);
        }

        $data = [
            'title' => $userTypeId == 1 ? 'Profil Saya' : $user->userType->name,
            'user' => $user,
            'user_type_id' => $userTypeId,
            'users' => User::where('user_type_id', $userTypeId)->orderBy('name', 'asc')->get()
        ];

        $userDetail = $this->getUserDetail($userTypeId, $user->id);

        if (!$userDetail) {
            $this->setUserDetail($userTypeId, $user->id);
            $userDetail = $this->getUserDetail($userTypeId, $user->id);
        }

        if ($userTypeId != 1) {
            $data['user_detail_id'] = $userDetail->id;
            $data['user_detail'] = $this->listUserDetail($userDetail);
        }

        return view('user-detail', $data);
    }

    private function getUserDetail($userTypeId, $userId)
    {
        switch ($userTypeId) {
            case 2:
                return HealthOffice::with('user.userType')->where('user_id', $userId)->first();
            case 3:
                return Pjtb::with('user.userType', 'puskesmas')->where('user_id', $userId)->first();
            case 4:
                return Patient::with('user.userType', 'puskesmas', 'subdistrict')->where('user_id', $userId)->first();
            default:
                return null;
        }
    }

    private function setUserDetail($userTypeId, $userId)
    {
        $model = null;

        switch ($userTypeId) {
            case 2:
                $model = HealthOffice::class;
                break;
            case 3:
                $model = Pjtb::class;
                break;
            case 4:
                $model = Patient::class;
                break;
            default:
                return;
        }

        $instance = $model::where('user_id', $userId)->first();

        if (!$instance) {
            $instance = new $model();
            $instance->user_id = $userId;
            $instance->save();
        }
    }

    private function listUserDetail($userDetail)
    {
        $userTypeId = $userDetail->user->user_type_id;
        $listData = [];

        if ($userTypeId == 2) {
            $listData = [
                'Dinas Kesehatan' => $userDetail->office_type_id
                    ? HealthOffice::getOfficeTypes($userDetail->office_type_id)
                    : '-',
                'Alamat Kantor' => $userDetail->office_address ?? '-',
                'Kabupaten/Kota' => $userDetail->district_id ? District::getDistrictById($userDetail->district_id)['name'] : '-',
                'Nomor Telepon' => $userDetail->telephone ?? '-',
                'Alamat Email' => $userDetail->email ?? '-'
            ];
        } elseif ($userTypeId == 3) {
            $listData = [
                'PJTB/Kader' => $userDetail->coord_type_id ? Coordinator::getCoordTypes($userDetail->coord_type_id) : '-',
                'Puskesmas' => $userDetail->puskesmas_id ? Puskesmas::getPuskesmasById($userDetail->puskesmas_id)['name'] : '-',
            ];
        } elseif ($userTypeId == 4) {
            $listData = [
                'NIK' => $userDetail->nik ?? '-',
                'Alamat' => $userDetail->address ?? '-',
                'Kecamatan' => $userDetail->subdistrict_id ? Subdistrict::getSubdistrictById($userDetail->subdistrict_id)['name'] : '-',
                'Pekerjaan' => $userDetail->occupation ?? '-',
                'Tinggi Badan' => $userDetail->height ? $userDetail->height . ' Cm' : '-',
                'Berat Badan' => $userDetail->weight ? $userDetail->weight . ' Kg' : '-',
                'Golongan Darah' => $userDetail->blood_type ?? '-',
                'Tanggal Diagnosis' => isset($userDetail->diagnosis_date) ? \Carbon\Carbon::parse($userDetail->diagnosis_date)->format('d F Y') : '-',
                'Tempat Berobat (Puskesmas)' => $userDetail->puskesmas_id ? Puskesmas::getPuskesmasById($userDetail->puskesmas_id)['name'] : '-',
            ];
        }

        return $listData;
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        date_default_timezone_set('Asia/Jakarta');

        $user = User::find(base64_decode($id));

        if (!$user) {
            $user = new User();
        }

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'telephone' => [
                'required',
                'numeric',
                'digits_between:10,15',
                Rule::unique('users', 'telephone')->ignore($user->id)
            ],
            'password' => [
                empty($user->id) ? 'required' : 'nullable',
                'string',
                'min:8'
            ],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'place_of_birth' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date_format:d/m/Y', 'before_or_equal:today'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'office_type_id' => empty($user->id) && $request->input('user_type_id') == 2 ? ['required', 'in:1,2'] : ['nullable'],
            'office_address' =>  empty($user->id) && $request->input('user_type_id') == 2 ? ['required', 'string', 'max:255'] : ['nullable'],
            'district_id' => empty($user->id) && $request->input('user_type_id') == 2 ? ['required', 'exists:districts,id'] : ['nullable'],
            'coord_type_id' => empty($user->id) && $request->input('coord_type_id') == 3 ? ['required', 'in:1,2'] : ['nullable'],
            'puskesmas_id' => empty($user->id) && $request->input('user_type_id') == 3 || empty($user->id) && $request->input('user_type_id') == 4 ? ['required', 'exists:puskesmas,id'] : ['nullable'],
        ]);

        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];

        $username = $username = preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $validatedData['email'])[0]);
        $user->username = $username;

        if (isset($validatedData['password']) && !empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }
        $user->user_type_id = $validatedData['user_type_id'];
        $user->telephone = $validatedData['telephone'];
        $user->gender = $validatedData['gender'];
        $user->place_of_birth = $validatedData['place_of_birth'];
        $user->date_of_birth = \DateTime::createFromFormat('d/m/Y', $validatedData['date_of_birth'])->format('Y-m-d');

        if ($request->hasFile('image')) {
            if ($user->profile) {
                $oldImagePath = config('constants.UPLOAD_PATH') . '/' . $user->profile;
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }

            $uploadImage = $request->file('image');
            $imageName = $username . '-' . date('YmdHi') . '.' . $uploadImage->getClientOriginalExtension();
            $uploadImage->move(config('constants.UPLOAD_PATH'), $imageName);
            $user->profile = $imageName;
        } else {
            if ($request->input('remove_image')) {
                if ($user->profile) {
                    $oldImagePath = config('constants.UPLOAD_PATH') . '/' . $user->profile;
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                    }
                    $user->profile = null;
                }
            }
        }

        $user->save();

        if ($request->input('user_type_id') == 2) {
            $healthOffice = HealthOffice::where('user_id', $user->id)->first();

            if (!$healthOffice) {
                $healthOffice = new HealthOffice();
                $healthOffice->user_id = $user->id;
            }
            $healthOffice->office_type_id = $request->input('office_type_id');
            $healthOffice->office_address = $request->input('office_address');
            $healthOffice->district_id = $request->input('district_id');
            $healthOffice->save();
        } elseif ($request->input('user_type_id') == 3) {
            $pjtb = Pjtb::where('user_id', $user->id)->first();
            if (!$pjtb) {
                $pjtb = new Pjtb();
                $pjtb->user_id = $user->id;
            }
            $pjtb->coord_type_id = $request->input('coord_type_id');
            $pjtb->puskesmas_id = $request->input('puskesmas_id');
            $pjtb->save();
        } elseif ($request->input('user_type_id') == 4) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                $patient = new Patient();
                $patient->user_id = $user->id;
            }
            $patient->puskesmas_id = $request->input('puskesmas_id');
            $patient->save();
        }

        $data = [
            'status' => true,
            'message' => 'Data ' . $request->input('user_type') . ' berhasil disimpan.',
            'url' => $request->input('user_type_id') != 1 || Auth::id() == $user->id ? r : route('user.list', ['id' => base64_encode($request->input('user_type_id'))]),
        ];

        return response()->json($data, 200);
    }

    public function destroy($id): RedirectResponse
    {
        $user = User::with('userType')->find(base64_decode($id));

        if ($user) {
            if ($user->profile) {
                $oldImagePath = config('constants.UPLOAD_PATH') . '/' . $user->profile;
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
            }

            $user->delete();

            return redirect()->route('user.list', ['id' => base64_encode($user->user_type_id)])->with('success', 'Data ' . $user->userType->name . ' berhasil dihapus.');
        }

        return redirect()->back();
    }

    public function updateTreatment(Request $request, $id): JsonResponse
    {
        $patient = Patient::findOrFail(base64_decode($id));

        $validatedData = $request->validate([
            'nik' => [
                'nullable',
                'numeric',
                Rule::when(function ($input) {
                    return $input->nik != '0';
                }, ['digits:16']),
                Rule::unique('patients', 'nik')->ignore($patient->id)->where(function ($query) {
                    return $query->whereNotNull('nik')->where('nik', '!=', '0');
                }),
            ],
            'address' => ['required', 'string', 'max:255'],
            'subdistrict_id' => ['required', 'exists:subdistricts,id'],
            'diagnosis_date' => ['required', 'date_format:d/m/Y'],
            'puskesmas_id' => ['required', 'exists:puskesmas,id'],
        ]);

        if ($patient->user) {
            $patient->user->update([
                'address' => $validatedData['address'],
            ]);
        }

        $patient->update([
            'nik' => $validatedData['nik'],
            'subdistrict_id' => $validatedData['subdistrict_id'],
            'puskesmas_id' => $validatedData['puskesmas_id'],
            'diagnosis_date' => \DateTime::createFromFormat('d/m/Y', $validatedData['diagnosis_date'])->format('Y-m-d'),
        ]);

        $data = [
            'success' => true,
            'message' => 'Data pengobatan berhasil diperbarui.',
        ];

        return response()->json($data, 200);
    }
}

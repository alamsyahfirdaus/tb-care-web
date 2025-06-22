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
            'title'         => $user->id == Auth::id() ? 'Profil Saya' : $user->userType->name,
            'users'         => User::with('userType')->orderByDesc('id')->get(),
            'data'          => $user,
            'user_type_id'  => $user->user_type_id,
            'puskesmas'     => Puskesmas::getAllPuskesmas(),
            'districts'     => District::getAllDistricts(),
            'office_types'  => HealthOffice::getOfficeTypes(),
            'coord_types'   => Coordinator::getCoordTypes(),
        ];

        if ($user->id == Auth::id() || $user->user_type_id == 1) {
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

    public function show($id = null)
    {
        $userId = $id ? base64_decode($id) : Auth::id();
        $user   = User::with('userType')->find($userId);

        if (!$user) {
            return redirect()->back();
        }

        $userTypeId = $user->userType->id;

        if ($userTypeId == 1 && $user->id != Auth::id()) {
            return redirect()->route('user.list', ['id' => base64_encode($userTypeId)]);
        }

        $data = [
            'title'         => $user->id == Auth::id() ? 'Profil Saya' : $user->userType->name,
            'user'          => $user,
            'user_type_id'  => $userTypeId,
            'users'         => User::where('user_type_id', $userTypeId)->orderBy('name', 'asc')->get()
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
                'Telepon Kantor' => $userDetail->office_phone ?? '-',
                'Email Kantor' => $userDetail->office_email ?? '-'
            ];
        } elseif ($userTypeId == 3) {
            $listData = [
                'Koordinator' => $userDetail->coord_type_id ? Coordinator::getCoordTypes($userDetail->coord_type_id) : '-',
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
                // 'Tanggal Diagnosis' => isset($userDetail->diagnosis_date) ? \App\Helpers\DateHelper::convertDate($userDetail->diagnosis_date) : '-',
                'Puskesmas' => $userDetail->puskesmas_id ? Puskesmas::getPuskesmasById($userDetail->puskesmas_id)['name'] : '-',
            ];
        }

        return $listData;
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        date_default_timezone_set('Asia/Jakarta');

        $user = User::find(base64_decode($id)) ?? new User();

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($user->id)
            ],
            'username' => [
                empty($user->id) ? 'nullable' : 'required',
                Rule::unique('users', 'username')->ignore($user->id)
            ],
            'phone' => [
                'required',
                'numeric',
                'digits_between:10,15',
                Rule::unique('users', 'phone')->ignore($user->id)
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'place_of_birth' => [
                $request->input('user_type_id') == 1 ? 'nullable' : 'required',
                'string',
                'max:255'
            ],
            'date_of_birth' => [
                $request->input('user_type_id') == 1 ? 'nullable' : 'required',
                'date_format:d/m/Y',
                'before_or_equal:today'
            ],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'office_type_id' => [
                empty($user->id) && $request->input('user_type_id') == 2 ? 'required' : 'nullable',
                'in:1,2'
            ],
            'office_address' => [
                empty($user->id) && $request->input('user_type_id') == 2 ? 'required' : 'nullable',
                'string',
                'max:255'
            ],
            'district_id' => [
                empty($user->id) && $request->input('user_type_id') == 2 ? 'required' : 'nullable',
                'exists:districts,id'
            ],
            'coord_type_id' => [
                empty($user->id) && $request->input('user_type_id') == 3 ? 'required' : 'nullable',
                'in:1,2'
            ],
            'puskesmas_id' => [
                empty($user->id) && in_array($request->input('user_type_id'), [3, 4]) ? 'required' : 'nullable',
                'exists:puskesmas,id'
            ]
        ]);

        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];

        if (empty($user->id) && empty($validatedData['email']) && !isset($validatedData['username'])) {
            $nameParts = explode(' ', trim($validatedData['name']));
            $username = strtolower(preg_replace("/[^a-zA-Z]/", "", $nameParts[0]));
            if (count($nameParts) > 1) {
                $username .= strtolower(preg_replace("/[^a-zA-Z]/", "", $nameParts[1]));
            }

            $originalUsername = $username;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $originalUsername . $counter;
                $counter++;
            }

            $password = Hash::make($username);
        } elseif (isset($validatedData['username'])) {
            $username = $validatedData['username'];
            $password = $validatedData['password'] ? Hash::make($validatedData['password']) : $user->password;
        } else {
            $username = isset($validatedData['username']) ? $validatedData['username'] : preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $validatedData['email'])[0]);
            $password = Hash::make($username);
        }

        $user->username = $username;
        $user->password = $password;
        $user->user_type_id = $validatedData['user_type_id'];
        $user->phone = $validatedData['phone'];
        $user->gender = $validatedData['gender'];
        $user->place_of_birth = $validatedData['place_of_birth'] ?? null;
        $user->date_of_birth = $validatedData['date_of_birth'] ? \DateTime::createFromFormat('d/m/Y', $validatedData['date_of_birth'])->format('Y-m-d') : null;

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
        } elseif ($request->input('remove_image')) {
            if ($user->profile) {
                $oldImagePath = config('constants.UPLOAD_PATH') . '/' . $user->profile;
                if (File::exists($oldImagePath)) {
                    File::delete($oldImagePath);
                }
                $user->profile = null;
            }
        }

        $user->save();

        switch ($request->input('user_type_id')) {
            case 2:
                $healthOffice = HealthOffice::firstOrNew(['user_id' => $user->id]);

                $office_type_id = $request->input('office_type_id') ?
                    $request->input('office_type_id') : (isset($healthOffice->id) && $healthOffice->office_type_id ?
                        $healthOffice->office_type_id :
                        null);

                $office_address = $request->input('office_address') ?
                    $request->input('office_address') : (isset($healthOffice->id) && $healthOffice->office_address ?
                        $healthOffice->office_address :
                        null);

                $district_id = $request->input('district_id') ?
                    $request->input('district_id') : (isset($healthOffice->id) && $healthOffice->district_id ?
                        $healthOffice->district_id :
                        null);

                $healthOffice->office_type_id = $office_type_id;
                $healthOffice->office_address = $office_address;
                $healthOffice->district_id = $district_id;
                $healthOffice->save();
                break;

            case 3:
                $pjtb = Pjtb::firstOrNew(['user_id' => $user->id]);

                $coord_type_id = $request->input('coord_type_id') ?
                    $request->input('coord_type_id') : (isset($pjtb->id) && $pjtb->coord_type_id ?
                        $pjtb->coord_type_id :
                        null);

                $puskesmas_id = $request->input('puskesmas_id') ?
                    $request->input('puskesmas_id') : (isset($pjtb->id) && $pjtb->puskesmas_id ?
                        $pjtb->puskesmas_id :
                        null);

                $pjtb->coord_type_id = $coord_type_id;
                $pjtb->puskesmas_id = $puskesmas_id;
                $pjtb->save();
                break;

            case 4:
                $patient = Patient::firstOrNew(['user_id' => $user->id]);

                $puskesmas_id = $request->input('puskesmas_id') ?
                    $request->input('puskesmas_id') : (isset($patient->id) && $patient->puskesmas_id ?
                        $patient->puskesmas_id :
                        null);

                $patient->puskesmas_id = $puskesmas_id;
                $patient->save();
                break;
        }

        $data['status'] = true;

        $redirectUrl = $this->determineRedirectUrl($user, $request);

        if ($redirectUrl) {
            $data['message'] = 'Data ' . $request->input('user_type') . ' berhasil disimpan.';
            $data['url'] = $redirectUrl;
        } else {
            $data['previous'] = true;
        }

        return response()->json($data, 200);
    }

    private function determineRedirectUrl(User $user, Request $request): ?string
    {
        if ($user->id != Auth::id()) {
            if ($request->input('user_type_id') != 1) {
                return route('user.show', ['id' => base64_encode($user->id)]);
            }
            return route('user.list', ['id' => base64_encode($request->input('user_type_id'))]);
        }
        return null;
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
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Arr;
use App\Models\User;
use App\Models\UserType;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index()
    {
        // $data = [
        //     'title'         => 'Pengguna',
        //     'users'         => User::with('userType') ->whereNotIn('id', [1])->orderByDesc('id') ->get(),
        //     'user_types'    => UserType::all()
        // ];

        // return view('user', $data);
    }

    public function list($id)
    {
        $data = [
            'title'         => 'Pengguna',
            'users'         => User::with('userType')->whereNotIn('id', [1])->orderByDesc('id') ->get(),
            'user_types'    => UserType::all()
        ];

        return view('user', $data);
    }

    public function create()
    {
        
    }

    public function edit($id)
    {
        $data = [
            'title'      => 'Pengguna',
            'users'      => User::with('userType')->orderByDesc('id')->get(),
            'user_types' => UserType::all(),
            'data'       => User::find(base64_decode($id))
        ];

        return view('user', $data);
    }

    public function save(Request $request, $id = null): JsonResponse
    {
        $user = User::find(base64_decode($id));
        if (!$user) {
            $user = new User();
        }

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', isset($user->id) ? Rule::unique('users', 'email')->ignore($user->id) : 'unique:users,email'],
            'telephone' => ['required', 'string', 'min:10', isset($user->id) ? Rule::unique('users', 'telephone')->ignore($user->id) : 'unique:users,telephone'],
            'password' => [empty($user->id) ? 'required' : 'nullable', 'string', empty($user->id) ? 'min:8' : 'sometimes|min:8'],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        if (isset($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }
        $user->user_type_id = $validatedData['user_type_id'];
        $user->telephone = $validatedData['telephone'];

        if ($request->hasFile('image')) {
            $profileImage = $request->file('image');
            $imageName = time() . '.' . $profileImage->getClientOriginalExtension();
            $profileImage->move(config('constants.UPLOAD_PATH'), $imageName);
            $user->profile = $imageName;
        }

        $user->save();

        $data = array(
            'success' => true,
            'message' => 'Data pengguna berhasil disimpan.',
        );

        if (!$user->wasRecentlyCreated) {
            $data['previous'] = true;
        }

        return response()->json($data, 200);
    }

    public function destroy($id): RedirectResponse
    {
        $user = User::find(base64_decode($id));

        if ($user) {
            if ($user->profile) {
                $profilePath = config('constants.UPLOAD_PATH') . '/' . $user->profile;
                if (File::exists($profilePath)) {
                    File::delete($profilePath);
                }
            }

            $user->delete();
            return redirect()->route('users')->with('success', 'Data pengguna berhasil dihapus.');
        }

        return redirect()->route('users')->with('error', 'Data pengguna tidak ditemukan.');
    }
}

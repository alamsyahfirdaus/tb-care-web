<?php

namespace App\Http\Controllers;

use App\Models\HealthOffice;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class HealthOfficeController extends Controller
{
    public function index()
    {
        //
    }

    public function edit($id)
    {
        $coord = HealthOffice::with('user.userType')->find(base64_decode($id));

        if (!$coord) {
            return redirect()->back();
        }

        $data = [
            'title' => $coord->user->userType->name,
            'data' => $coord,
            'health_offices' => HealthOffice::getHoWithUser(),
            'office_types' => HealthOffice::getOfficeTypes(),
            'districts' => District::getAllDistricts(),
        ];

        return view('ho-edit', $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $id = base64_decode($id);

        $fieldData = [
            'office_type_id' => 'required|in:1,2',
            'office_address' => 'required|string|max:255',
            'district_id' => 'required|exists:districts,id',
            'telephone' => [
                'nullable',
                'numeric',
                'digits_between:10,15',
                'unique:health_offices,telephone,' . $id,
            ],
            'email' => [
                'nullable',
                'email',
                'unique:health_offices,email,' . $id,
            ],
        ];

        $validatedData = $request->validate($fieldData);

        $coord = HealthOffice::find($id);

        if (!$coord) {
            return response()->json([
                'status' => false,
                'message' => 'Admin dinkes tidak ditemukan.',
            ], 404);
        }

        $updateData = array_filter($validatedData, function ($key) use ($fieldData) {
            return array_key_exists($key, $fieldData);
        }, ARRAY_FILTER_USE_KEY);

        $coord->update($updateData);

        return response()->json([
            'status' => true,
            'message' => 'Data admin dinkes berhasil diperbarui.',
        ], 200);
    }
}

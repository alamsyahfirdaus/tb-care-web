<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Coordinator;
use App\Models\Puskesmas;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class CoordinatorController extends Controller
{
    public function index()
    {
        //
    }

    public function edit($id)
    {
        $coord = Coordinator::with('user.userType')->find(base64_decode($id));

        if (!$coord) {
            return redirect()->back();
        }

        $data = [
            'title' => $coord->user->userType->name,
            'data' => $coord,
            'coords' => Coordinator::getCoordWithUser(),
            'coord_types' => Coordinator::getCoordTypes(),
            'puskesmas' => Puskesmas::getAllPuskesmas(),
        ];

        return view('coord-edit', $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $id = base64_decode($id);
    
        $fieldData = [
            'coord_type_id' => 'required|in:1,2',
            'puskesmas_id' => 'required|exists:puskesmas,id',
        ];
    
        $validatedData = $request->validate($fieldData);
    
        $coord = Coordinator::find($id);
    
        if (!$coord) {
            return response()->json([
                'status' => false,
                'message' => 'PJTB/Kader tidak ditemukan.',
            ], 404);
        }
    
        $updateData = array_filter($validatedData, function ($key) use ($fieldData) {
            return array_key_exists($key, $fieldData);
        }, ARRAY_FILTER_USE_KEY);
    
        $coord->update($updateData);
    
        return response()->json([
            'status' => true,
            'message' => 'Data ptjb/kader berhasil diperbarui.',
        ], 200);
    }

}

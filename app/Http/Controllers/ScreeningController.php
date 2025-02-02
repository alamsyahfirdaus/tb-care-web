<?php

namespace App\Http\Controllers;

use App\Models\Screening;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function index()
    {
        $data = array(
            'title'      => 'Skrining',
            'screenings' => Screening::all()
        );
        return view('screening-index', $data);
    }

    public function process(Request $request)
    {
        $suspectedTbc = false;
        $age = 0;

        foreach ($request->input('category_ids') as $category_id) {
            // Menentukan usia berdasarkan category_id 1 atau 2
            if ($category_id == 1 || $category_id == 2) {
                $age = $request->input('question_' . $category_id);
            }

            // Jika usia ≥ 15 tahun
            if ($age >= 15) {
                if ($category_id == 12 && $request->input('question_' . $category_id) == 1) {
                    // Durasi dalam hari, ubah ke minggu
                    $duration = $request->input('category_id_' . $category_id) / 7;
                    if ($duration >= 2) {
                        // Batuk ≥ 2 minggu
                        $suspectedTbc = true;
                    }
                } elseif ($category_id == 13 && $request->input('question_' . $category_id) == 1) {
                    // Batuk darah
                    $suspectedTbc = true;
                }
            } else {
                // Jika usia < 15 tahun
                if ($category_id == 12 && $request->input('question_' . $category_id) == 1) {
                    $duration = $request->input('category_id_' . $category_id) / 7;
                    if ($duration >= 2) {
                        // Batuk ≥ 2 minggu
                        $suspectedTbc = true;
                    }
                } elseif ($category_id == 13 && $request->input('question_' . $category_id) == 1) {
                    // Batuk darah
                    $suspectedTbc = true;
                } elseif ($category_id == 14 && $request->input('question_' . $category_id) == 1) {
                    // BB turun/tidak naik dalam 2 bulan terakhir
                    $suspectedTbc = true;
                } elseif ($category_id == 15 && $request->input('question_' . $category_id) == 1) {
                    // BB turun/tidak naik dalam 2 bulan terakhir
                    $suspectedTbc = true;
                } elseif ($category_id == 16 && $request->input('question_' . $category_id) == 1) {
                    // Demam ≥ 2 minggu
                    $suspectedTbc = true;
                } elseif ($category_id == 17 && $request->input('question_' . $category_id) == 1) {
                    // Lesu atau malaise, anak kurang aktif bermain
                    $suspectedTbc = true;
                }
            }
        }

        if ($suspectedTbc) {
            return redirect()->back()->with('warning', 'Anda Terduga TB');
        } else {
            return redirect()->back()->with('success', 'Anda Bukan Terduga TB');
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScreeningCategory;
use App\Models\ScreeningQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScreeningController extends Controller
{
    public function getAgeCategories()
    {
        $categories = ScreeningCategory::select('id', 'name')->get();

        return response()->json([
            'message' => 'Berhasil mengambil daftar kategori usia.',
            'data' => $categories,
        ]);
    }

    public function getQuestions(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:screening_categories,id',
        ], [
            'category_id.required' => 'Kategori usia harus diisi.',
            'category_id.exists'   => 'Kategori usia yang dipilih tidak tersedia.',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $questions = ScreeningQuestion::with([
            'category:id,name',
            'subQuestions' => function ($query) {
                $query->select('id', 'group_id', 'question')->orderBy('id');
            }
        ])
            ->select('id', 'question', 'group', 'screening_category_id')
            ->where('screening_category_id', $request->category_id)
            ->whereNull('group_id')
            ->orderBy('ordering')
            ->get();

        $formatted = $questions->map(function ($item) {
            return [
                'id'            => $item->id,
                'category'      => $item->category ? $item->category->name : null,
                'group'         => $item->group,
                'sub_questions' => $item->subQuestions->map(function ($sub) {
                    return [
                        'question_id'   => $sub->id,
                        'question_text' => $sub->question,
                    ];
                }),
            ];
        });

        return response()->json([
            'message' => 'Berhasil mengambil daftar pertanyaan.',
            'data' => $formatted,
        ]);
    }

    public function submitAnswers(Request $request)
    {
        // Validasi data yang dikirim dari frontend
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:screening_questions,id',
            'answers.*.answer' => 'required|in:0,1',
        ], [
            'answers.required' => 'Jawaban harus diisi.',
            'answers.array' => 'Jawaban harus dalam bentuk array.',
            'answers.min' => 'Minimal satu jawaban harus diberikan.',
            'answers.*.question_id.required' => 'ID pertanyaan harus diisi.',
            'answers.*.question_id.exists' => 'ID pertanyaan tidak ditemukan.',
            'answers.*.answer.required' => 'Jawaban harus diisi.',
            'answers.*.answer.in' => 'Jawaban hanya boleh 1 (ya) atau 0 (tidak).',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Ambil data tervalidasi dan ubah ke bentuk koleksi
        $validated = $validator->validated();
        $answers = collect($validated['answers']);

        // Ambil semua question_id dari jawaban
        $questionIds = $answers->pluck('question_id')->toArray();

        // Ambil semua ID pertanyaan yang bersifat kritis (is_critical = true)
        $criticalQuestionIds = ScreeningQuestion::whereIn('id', $questionIds)
            ->where('is_critical', true)
            ->pluck('id')
            ->toArray();

        // Evaluasi apakah ada jawaban "ya" (1) pada pertanyaan kritis
        $isSuspectedTB = $answers
            ->whereIn('question_id', $criticalQuestionIds)
            ->where('answer', 1)
            ->isNotEmpty();

        // Kembalikan hasil evaluasi
        return response()->json([
            'message' => 'Skrining berhasil diproses.',
            'result'  => $isSuspectedTB ? 'Terduga TB' : 'Bukan Terduga TB',
        ]);
    }
}

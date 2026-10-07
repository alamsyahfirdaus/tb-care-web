<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CloseContact;
use App\Models\Screening;
use App\Models\ScreeningAnswer;
use App\Models\ScreeningCategory;
use App\Models\ScreeningQuestion;
use App\Models\Patient;
use App\Models\Puskesmas;
use Carbon\Carbon;
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
            'answers'               => 'required|array|min:1',
            'answers.*.question_id' => 'required|exists:screening_questions,id',
            'answers.*.answer'      => 'required|in:0,1',
            'close_contact_id'      => 'nullable|exists:close_contacts,id',
        ], [
            'answers.required'               => 'Jawaban harus diisi.',
            'answers.array'                  => 'Jawaban harus dalam bentuk array.',
            'answers.min'                    => 'Minimal satu jawaban harus diberikan.',
            'answers.*.question_id.required' => 'ID pertanyaan harus diisi.',
            'answers.*.question_id.exists'   => 'ID pertanyaan tidak ditemukan.',
            'answers.*.answer.required'      => 'Jawaban harus diisi.',
            'answers.*.answer.in'            => 'Jawaban hanya boleh 1 (ya) atau 0 (tidak).',
            'close_contact_id.exists'        => 'Data anggota serumah tidak ditemukan.',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Ambil data tervalidasi dan ubah ke bentuk koleksi
        $validated = $validator->validated();
        $answers = collect($validated['answers']);

        // Ambil semua question_id dari jawaban
        $questionIds = $answers->pluck('question_id')->toArray();

        // Ambil data user yang sedang login jika ada
        $user = auth('sanctum')->user() ?? auth()->user();
        $patient = $user ? Patient::where('user_id', $user->id)->first() : null;

        // Otorisasi & Resolusi Target Skrining (Anggota Serumah atau Pasien/Umum)
        $contact = null;
        if ($request->filled('close_contact_id')) {
            $contact = CloseContact::with('patient')->find($request->close_contact_id);
            if (!$contact) {
                return response()->json([
                    'message' => 'Data anggota serumah tidak ditemukan.',
                ], 404);
            }

            // Keamanan IDOR: Pastikan pasien hanya dapat melakukan skrining untuk anggota miliknya sendiri
            if ($user && $user->user_type_id == 2) {
                if (!$patient || $contact->patient_id != $patient->id) {
                    return response()->json([
                        'message' => 'Anda tidak memiliki wewenang melakukan skrining untuk anggota serumah ini.',
                    ], 403);
                }
            } elseif ($user && method_exists($contact->patient, 'isAccessibleBy') && !$contact->patient->isAccessibleBy($user)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki wewenang mengakses anggota serumah ini.',
                ], 403);
            }
        }

        if ($contact) {
            $userId      = $user ? $user->id : null;
            $patientId   = $contact->patient_id;
            $personName  = $contact->name;
            $nik         = $contact->nik;
            $phone       = $contact->phone;
            $gender      = $contact->gender;
            $age         = $contact->age ?: ($contact->date_of_birth ? Carbon::parse($contact->date_of_birth)->age : 25);
            $puskesmasId = optional($contact->patient)->puskesmas_id ?? ($patient ? $patient->puskesmas_id : (Puskesmas::value('id') ?? 1));
        } else {
            $userId      = $user ? $user->id : $request->input('user_id');
            $patientId   = $patient ? $patient->id : $request->input('patient_id');
            $personName  = $request->input('person_name') ?? ($user ? $user->name : 'Masyarakat Mandiri');
            $nik         = $request->input('nik') ?? ($patient ? $patient->nik : null);
            $phone       = $request->input('phone') ?? ($user ? $user->phone : null);
            $gender      = $request->input('gender') ?? ($patient ? $patient->gender : 'L');
            $age         = $request->input('age') ?? ($patient ? $patient->age : 25);
            $puskesmasId = $request->input('puskesmas_id') ?? ($patient ? $patient->puskesmas_id : (Puskesmas::value('id') ?? 1));
        }

        // Evaluasi skor dan gejala kritis
        $questions = ScreeningQuestion::with('groupParent')->whereIn('id', $questionIds)->get()->keyBy('id');
        $totalScore = 0;
        $symptomsCount = 0;
        $hasCritical = false;

        foreach ($answers as $ans) {
            $q = $questions->get($ans['question_id']);
            if (!$q) continue;

            $val = (int) $ans['answer'];
            if ($val === 1) {
                $score = $q->is_critical ? 2 : 1;
                $totalScore += $score;
                if ($q->is_critical) {
                    $hasCritical = true;
                }
                $grp = $q->group ?: (optional($q->groupParent)->group ?: '');
                if (stripos($grp, 'gejala') !== false || $q->is_critical) {
                    $symptomsCount++;
                }
            }
        }

        if ($hasCritical || $totalScore >= 3) {
            $riskLevel = 'Risiko Tinggi';
            $recommendation = 'Anda memiliki indikasi gejala batuk/TB aktif. Segera lakukan pemeriksaan dahak/TCM di Puskesmas terdekat.';
            $status = 'Perlu Tindak Lanjut';
        } elseif ($totalScore >= 1) {
            $riskLevel = 'Risiko Sedang';
            $recommendation = 'Terdapat faktor risiko TB. Lakukan pemantauan mandiri dan konsultasikan ke fasilitas kesehatan jika gejala memberat.';
            $status = 'Dalam Pemantauan';
        } else {
            $riskLevel = 'Risiko Rendah';
            $recommendation = 'Kondisi kesehatan saat ini tidak menunjukkan gejala TB yang signifikan. Jaga pola hidup bersih dan sehat (PHBS).';
            $status = 'Selesai';
        }

        // Generate nomor kode unik skrining
        $prefix = 'SCR-' . date('Ym') . '-';
        $lastScreening = Screening::where('code', 'like', $prefix . '%')->orderByDesc('id')->first();
        $nextSeq = 1;
        if ($lastScreening && preg_match('/SCR-\d+-(\d+)/', $lastScreening->code, $m)) {
            $nextSeq = intval($m[1]) + 1;
        }
        $code = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        $categoryId = $request->input('category_id') ?? ($questions->first() ? $questions->first()->screening_category_id : 1);

        // Simpan data skrining utama ke database
        $screening = Screening::create([
            'code'                  => $code,
            'user_id'               => $userId,
            'patient_id'            => $patientId,
            'close_contact_id'      => $contact ? $contact->id : null,
            'screening_category_id' => $categoryId,
            'puskesmas_id'          => $puskesmasId,
            'person_name'           => $personName,
            'nik'                   => $nik,
            'phone'                 => $phone,
            'gender'                => in_array(strtoupper($gender), ['L', 'P']) ? strtoupper($gender) : 'L',
            'age'                   => is_numeric($age) ? (int)$age : 25,
            'address'               => $request->input('address') ?? ($contact ? $contact->address : ($patient ? $patient->address : null)),
            'total_score'           => $totalScore,
            'symptoms_count'        => $symptomsCount,
            'has_critical_symptom'  => $hasCritical,
            'risk_level'            => $riskLevel,
            'recommendation'        => $recommendation,
            'status'                => $status,
            'notes'                 => $request->input('notes') ?? ($contact ? "Skrining Anggota Serumah {$contact->name} ({$contact->relationship})" : ($hasCritical ? 'Skrining mandiri via Mobile App - Ditemukan gejala kritis' : 'Skrining mandiri via Mobile App')),
            'screened_at'           => Carbon::now(),
        ]);

        // Simpan seluruh rincian jawaban ke tabel screening_answers
        foreach ($answers as $ans) {
            $q = $questions->get($ans['question_id']);
            if (!$q) continue;

            $ansVal = (int) $ans['answer'];
            $groupName = $q->group ?: (optional($q->groupParent)->group ?: ($q->is_critical ? 'Skrining Gejala' : 'Faktor Risiko'));

            ScreeningAnswer::create([
                'screening_id'          => $screening->id,
                'screening_question_id' => $q->id,
                'question_text'         => $q->question ?? 'Pertanyaan Skrining',
                'group_name'            => $groupName,
                'answer'                => (string) $ansVal,
                'is_critical'           => (bool) $q->is_critical,
                'score'                 => $ansVal === 1 ? ($q->is_critical ? 2 : 1) : 0,
                'duration_days'         => $ans['duration_days'] ?? null,
                'notes'                 => $ansVal === 1 ? 'Menjawab Ya' : 'Menjawab Tidak',
            ]);
        }

        // Jika skrining ini untuk anggota serumah, perbarui status skrining di tabel close_contacts
        if ($contact) {
            $contact->screening_date   = Carbon::now()->format('Y-m-d');
            $contact->screening_result = $riskLevel;
            $contact->tpt_status       = ($riskLevel === 'Risiko Tinggi' || $riskLevel === 'Risiko Sedang') ? 'Perlu Evaluasi' : 'Tidak Perlu';
            $contact->save();
        }

        // Kembalikan hasil evaluasi dan data skrining tersimpan
        return response()->json([
            'message'        => 'Skrining berhasil diproses dan tersimpan di database.',
            'result'         => $hasCritical ? 'Terduga TB' : 'Bukan Terduga TB',
            'risk_level'     => $riskLevel,
            'recommendation' => $recommendation,
            'data'           => [
                'id'                    => $screening->id,
                'code'                  => $screening->code,
                'person_name'           => $screening->person_name,
                'close_contact_id'      => $screening->close_contact_id,
                'risk_level'            => $screening->risk_level,
                'status'                => $screening->status,
                'total_score'           => $screening->total_score,
                'symptoms_count'        => $screening->symptoms_count,
                'recommendation'        => $screening->recommendation,
                'screened_at'           => $screening->screened_at ? $screening->screened_at->toIso8601String() : null,
                'screened_at_formatted' => $screening->screened_at ? $screening->screened_at->isoFormat('D MMMM Y') : null,
            ],
        ]);
    }

    /**
     * GET /api/screening/{id}
     * Ambil rincian detail data satu skrining dan seluruh jawabannya.
     */
    public function show($id)
    {
        $screening = Screening::with(['category', 'answers'])->find($id);
        if (!$screening) {
            return response()->json([
                'success' => false,
                'message' => 'Data skrining tidak ditemukan.',
            ], 404);
        }

        $user = auth('sanctum')->user() ?? auth()->user();
        if ($user) {
            if ($screening->patient && method_exists($screening->patient, 'isAccessibleBy') && !$screening->patient->isAccessibleBy($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data skrining ini.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail data skrining berhasil dimuat.',
            'data'    => [
                'id'                    => $screening->id,
                'code'                  => $screening->code,
                'person_name'           => $screening->person_name,
                'close_contact_id'      => $screening->close_contact_id,
                'category_name'         => optional($screening->category)->name,
                'age'                   => $screening->age,
                'gender'                => $screening->gender,
                'risk_level'            => $screening->risk_level,
                'status'                => $screening->status,
                'total_score'           => $screening->total_score,
                'symptoms_count'        => $screening->symptoms_count,
                'has_critical_symptom'  => (bool) $screening->has_critical_symptom,
                'recommendation'        => $screening->recommendation,
                'notes'                 => $screening->notes,
                'screened_at'           => $screening->screened_at ? $screening->screened_at->format('Y-m-d H:i') : null,
                'screened_at_formatted' => $screening->screened_at ? $screening->screened_at->isoFormat('D MMMM Y') : null,
                'answers'               => $screening->answers->map(function ($a) {
                    return [
                        'id'            => $a->id,
                        'question_text' => $a->question_text,
                        'group_name'    => $a->group_name,
                        'answer'        => (int) $a->answer,
                        'score'         => $a->score,
                        'is_critical'   => (bool) $a->is_critical,
                    ];
                }),
            ],
        ], 200);
    }
}

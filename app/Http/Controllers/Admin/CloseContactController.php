<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloseContact;
use App\Models\Patient;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CloseContactController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->query('filter'); // 'keluarga', 'screening'
        $relationshipFilter = $request->query('relationship');
        $resultFilter = $request->query('result');
        $keyword = $request->query('q');

        $query = CloseContact::with(['patient.user', 'patient.puskesmas']);

        if ($filterType == 'keluarga') {
            $query->where('relationship', 'Keluarga Serumah');
        }

        if ($filterType == 'screening') {
            $query->whereNotNull('screening_result');
        }

        if ($relationshipFilter) {
            $query->where('relationship', $relationshipFilter);
        }

        if ($resultFilter) {
            $query->where('screening_result', $resultFilter);
        }

        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('contact_code', 'like', "%{$keyword}%")
                  ->orWhere('name', 'like', "%{$keyword}%")
                  ->orWhere('nik', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%")
                  ->orWhereHas('patient.user', function ($sub) use ($keyword) {
                      $sub->where('name', 'like', "%{$keyword}%");
                  });
            });
        }

        $contacts = $query->orderByDesc('id')->get();

        $pageTitle = 'Semua Kontak Erat Pasien TB';
        if ($filterType == 'keluarga') $pageTitle = 'Kontak Erat Keluarga Serumah';
        if ($filterType == 'screening') $pageTitle = 'Hasil Skrining & Investigasi Kontak';

        return view('admin.contacts.index', compact('contacts', 'filterType', 'relationshipFilter', 'resultFilter', 'keyword', 'pageTitle'))
            ->with([
                'title'        => $pageTitle,
                'pageSubtitle' => 'Investigasi kontak serumah dan lingkungan sekitar pasien TB untuk penemuan kasus dini dan pemberian TPT.'
            ]);
    }

    public function create()
    {
        $patients = Patient::with('user')->get();

        return view('admin.contacts.form', compact('patients'))->with([
            'title'       => 'Tambah Kontak Erat',
            'pageTitle'   => 'Pencatatan Kontak Erat Baru',
            'pageSubtitle'=> 'Registrasi data keluarga atau kontak erat pasien TB untuk dilakukan skrining.',
            'contact'     => new CloseContact(),
            'isEdit'      => false,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'name'             => 'required|string|max:255',
            'nik'              => 'nullable|string|max:20',
            'relationship'     => 'required|string',
            'gender'           => 'required|in:L,P',
            'age'              => 'required|integer|min:0|max:120',
            'phone'            => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'screening_date'   => 'nullable|date',
            'screening_result' => 'required|string',
            'tpt_status'       => 'required|string',
            'notes'            => 'nullable|string',
        ], [
            'patient_id.required'   => 'Pasien sumber penularan wajib dipilih.',
            'name.required'         => 'Nama kontak wajib diisi.',
            'relationship.required' => 'Hubungan kontak wajib dipilih.',
            'age.required'          => 'Umur kontak wajib diisi.',
        ]);

        $code = 'KONT-' . Carbon::now()->format('Ym') . '-' . str_pad(CloseContact::count() + 1, 4, '0', STR_PAD_LEFT);

        $contact = CloseContact::create([
            'contact_code'     => $code,
            'patient_id'       => $request->patient_id,
            'name'             => $request->name,
            'nik'              => $request->nik,
            'relationship'     => $request->relationship,
            'gender'           => $request->gender,
            'age'              => $request->age,
            'phone'            => $request->phone,
            'address'          => $request->address,
            'screening_date'   => $request->screening_date,
            'screening_result' => $request->screening_result,
            'tpt_status'       => $request->tpt_status,
            'notes'            => $request->notes,
        ]);

        ActivityLog::log('Tambah Kontak Erat', 'Kontak Erat', "Mencatat kontak erat {$contact->name} untuk Pasien ID {$contact->patient_id}.");

        return redirect()->route('admin.contacts.index')->with('success', 'Data kontak erat berhasil dicatat.');
    }

    public function show($id)
    {
        $contact = CloseContact::with('patient.user')->findOrFail($id);
        return redirect()->route('admin.contacts.edit', $contact->id);
    }

    public function edit($id)
    {
        $contact = CloseContact::with('patient.user')->findOrFail($id);
        $patients = Patient::with('user')->get();

        return view('admin.contacts.form', compact('contact', 'patients'))->with([
            'title'        => 'Edit Kontak Erat: ' . $contact->name,
            'pageTitle'    => 'Edit Data Kontak Erat',
            'pageSubtitle' => 'Perbarui hasil investigasi dan status terapi pencegahan (TPT).',
            'isEdit'       => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contact = CloseContact::findOrFail($id);

        $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'name'             => 'required|string|max:255',
            'nik'              => 'nullable|string|max:20',
            'relationship'     => 'required|string',
            'gender'           => 'required|in:L,P',
            'age'              => 'required|integer|min:0|max:120',
            'phone'            => 'nullable|string|max:20',
            'address'          => 'nullable|string',
            'screening_date'   => 'nullable|date',
            'screening_result' => 'required|string',
            'tpt_status'       => 'required|string',
            'notes'            => 'nullable|string',
        ]);

        $contact->update($request->all());

        ActivityLog::log('Perbarui Kontak Erat', 'Kontak Erat', "Memperbarui kontak {$contact->name} (Kode {$contact->contact_code}).");

        return redirect()->route('admin.contacts.index')->with('success', 'Data kontak erat berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $contact = CloseContact::findOrFail($id);
        $name = $contact->name;
        $contact->delete();

        ActivityLog::log('Hapus Kontak Erat', 'Kontak Erat', "Menghapus data kontak erat {$name}.");

        return redirect()->route('admin.contacts.index')->with('success', "Kontak erat {$name} berhasil dihapus.");
    }
}

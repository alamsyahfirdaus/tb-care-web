<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloseContact;
use App\Models\Patient;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

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

    public function form(?string $encryptedId = null)
    {
        $isEdit = false;
        $contact = new CloseContact();

        if ($encryptedId) {
            $id = decrypt_id($encryptedId);
            $contact = CloseContact::with('patient.user')->findOrFail($id);
            $isEdit = true;
        }

        $patients = Patient::with('user')->get();

        return view('admin.contacts.form', compact('contact', 'patients', 'isEdit'))->with([
            'title'        => $isEdit ? ('Edit Kontak Erat: ' . $contact->name) : 'Tambah Kontak Erat',
            'pageTitle'    => $isEdit ? 'Edit Data Kontak Erat' : 'Pencatatan Kontak Erat Baru',
            'pageSubtitle' => $isEdit ? 'Perbarui hasil investigasi dan status terapi pencegahan (TPT).' : 'Registrasi data keluarga atau kontak erat pasien TB untuk dilakukan skrining.',
        ]);
    }

    public function create()
    {
        return $this->form();
    }

    public function edit($id)
    {
        return $this->form($id);
    }

    public function save(Request $request, ?string $encryptedId = null)
    {
        $resolvedEncryptedId = $request->input('encrypted_id') ?? $encryptedId;
        $isEdit = !empty($resolvedEncryptedId);
        $contact = $isEdit ? CloseContact::findOrFail(decrypt_id($resolvedEncryptedId)) : new CloseContact();

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

        return DB::transaction(function () use ($request, $contact, $isEdit) {
            $data = [
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
            ];

            if (!$isEdit) {
                $code = 'KONT-' . Carbon::now()->format('Ym') . '-' . str_pad(CloseContact::count() + 1, 4, '0', STR_PAD_LEFT);
                $data['contact_code'] = $code;
                $contact->fill($data)->save();

                ActivityLog::log('Tambah Kontak Erat', 'Kontak Erat', "Mencatat kontak erat {$contact->name} untuk Pasien ID {$contact->patient_id}.");
                $message = 'Data kontak erat berhasil dicatat.';
            } else {
                $contact->update($data);

                ActivityLog::log('Perbarui Kontak Erat', 'Kontak Erat', "Memperbarui kontak {$contact->name} (Kode {$contact->contact_code}).");
                $message = 'Data kontak erat berhasil diperbarui.';
            }

            return redirect()->route('admin.contacts.index')->with('success', $message);
        });
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $id)
    {
        return $this->save($request, $id);
    }

    public function show($id)
    {
        $id = decrypt_id($id);
        $contact = CloseContact::with('patient.user')->findOrFail($id);
        return redirect()->route('admin.contacts.edit', $contact);
    }

    public function destroy($id)
    {
        $id = decrypt_id($id);
        $contact = CloseContact::findOrFail($id);
        $name = $contact->name;

        DB::transaction(function () use ($contact) {
            $contact->delete();
        });

        ActivityLog::log('Hapus Kontak Erat', 'Kontak Erat', "Menghapus data kontak erat {$name}.");

        return redirect()->route('admin.contacts.index')->with('success', "Kontak erat {$name} berhasil dihapus.");
    }
}

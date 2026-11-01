<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\District;
use App\Models\KaderArea;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KaderManagementByPJTBTest extends TestCase
{
    use DatabaseTransactions;

    protected $district;
    protected $subdistrictA;
    protected $subdistrictB;
    protected $puskesmasA;
    protected $puskesmasB;
    protected $villageA1;
    protected $villageA2;
    protected $villageB1;

    protected $adminUser;
    protected $pjtbAUser;
    protected $pjtbAOfficer;
    protected $pjtbBUser;
    protected $pjtbBOfficer;
    protected $kaderAUser;
    protected $kaderAOfficer;
    protected $kaderBUser;
    protected $kaderBOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Data Wilayah & Faskes
        $this->district = District::first() ?? District::create(['name' => 'Kabupaten Test', 'code' => 'K' . rand(10, 99)]);

        $this->puskesmasA = Puskesmas::with('subdistrict')->find(1) ?? Puskesmas::first();
        if (!$this->puskesmasA) {
            $subA = Subdistrict::create(['name' => 'Kec A', 'district_id' => $this->district->id, 'code' => 'SA' . rand(10, 99)]);
            $this->puskesmasA = Puskesmas::create(['name' => 'Puskesmas A Testing', 'subdistrict_id' => $subA->id, 'code' => 'PA' . rand(10, 99)]);
        }

        $this->puskesmasB = Puskesmas::with('subdistrict')->where('id', '!=', $this->puskesmasA->id)->first();
        if (!$this->puskesmasB) {
            $subB = Subdistrict::create(['name' => 'Kec B', 'district_id' => $this->district->id, 'code' => 'SB' . rand(10, 99)]);
            $this->puskesmasB = Puskesmas::create(['name' => 'Puskesmas B Testing', 'subdistrict_id' => $subB->id, 'code' => 'PB' . rand(10, 99)]);
        }

        $this->subdistrictA = $this->puskesmasA->subdistrict ?? Subdistrict::first();
        $this->subdistrictB = $this->puskesmasB->subdistrict ?? Subdistrict::orderBy('id', 'desc')->first();

        $villagesA = Village::where('subdistrict_id', $this->subdistrictA->id)->get();
        if ($villagesA->count() >= 2) {
            $this->villageA1 = $villagesA[0];
            $this->villageA2 = $villagesA[1];
        } else {
            $this->villageA1 = $villagesA->first() ?? Village::create(['name' => 'Desa A1 Test', 'subdistrict_id' => $this->subdistrictA->id, 'code' => 'VA' . rand(10, 99)]);
            $this->villageA2 = Village::create(['name' => 'Desa A2 Test', 'subdistrict_id' => $this->subdistrictA->id, 'code' => 'VB' . rand(10, 99)]);
        }

        $this->villageB1 = Village::where('subdistrict_id', $this->subdistrictB->id)->first();
        if (!$this->villageB1 || $this->villageB1->id == $this->villageA1->id) {
            $this->villageB1 = Village::create(['name' => 'Desa B1 Test', 'subdistrict_id' => $this->subdistrictB->id, 'code' => 'VC' . rand(10, 99)]);
        }

        // 2. Setup Super Administrator
        $this->adminUser = User::create([
            'name'         => 'Super Admin Testing',
            'username'     => 'admin_' . uniqid(),
            'phone'        => '0811' . rand(10000000, 99999999),
            'email'        => 'admin_' . uniqid() . '@test.com',
            'password'     => Hash::make('password123'),
            'user_type_id' => 1,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);

        // 3. Setup PJTB Puskesmas A
        $this->pjtbAUser = User::create([
            'name'         => 'PJTB Puskesmas A',
            'username'     => 'pjtb_a_' . uniqid(),
            'phone'        => '0812' . rand(10000000, 99999999),
            'email'        => 'pjtb_a_' . uniqid() . '@test.com',
            'password'     => Hash::make('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);

        $this->pjtbAOfficer = Officer::create([
            'user_id'         => $this->pjtbAUser->id,
            'officer_type_id' => 3, // PJTB Puskesmas
            'puskesmas_id'    => $this->puskesmasA->id,
            'district_id'     => $this->district->id,
        ]);

        // 4. Setup PJTB Puskesmas B
        $this->pjtbBUser = User::create([
            'name'         => 'PJTB Puskesmas B',
            'username'     => 'pjtb_b_' . uniqid(),
            'phone'        => '0813' . rand(10000000, 99999999),
            'email'        => 'pjtb_b_' . uniqid() . '@test.com',
            'password'     => Hash::make('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'P',
        ]);

        $this->pjtbBOfficer = Officer::create([
            'user_id'         => $this->pjtbBUser->id,
            'officer_type_id' => 3, // PJTB Puskesmas
            'puskesmas_id'    => $this->puskesmasB->id,
            'district_id'     => $this->district->id,
        ]);

        // 5. Setup Kader Puskesmas A
        $this->kaderAUser = User::create([
            'name'         => 'Kader Asli Puskesmas A',
            'username'     => 'kader_a_' . uniqid(),
            'phone'        => '0814' . rand(10000000, 99999999),
            'email'        => 'kader_a_' . uniqid() . '@test.com',
            'password'     => Hash::make('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'P',
        ]);

        $this->kaderAOfficer = Officer::create([
            'user_id'         => $this->kaderAUser->id,
            'officer_type_id' => 4, // Kader
            'puskesmas_id'    => $this->puskesmasA->id,
            'district_id'     => $this->district->id,
        ]);

        KaderArea::create([
            'officer_id'     => $this->kaderAOfficer->id,
            'subdistrict_id' => $this->subdistrictA->id,
            'village_id'     => $this->villageA1->id,
        ]);

        // 6. Setup Kader Puskesmas B
        $this->kaderBUser = User::create([
            'name'         => 'Kader Asli Puskesmas B',
            'username'     => 'kader_b_' . uniqid(),
            'phone'        => '0815' . rand(10000000, 99999999),
            'email'        => 'kader_b_' . uniqid() . '@test.com',
            'password'     => Hash::make('password123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'P',
        ]);

        $this->kaderBOfficer = Officer::create([
            'user_id'         => $this->kaderBUser->id,
            'officer_type_id' => 4, // Kader
            'puskesmas_id'    => $this->puskesmasB->id,
            'district_id'     => $this->district->id,
        ]);

        KaderArea::create([
            'officer_id'     => $this->kaderBOfficer->id,
            'subdistrict_id' => $this->subdistrictB->id,
            'village_id'     => $this->villageB1->id,
        ]);
    }

    /**
     * 1. PJTB dapat melihat daftar kader di Puskesmasnya sendiri, tetapi tidak melihat kader Puskesmas lain.
     */
    public function test_pjtb_can_view_kader_in_own_puskesmas_and_not_other_puskesmas()
    {
        $response = $this->actingAs($this->pjtbAUser)->get(route('kaders.index'));

        $response->assertStatus(200);
        $response->assertSee($this->kaderAUser->name);
        $response->assertDontSee($this->kaderBUser->name);
    }

    /**
     * 2. PJTB tidak dapat membuka detail kader dari Puskesmas lain (403 Forbidden).
     */
    public function test_pjtb_cannot_access_detail_of_kader_in_another_puskesmas()
    {
        $response = $this->actingAs($this->pjtbAUser)->get(route('kaders.show', $this->kaderBUser->encrypted_id));

        $response->assertStatus(403);
    }

    /**
     * 3. PJTB tidak dapat membuka halaman edit kader dari Puskesmas lain (403 Forbidden).
     */
    public function test_pjtb_cannot_access_edit_form_of_kader_in_another_puskesmas()
    {
        $response = $this->actingAs($this->pjtbAUser)->get(route('kaders.edit', $this->kaderBUser->encrypted_id));

        $response->assertStatus(403);
    }

    /**
     * 4. PJTB tidak dapat mengupdate data kader dari Puskesmas lain (403 Forbidden).
     */
    public function test_pjtb_cannot_update_kader_in_another_puskesmas()
    {
        $response = $this->actingAs($this->pjtbAUser)->put(route('kaders.update', $this->kaderBUser->encrypted_id), [
            'name'      => 'Nama Dibajak Oleh PJTB A',
            'username'  => $this->kaderBUser->username,
            'gender'    => 'P',
            'is_active' => 1,
        ]);

        $response->assertStatus(403);
        $this->assertEquals('Kader Asli Puskesmas B', $this->kaderBUser->fresh()->name);
    }

    /**
     * 5. PJTB tidak dapat menonaktifkan atau mengaktifkan kader dari Puskesmas lain (403 Forbidden).
     */
    public function test_pjtb_cannot_toggle_status_of_kader_in_another_puskesmas()
    {
        $response = $this->actingAs($this->pjtbAUser)->patch(route('kaders.toggle_status', $this->kaderBUser->encrypted_id));

        $response->assertStatus(403);
        $this->assertEquals(1, $this->kaderBUser->fresh()->is_active);
    }

    /**
     * 6. PJTB dapat menambahkan kader baru dan Puskesmas otomatis dikunci pada Puskesmas penugasannya.
     */
    public function test_pjtb_can_create_kader_and_puskesmas_is_enforced_to_own_puskesmas()
    {
        $username = 'kader_baru_' . uniqid();

        $response = $this->actingAs($this->pjtbAUser)->post(route('kaders.store'), [
            'name'                  => 'Kader Baru Pusk A',
            'username'              => $username,
            'email'                 => $username . '@example.com',
            'phone'                 => '082199887766',
            'gender'                => 'P',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active'             => 1,
            'village_ids'           => [$this->villageA1->id, $this->villageA2->id],
        ]);

        $response->assertSessionHasNoErrors();

        $newKader = User::where('username', $username)->first();
        $this->assertNotNull($newKader);
        $this->assertEquals(3, $newKader->user_type_id);
        $this->assertTrue(Hash::check('secret123', $newKader->password));

        $officer = $newKader->officer;
        $this->assertNotNull($officer);
        $this->assertEquals(4, $officer->officer_type_id); // Kader
        $this->assertEquals($this->puskesmasA->id, $officer->puskesmas_id);

        // Pastikan penugasan desa binaan tersimpan
        $assignedVillages = $officer->kaderAreas()->pluck('village_id')->toArray();
        $this->assertContains($this->villageA1->id, $assignedVillages);
        $this->assertContains($this->villageA2->id, $assignedVillages);
    }

    /**
     * 7. Manipulasi request puskesmas_id oleh PJTB diabaikan; kader tetap terdaftar di Puskesmas penugasannya.
     */
    public function test_pjtb_request_tampering_puskesmas_id_is_strictly_ignored()
    {
        $username = 'kader_tamper_' . uniqid();

        // PJTB A mencoba menyuntikkan puskesmas_id milik Puskesmas B
        $response = $this->actingAs($this->pjtbAUser)->post(route('kaders.store'), [
            'name'                  => 'Kader Tamper Puskesmas',
            'username'              => $username,
            'gender'                => 'P',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active'             => 1,
            'puskesmas_id'          => $this->puskesmasB->id, // Percobaan manipulasi
            'village_ids'           => [$this->villageA1->id],
        ]);

        $response->assertSessionHasNoErrors();

        $kader = User::where('username', $username)->first();
        $this->assertNotNull($kader);
        // Tetap wajib Puskesmas A
        $this->assertEquals($this->puskesmasA->id, $kader->officer->puskesmas_id);
    }

    /**
     * 8. Manipulasi request user_type_id dan officer_type_id oleh PJTB diabaikan (tidak bisa buat admin).
     */
    public function test_pjtb_request_tampering_role_is_strictly_ignored()
    {
        $username = 'kader_role_tamper_' . uniqid();

        $response = $this->actingAs($this->pjtbAUser)->post(route('kaders.store'), [
            'name'                  => 'Kader Tamper Role',
            'username'              => $username,
            'gender'                => 'L',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active'             => 1,
            'user_type_id'          => 1, // Percobaan ubah ke Super Admin
            'officer_type_id'       => 1, // Percobaan ubah ke Dinkes Provinsi
            'village_ids'           => [$this->villageA1->id],
        ]);

        $response->assertSessionHasNoErrors();

        $kader = User::where('username', $username)->first();
        $this->assertNotNull($kader);
        $this->assertEquals(3, $kader->user_type_id);
        $this->assertEquals(4, $kader->officer->officer_type_id); // Kader
    }

    /**
     * 9. Validasi username unik diterapkan.
     */
    public function test_unique_username_validation_is_enforced()
    {
        $response = $this->actingAs($this->pjtbAUser)->post(route('kaders.store'), [
            'name'                  => 'Kader Duplikat',
            'username'              => $this->kaderAUser->username, // Username sudah terdaftar
            'gender'                => 'P',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active'             => 1,
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    /**
     * 10. PJTB dapat mengedit profil kader dalam wilayah kewenangannya.
     */
    public function test_pjtb_can_edit_kader_in_own_puskesmas()
    {
        $response = $this->actingAs($this->pjtbAUser)->put(route('kaders.update', $this->kaderAUser->encrypted_id), [
            'name'        => 'Kader A Sukses Diperbarui',
            'username'    => $this->kaderAUser->username,
            'phone'       => '089911223344',
            'gender'      => 'P',
            'is_active'   => 1,
            'village_ids' => [$this->villageA1->id, $this->villageA2->id],
        ]);

        $response->assertSessionHasNoErrors();

        $this->kaderAUser->refresh();
        $this->assertEquals('Kader A Sukses Diperbarui', $this->kaderAUser->name);
        $this->assertEquals('089911223344', $this->kaderAUser->phone);
    }

    /**
     * 11. PJTB dapat mengubah dan menyinkronkan desa binaan kader.
     */
    public function test_pjtb_can_assign_and_change_village_binaan()
    {
        // Ubah desa binaan dari villageA1 menjadi villageA2
        $response = $this->actingAs($this->pjtbAUser)->put(route('kaders.update', $this->kaderAUser->encrypted_id), [
            'name'        => $this->kaderAUser->name,
            'username'    => $this->kaderAUser->username,
            'gender'      => 'P',
            'is_active'   => 1,
            'village_ids' => [$this->villageA2->id],
        ]);

        $response->assertSessionHasNoErrors();

        $assigned = $this->kaderAOfficer->fresh()->kaderAreas()->pluck('village_id')->toArray();
        $this->assertEquals([$this->villageA2->id], $assigned);
    }

    /**
     * 12. Men-toggle status nonaktif kader mencabut seluruh token Sanctum (akses mobile putus).
     */
    public function test_pjtb_can_deactivate_kader_and_sanctum_tokens_are_revoked()
    {
        // Berikan token Sanctum (simulasi login mobile aktif)
        $token = $this->kaderAUser->createToken('tb-care-mobile')->plainTextToken;
        $this->assertCount(1, $this->kaderAUser->tokens);

        // PJTB menonaktifkan akun kader
        $response = $this->actingAs($this->pjtbAUser)->patch(route('kaders.toggle_status', $this->kaderAUser->encrypted_id));

        $response->assertRedirect();
        $this->assertEquals(0, $this->kaderAUser->fresh()->is_active);

        // Seluruh token mobile harus terhapus
        $this->assertCount(0, $this->kaderAUser->fresh()->tokens);
    }

    /**
     * 13. Kader yang diaktifkan kembali dapat login normal dan memperoleh token baru.
     */
    public function test_reactivated_kader_can_login_again_via_api()
    {
        // 1. Nonaktifkan kader
        $this->kaderAUser->is_active = 0;
        $this->kaderAUser->save();

        // 2. Coba login saat nonaktif -> Ditolak (403 Forbidden)
        $responseLoginBlocked = $this->postJson('/api/login', [
            'username' => $this->kaderAUser->username,
            'password' => 'password123',
        ]);
        $responseLoginBlocked->assertStatus(403);
        $responseLoginBlocked->assertJson(['code' => 'ACCOUNT_DEACTIVATED']);

        // 3. PJTB mengaktifkan kembali kader
        $this->actingAs($this->pjtbAUser)->patch(route('kaders.toggle_status', $this->kaderAUser->encrypted_id));
        $this->assertEquals(1, $this->kaderAUser->fresh()->is_active);

        // 4. Coba login kembali -> Sukses dan dapat token
        $responseLoginSuccess = $this->postJson('/api/login', [
            'username' => $this->kaderAUser->username,
            'password' => 'password123',
        ]);
        $responseLoginSuccess->assertStatus(200);
        $responseLoginSuccess->assertJsonStructure(['message', 'token', 'user']);
    }

    /**
     * 14. Akun kader tidak diizinkan mengakses Web Admin (EnsureWebAdminRole).
     */
    public function test_kader_cannot_access_web_admin()
    {
        // Kader mencoba membuka /dashboard atau /kaders
        $responseDashboard = $this->actingAs($this->kaderAUser)->get(route('dashboard'));
        $responseKaders = $this->actingAs($this->kaderAUser)->get(route('kaders.index'));

        // Harus dialihkan atau ditolak (EnsureWebAdminRole melarang kader dan mengarahkan)
        $this->assertTrue(in_array($responseDashboard->status(), [302, 403]));
        $this->assertTrue(in_array($responseKaders->status(), [302, 403]));
    }

    /**
     * 15. Prinsip fail-closed: Kader tanpa wilayah binaan tidak dapat mengakses data pasien secara global.
     */
    public function test_fail_closed_kader_without_assigned_village_cannot_access_patients()
    {
        // Buat pasien di desa A1
        $patientUser = User::create([
            'name'         => 'Pasien Desa A1',
            'username'     => 'pasien_' . uniqid(),
            'user_type_id' => 2,
            'gender'       => 'L',
            'is_active'    => 1,
            'password'     => bcrypt('secret'),
        ]);

        $patient = Patient::create([
            'user_id'        => $patientUser->id,
            'puskesmas_id'   => $this->puskesmasA->id,
            'subdistrict_id' => $this->subdistrictA->id,
            'village_id'     => $this->villageA1->id,
            'nik'            => '3201' . rand(1000000000, 9999999999),
        ]);

        // Hapus seluruh penugasan wilayah kader A
        $this->kaderAOfficer->kaderAreas()->delete();

        // Scope accessibleBy harus mengembalikan 0 pasien (fail-closed)
        $accessiblePatients = Patient::accessibleBy($this->kaderAUser)->get();
        $this->assertEquals(0, $accessiblePatients->count());

        // Tugaskan desa A1 -> sekarang dapat melihat pasien
        KaderArea::create([
            'officer_id'     => $this->kaderAOfficer->id,
            'subdistrict_id' => $this->subdistrictA->id,
            'village_id'     => $this->villageA1->id,
        ]);

        $accessiblePatientsNow = Patient::accessibleBy($this->kaderAUser)->get();
        $this->assertTrue($accessiblePatientsNow->contains('id', $patient->id));
    }

    /**
     * 16. Super Administrator dapat mengelola seluruh kader lintas Puskesmas.
     */
    public function test_super_admin_can_manage_all_kaders_across_all_puskesmas()
    {
        // 1. Super Admin melihat kader A dan kader B
        $responseIndex = $this->actingAs($this->adminUser)->get(route('kaders.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee($this->kaderAUser->name);
        $responseIndex->assertSee($this->kaderBUser->name);

        // 2. Super Admin dapat melihat detail kader A dan kader B
        $responseShowA = $this->actingAs($this->adminUser)->get(route('kaders.show', $this->kaderAUser->encrypted_id));
        $responseShowA->assertStatus(200);

        $responseShowB = $this->actingAs($this->adminUser)->get(route('kaders.show', $this->kaderBUser->encrypted_id));
        $responseShowB->assertStatus(200);

        // 3. Super Admin dapat mengedit kader B dan memindahkan ke Puskesmas A
        $responseUpdate = $this->actingAs($this->adminUser)->put(route('kaders.update', $this->kaderBUser->encrypted_id), [
            'name'         => 'Kader B Dipindah ke Pusk A',
            'username'     => $this->kaderBUser->username,
            'gender'       => 'P',
            'is_active'    => 1,
            'puskesmas_id' => $this->puskesmasA->id,
        ]);
        $responseUpdate->assertSessionHasNoErrors();
        $this->assertEquals($this->puskesmasA->id, $this->kaderBUser->fresh()->officer->puskesmas_id);
    }

    /**
     * 17. Log aktivitas dicatat dengan benar untuk tindakan PJTB.
     */
    public function test_activity_logs_recorded_for_kader_actions()
    {
        $username = 'kader_log_' . uniqid();

        // 1. Log pembuatan kader
        $this->actingAs($this->pjtbAUser)->post(route('kaders.store'), [
            'name'                  => 'Kader Uji Log',
            'username'              => $username,
            'gender'                => 'P',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'is_active'             => 1,
            'village_ids'           => [$this->villageA1->id],
        ]);

        $createdLog = ActivityLog::where('user_id', $this->pjtbAUser->id)
            ->where('activity', 'Tambah Kader Baru')
            ->first();
        $this->assertNotNull($createdLog);
        $this->assertStringContainsString($username, $createdLog->description);

        // 2. Log penonaktifan kader
        $kader = User::where('username', $username)->first();
        $this->actingAs($this->pjtbAUser)->patch(route('kaders.toggle_status', $kader->encrypted_id));

        $deactivatedLog = ActivityLog::where('user_id', $this->pjtbAUser->id)
            ->where('activity', 'Nonaktifkan Akun Kader')
            ->first();
        $this->assertNotNull($deactivatedLog);
        $this->assertStringContainsString($username, $deactivatedLog->description);
    }

    /**
     * 18. Ekspor CSV kader dibatasi sesuai Puskesmas PJTB.
     */
    public function test_kader_export_csv_is_scoped_for_pjtb()
    {
        $response = $this->actingAs($this->pjtbAUser)->get(route('kaders.export'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString($this->kaderAUser->name, $content);
        $this->assertStringNotContainsString($this->kaderBUser->name, $content);
    }
}

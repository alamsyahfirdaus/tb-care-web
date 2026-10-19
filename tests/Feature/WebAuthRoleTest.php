<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Officer;
use App\Models\Patient;
use App\Models\Province;
use App\Models\Puskesmas;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WebAuthRoleTest extends TestCase
{
    use DatabaseTransactions;

    protected $adminUser;
    protected $petugasUser;
    protected $patientUser;
    protected $patientData;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Admin (user_type_id = 1)
        $this->adminUser = User::create([
            'name'         => 'Admin Web Test',
            'username'     => 'admin_test_' . uniqid(),
            'email'        => 'admin_test_' . uniqid() . '@tbcare.test',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 1,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);

        // 2. Setup Petugas / PJTB Puskesmas (user_type_id = 3)
        $province = Province::first() ?? Province::create(['name' => 'Jawa Barat']);
        $district = District::first() ?? District::create(['name' => 'Kota Tasikmalaya', 'province_id' => $province->id]);
        $subdistrict = Subdistrict::first() ?? Subdistrict::create(['name' => 'Cihideung', 'district_id' => $district->id]);
        $puskesmas = Puskesmas::first() ?? Puskesmas::create(['name' => 'Puskesmas Cihideung', 'subdistrict_id' => $subdistrict->id]);

        $this->petugasUser = User::create([
            'name'         => 'Petugas Web Test',
            'username'     => 'petugas_test_' . uniqid(),
            'email'        => 'petugas_test_' . uniqid() . '@tbcare.test',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 3,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);

        Officer::create([
            'user_id'         => $this->petugasUser->id,
            'officer_type_id' => 3,
            'puskesmas_id'    => $puskesmas->id,
            'district_id'     => $district->id,
        ]);

        // 3. Setup Pasien (user_type_id = 2)
        $village = Village::first() ?? Village::create(['name' => 'Nugraha', 'subdistrict_id' => $subdistrict->id]);

        $this->patientUser = User::create([
            'name'         => 'Pasien Web Test',
            'username'     => 'pasien_test_' . uniqid(),
            'email'        => 'pasien_test_' . uniqid() . '@tbcare.test',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 2,
            'is_active'    => 1,
            'gender'       => 'L',
        ]);

        $this->patientData = Patient::create([
            'user_id'              => $this->patientUser->id,
            'nik'                  => '3271' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $puskesmas->id,
            'subdistrict_id'       => $subdistrict->id,
            'village_id'           => $village->id,
            'rw'                   => '01',
            'rt'                   => '01',
            'treatment_start_date' => now()->toDateString(),
        ]);
    }

    /**
     * Skenario 1: Pasien login web dengan kredensial benar -> Ditolak backend dengan 422 JSON, tidak mendapat session
     */
    public function test_01_patient_login_web_with_correct_credentials_is_rejected()
    {
        $response = $this->postJson(route('login'), [
            'username' => $this->patientUser->username,
            'password' => 'secret123',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Akun pasien tidak dapat digunakan untuk login melalui Web Admin. Silakan gunakan aplikasi TB Care mobile.',
        ]);

        // Verifikasi pengguna TIDAK terautentikasi
        $this->assertFalse(Auth::check());
    }

    /**
     * Skenario 2: Pasien login web dengan kredensial salah -> Login gagal 422 JSON
     */
    public function test_02_patient_login_web_with_wrong_credentials_fails()
    {
        $response = $this->postJson(route('login'), [
            'username' => $this->patientUser->username,
            'password' => 'wrong_password_999',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Login Gagal! Username atau password tidak sesuai.',
        ]);

        $this->assertFalse(Auth::check());
    }

    /**
     * Skenario 3: Administrator login web -> Berhasil (200 JSON, terautentikasi)
     */
    public function test_03_admin_login_web_succeeds()
    {
        $response = $this->postJson(route('login'), [
            'username' => $this->adminUser->username,
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue(Auth::check());
        $this->assertEquals($this->adminUser->id, Auth::id());
        $this->assertEquals(1, Auth::user()->user_type_id);
    }

    /**
     * Skenario 4: Petugas login web -> Berhasil (200 JSON, terautentikasi)
     */
    public function test_04_officer_login_web_succeeds()
    {
        $response = $this->postJson(route('login'), [
            'username' => $this->petugasUser->username,
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertTrue(Auth::check());
        $this->assertEquals($this->petugasUser->id, Auth::id());
        $this->assertEquals(3, Auth::user()->user_type_id);
    }

    /**
     * Skenario 5: Pasien mengakses /dashboard secara langsung -> Ditolak, session dibersihkan, redirect ke login
     */
    public function test_05_patient_accessing_dashboard_directly_is_rejected()
    {
        $response = $this->actingAs($this->patientUser)->get(route('dashboard'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');

        // Pastikan session autentikasi telah dibersihkan
        $this->assertFalse(Auth::check());
    }

    /**
     * Skenario 6: Pasien mengakses URL CRUD secara langsung (/patients) -> Ditolak & session dibersihkan
     */
    public function test_06_patient_accessing_crud_directly_is_rejected()
    {
        $response = $this->actingAs($this->patientUser)->get(route('patients.index'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }

    /**
     * Skenario 7: Pasien mengakses endpoint AJAX operasional web -> Ditolak 403 Forbidden
     */
    public function test_07_patient_accessing_ajax_endpoint_is_rejected_with_403()
    {
        $response = $this->actingAs($this->patientUser)->getJson(route('regions.ajax.districts'));

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Akun pasien tidak dapat digunakan untuk Web Admin. Silakan gunakan aplikasi TB Care mobile.',
        ]);

        $this->assertFalse(Auth::check());
    }

    /**
     * Skenario 8: Pasien login melalui aplikasi Flutter (API /api/login) -> Tetap berhasil 200 dengan token
     */
    public function test_08_patient_api_login_via_flutter_succeeds()
    {
        $response = $this->postJson('/api/login', [
            'username' => $this->patientUser->username,
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'user' => [
                'id',
                'name',
                'username',
                'user_type_id',
                'patient' => [
                    'id',
                    'nik',
                ],
            ],
        ]);

        $this->assertEquals(2, $response->json('user.user_type_id'));
        $this->assertEquals($this->patientData->id, $response->json('user.patient.id'));
    }

    /**
     * Skenario 9: Administrator mengelola data pasien di Web -> Berhasil 200 OK
     */
    public function test_09_admin_can_manage_patient_data()
    {
        $response = $this->actingAs($this->adminUser)->get(route('patients.index'));
        $response->assertStatus(200);

        $responseShow = $this->actingAs($this->adminUser)->get(route('patients.show', $this->patientData->encrypted_id));
        $responseShow->assertStatus(200);
    }

    /**
     * Skenario 10: Petugas mengakses data pasien di luar cakupan faskes -> 403 Forbidden
     */
    public function test_10_officer_accessing_patient_outside_scope_is_forbidden()
    {
        // Buat puskesmas lain & pasien di luar cakupan petugas
        $subOther = Subdistrict::create(['name' => 'Subdistrict Other ' . uniqid(), 'district_id' => District::first()->id]);
        $pkmOther = Puskesmas::create(['name' => 'Pkm Other ' . uniqid(), 'subdistrict_id' => $subOther->id]);
        $userOther = User::create([
            'name'         => 'Pasien Luar Scope',
            'username'     => 'other_' . uniqid(),
            'email'        => 'other_' . uniqid() . '@test.com',
            'password'     => bcrypt('secret123'),
            'user_type_id' => 2,
        ]);
        $patientOther = Patient::create([
            'user_id'              => $userOther->id,
            'nik'                  => '3271' . rand(100000000000, 999999999999),
            'puskesmas_id'         => $pkmOther->id,
            'treatment_start_date' => now()->toDateString(),
        ]);

        // Petugas puskesmas A mengakses pasien puskesmas B -> 403 Forbidden
        $response = $this->actingAs($this->petugasUser)->get(route('patients.show', $patientOther->encrypted_id));
        $response->assertStatus(403);
    }

    /**
     * Skenario 11: Pengguna logout lalu membuka halaman terlindungi -> Diarahkan ke login
     */
    public function test_11_logged_out_user_accessing_protected_page_is_redirected_to_login()
    {
        $this->actingAs($this->adminUser)->get(route('logout'));

        $this->assertFalse(Auth::check());

        $response = $this->get(route('dashboard'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    /**
     * Skenario 12: Session lama milik pasien digunakan kembali -> Dibersihkan seketika dan tidak redirect loop
     */
    public function test_12_reused_patient_session_is_cleared_without_redirect_loop()
    {
        // Simulasikan session pasien aktif
        $this->withSession(['role' => 2]);
        $response = $this->actingAs($this->patientUser)->get(route('dashboard'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        // Cek bahwa saat mengakses halaman login setelahnya, tidak terjadi loop
        $loginPageResponse = $this->get(route('login'));
        $loginPageResponse->assertStatus(200);
    }
}

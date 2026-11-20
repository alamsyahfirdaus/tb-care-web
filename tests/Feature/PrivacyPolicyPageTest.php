<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyPageTest extends TestCase
{
    /**
     * Test halaman kebijakan privasi dapat diakses secara publik dengan status HTTP 200.
     */
    public function test_privacy_policy_page_is_publicly_accessible_without_login()
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertViewIs('privacy-policy');
        $response->assertSee('Kebijakan Privasi TB Care');
        $response->assertDontSee('Redirecting to');
    }

    /**
     * Test kelengkapan konten penting sesuai persyaratan Google Play Console & transparansi data TB Care.
     */
    public function test_privacy_policy_contains_all_required_sections_and_tbcare_context()
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);

        // Identitas Aplikasi & Pengelola
        $response->assertSee('TB Care');
        $response->assertSee('Universitas Muhammadiyah Tasikmalaya');
        $response->assertSee('id.alsyafir.tbcare');
        $response->assertSee('tbcare.umtas.ac.id');
        $response->assertSee('20 November 2026');

        // Kategori Data Spesifik TB Care
        $response->assertSee('Data Identitas dan Akun');
        $response->assertSee('Data Medis dan Demografis Pasien');
        $response->assertSee('Nomor Induk Kependudukan');
        $response->assertSee('Data Pengobatan dan Pemantauan Kepatuhan Minum Obat');
        $response->assertSee('Data Kontak Erat');
        $response->assertSee('Data Skrining Gejala');
        $response->assertSee('Jadwal Kontrol');
        $response->assertSee('Pemeriksaan Klinis');
        $response->assertSee('Fitur Konsultasi');
        $response->assertSee('Firebase Cloud Messaging');

        // Bagian Regulasi & Prinsip Privasi
        $response->assertSee('Tujuan Penggunaan Data');
        $response->assertSee('Akses dan Pembatasan Pengelolaan Data');
        $response->assertSee('Penyimpanan Data');
        $response->assertSee('Pembagian dan Pengungkapan Data');
        $response->assertSee('Keamanan Data');
        $response->assertSee('Retensi dan Masa Simpan Data');
        $response->assertSee('Hak dan Permintaan Pengguna');
        $response->assertSee('Pasien Usia Anak');
        $response->assertSee('Perubahan Kebijakan Privasi');
        $response->assertSee('Kontak Resmi Pengelola');
    }

    /**
     * Test halaman kebijakan privasi dapat dibuka oleh pengguna yang sedang login tanpa terhalang.
     */
    public function test_authenticated_users_can_also_access_privacy_policy()
    {
        // Pengguna pasien maupun petugas tetap bisa membuka halaman tanpa redirect paksa
        $user = User::factory()->make([
            'id' => 9999,
            'user_type_id' => 2, // Pasien
            'username' => 'pasien_test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertSee('Kebijakan Privasi TB Care');
    }

    /**
     * Test portal beranda memuat tautan menuju halaman kebijakan privasi.
     */
    public function test_portal_page_contains_link_to_privacy_policy()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee(route('privacy-policy'));
        $response->assertSee('Kebijakan Privasi');
    }
}

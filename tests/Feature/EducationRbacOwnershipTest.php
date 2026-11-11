<?php

namespace Tests\Feature;

use App\Models\EducationalMaterial;
use App\Models\Officer;
use App\Models\User;
use Tests\TestCase;

class EducationRbacOwnershipTest extends TestCase
{
    private function createAdmin(): User
    {
        $rand = rand(10000, 99999);
        return User::create([
            'name'         => 'Admin ' . $rand,
            'username'     => 'admin_' . $rand,
            'email'        => 'admin_' . $rand . '@tbcare.id',
            'phone'        => '0811' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 1,
            'is_active'    => true,
        ]);
    }

    private function createPetugas(): User
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Petugas ' . $rand,
            'username'     => 'petugas_' . $rand,
            'email'        => 'petugas_' . $rand . '@tbcare.id',
            'phone'        => '0812' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        $puskId = \App\Models\Puskesmas::first()?->id ?? 1;
        $distId = \App\Models\District::first()?->id ?? 1;

        Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => 3, // PJTB
            'puskesmas_id'    => $puskId,
            'district_id'     => $distId,
        ]);

        return $user;
    }

    private function createKader(): User
    {
        $rand = rand(10000, 99999);
        $user = User::create([
            'name'         => 'Kader ' . $rand,
            'username'     => 'kader_' . $rand,
            'email'        => 'kader_' . $rand . '@tbcare.id',
            'phone'        => '0813' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 3,
            'is_active'    => true,
        ]);

        $puskId = \App\Models\Puskesmas::first()?->id ?? 1;
        $distId = \App\Models\District::first()?->id ?? 1;

        Officer::create([
            'user_id'         => $user->id,
            'officer_type_id' => 4, // Kader
            'puskesmas_id'    => $puskId,
            'district_id'     => $distId,
        ]);

        return $user;
    }

    private function createPasien(): User
    {
        $rand = rand(10000, 99999);
        return User::create([
            'name'         => 'Pasien ' . $rand,
            'username'     => 'pasien_' . $rand,
            'email'        => 'pasien_' . $rand . '@tbcare.id',
            'phone'        => '0814' . $rand . rand(100, 999),
            'password'     => bcrypt('password123'),
            'user_type_id' => 2, // Pasien
            'is_active'    => true,
        ]);
    }

    public function test_all_roles_can_view_all_published_materials()
    {
        $petugasA = $this->createPetugas();
        $petugasB = $this->createPetugas();
        $admin = $this->createAdmin();
        $kader = $this->createKader();
        $pasien = $this->createPasien();

        $matA = EducationalMaterial::create([
            'title_material' => 'Materi A dari Petugas A',
            'description'    => 'Desc',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678901',
            'is_publish'     => 1,
            'created_by'     => $petugasA->id,
        ]);

        $matB = EducationalMaterial::create([
            'title_material' => 'Materi B dari Petugas B',
            'description'    => 'Desc',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678902',
            'is_publish'     => 1,
            'created_by'     => $petugasB->id,
        ]);

        // Pasien sees both published materials
        $resPasien = $this->actingAs($pasien, 'sanctum')->getJson('/api/education?status=published');
        $resPasien->assertStatus(200);
        $ids = collect($resPasien->json('data'))->pluck('id')->all();
        $this->assertContains($matA->id, $ids);
        $this->assertContains($matB->id, $ids);

        // Kader sees both published materials
        $resKader = $this->actingAs($kader, 'sanctum')->getJson('/api/education?status=published');
        $resKader->assertStatus(200);
        $idsKader = collect($resKader->json('data'))->pluck('id')->all();
        $this->assertContains($matA->id, $idsKader);
        $this->assertContains($matB->id, $idsKader);

        // Petugas A sees both published materials (no created_by filter for published!)
        $resPetugasA = $this->actingAs($petugasA, 'sanctum')->getJson('/api/education?status=published');
        $resPetugasA->assertStatus(200);
        $idsPetugas = collect($resPetugasA->json('data'))->pluck('id')->all();
        $this->assertContains($matA->id, $idsPetugas);
        $this->assertContains($matB->id, $idsPetugas);
    }

    public function test_draft_access_control()
    {
        $admin = $this->createAdmin();
        $petugasA = $this->createPetugas();
        $petugasB = $this->createPetugas();
        $kader = $this->createKader();
        $pasien = $this->createPasien();

        $draftA = EducationalMaterial::create([
            'title_material' => 'Draft Petugas A',
            'description'    => 'Draft Desc',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678903',
            'is_publish'     => 0,
            'created_by'     => $petugasA->id,
        ]);

        $draftB = EducationalMaterial::create([
            'title_material' => 'Draft Petugas B',
            'description'    => 'Draft Desc',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678904',
            'is_publish'     => 0,
            'created_by'     => $petugasB->id,
        ]);

        // Pasien & Kader accessing draft list returns 403
        $this->actingAs($pasien, 'sanctum')->getJson('/api/education?status=draft')->assertStatus(403);
        $this->actingAs($kader, 'sanctum')->getJson('/api/education?status=draft')->assertStatus(403);

        // Petugas A only sees draft A
        $resA = $this->actingAs($petugasA, 'sanctum')->getJson('/api/education?status=draft');
        $resA->assertStatus(200);
        $idsA = collect($resA->json('data'))->pluck('id')->all();
        $this->assertContains($draftA->id, $idsA);
        $this->assertNotContains($draftB->id, $idsA);

        // Admin sees all drafts
        $resAdmin = $this->actingAs($admin, 'sanctum')->getJson('/api/education?status=draft');
        $resAdmin->assertStatus(200);
        $idsAdmin = collect($resAdmin->json('data'))->pluck('id')->all();
        $this->assertContains($draftA->id, $idsAdmin);
        $this->assertContains($draftB->id, $idsAdmin);
    }

    public function test_ownership_restrictions_on_edit_and_delete()
    {
        $admin = $this->createAdmin();
        $petugasA = $this->createPetugas();
        $petugasB = $this->createPetugas();
        $kader = $this->createKader();
        $pasien = $this->createPasien();

        $matA = EducationalMaterial::create([
            'title_material' => 'Materi Milik Petugas A',
            'description'    => 'Desc',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678905',
            'is_publish'     => 1,
            'created_by'     => $petugasA->id,
        ]);

        // Petugas B attempts to edit Petugas A's material -> 403 Forbidden
        $editPayload = [
            'id'             => $matA->id,
            'title_material' => 'Updated by B',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678905',
        ];
        $this->actingAs($petugasB, 'sanctum')->postJson('/api/education/store', $editPayload)->assertStatus(403);

        // Petugas B attempts to delete Petugas A's material -> 403 Forbidden
        $this->actingAs($petugasB, 'sanctum')->deleteJson('/api/education/' . $matA->id)->assertStatus(403);

        // Kader attempts to edit or delete -> 403 Forbidden
        $this->actingAs($kader, 'sanctum')->postJson('/api/education/store', $editPayload)->assertStatus(403);
        $this->actingAs($kader, 'sanctum')->deleteJson('/api/education/' . $matA->id)->assertStatus(403);

        // Pasien attempts to edit or delete -> 403 Forbidden
        $this->actingAs($pasien, 'sanctum')->postJson('/api/education/store', $editPayload)->assertStatus(403);
        $this->actingAs($pasien, 'sanctum')->deleteJson('/api/education/' . $matA->id)->assertStatus(403);

        // Petugas A can successfully edit their own material
        $resUpdateA = $this->actingAs($petugasA, 'sanctum')->postJson('/api/education/store', [
            'id'             => $matA->id,
            'title_material' => 'Updated by Owner A',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678905',
        ]);
        $resUpdateA->assertStatus(200);
        $this->assertEquals('Updated by Owner A', $matA->fresh()->title_material);

        // Admin can edit Petugas A's material, and created_by is PRESERVED (NOT overwritten)
        $resAdminUpdate = $this->actingAs($admin, 'sanctum')->postJson('/api/education/store', [
            'id'             => $matA->id,
            'title_material' => 'Updated by Super Admin',
            'material_type'  => 'video',
            'video_url'      => 'https://youtube.com/watch?v=12345678905',
        ]);
        $resAdminUpdate->assertStatus(200);
        $matFresh = $matA->fresh();
        $this->assertEquals('Updated by Super Admin', $matFresh->title_material);
        $this->assertEquals($petugasA->id, $matFresh->created_by); // MUST NOT BE ADMIN ID!

        // Admin can delete any material
        $this->actingAs($admin, 'sanctum')->deleteJson('/api/education/' . $matA->id)->assertStatus(200);
        $this->assertNull(EducationalMaterial::find($matA->id));
    }
}

<?php

namespace Tests\Unit;

use App\Models\Officer;
use App\Models\Patient;
use App\Models\User;
use App\Policies\PatientPolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class KaderScopingLogicTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Kader can assign/manage patients in their assigned village regardless of RW/RT.
     */
    public function test_kader_can_assign_territory_in_same_village_any_rw_rt()
    {
        $policy = new PatientPolicy();

        // Setup mock user and officer
        $user = new User(['user_type_id' => 3]);
        $officer = Mockery::mock(Officer::class)->makePartial();
        $officer->officer_type_id = 4; // Kader
        $officer->puskesmas_id = 1;

        // Mock kaderAreas relationship returning assigned village [10, 11]
        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('pluck->filter->unique->all')
            ->andReturn([10, 11]);

        $officer->shouldReceive('kaderAreas')->andReturn($hasManyMock);
        $user->setRelation('officer', $officer);

        // Case 1: Same village (10), RW 01, RT 01 -> TRUE
        $this->assertTrue($policy->canAssignTerritory($user, 10, '01', '01', 1));

        // Case 2: Same village (10), different RW 05, RT 09 -> TRUE
        $this->assertTrue($policy->canAssignTerritory($user, 10, '05', '09', 1));

        // Case 3: Same village (10), RW null, RT null -> TRUE
        $this->assertTrue($policy->canAssignTerritory($user, 10, null, null, 1));

        // Case 4: Different village (20), any RW -> FALSE
        $this->assertFalse($policy->canAssignTerritory($user, 20, '01', '01', 1));

        // Case 5: Empty/null village -> FALSE (fail-closed)
        $this->assertFalse($policy->canAssignTerritory($user, null, '01', '01', 1));
        $this->assertFalse($policy->canAssignTerritory($user, '', '01', '01', 1));

        // Case 6: Different puskesmas -> FALSE
        $this->assertFalse($policy->canAssignTerritory($user, 10, '01', '01', 99));
    }

    /**
     * Test: Kader with NO assigned areas fails closed (access denied).
     */
    public function test_kader_without_assigned_areas_fails_closed()
    {
        $policy = new PatientPolicy();

        $user = new User(['user_type_id' => 3]);
        $officer = Mockery::mock(Officer::class)->makePartial();
        $officer->officer_type_id = 4;
        $officer->puskesmas_id = 1;

        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('pluck->filter->unique->all')
            ->andReturn([]); // No assigned areas

        $officer->shouldReceive('kaderAreas')->andReturn($hasManyMock);
        $user->setRelation('officer', $officer);

        $this->assertFalse($policy->canAssignTerritory($user, 10, '01', '01', 1));
    }

    /**
     * Test: Patient::scopeAccessibleBy constructs village-based filter without RW/RT constraints.
     */
    public function test_kader_patient_query_scope_filters_by_village_without_rw_restriction()
    {
        $user = new User(['user_type_id' => 3]);
        $officer = Mockery::mock(Officer::class)->makePartial();
        $officer->officer_type_id = 4;
        $officer->puskesmas_id = 5;

        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('pluck->filter->unique->values->all')
            ->andReturn([42]);

        $officer->shouldReceive('kaderAreas')->andReturn($hasManyMock);
        $user->setRelation('officer', $officer);

        $query = Patient::query()->accessibleBy($user);
        $sql = $query->toSql();

        // SQL should filter by village_id IN (?) and puskesmas_id = ?
        $this->assertStringContainsString('village_id', $sql);
        $this->assertStringContainsString('puskesmas_id', $sql);

        // SQL MUST NOT restrict by rw or rt
        $this->assertStringNotContainsString('`rw`', $sql);
        $this->assertStringNotContainsString('"rw"', $sql);
        $this->assertStringNotContainsString('`rt`', $sql);
        $this->assertStringNotContainsString('"rt"', $sql);
    }

    /**
     * Test: Patient::scopeAccessibleBy fails closed when kader has no assigned areas.
     */
    public function test_kader_patient_query_scope_fails_closed_when_no_areas()
    {
        $user = new User(['user_type_id' => 3]);
        $officer = Mockery::mock(Officer::class)->makePartial();
        $officer->officer_type_id = 4;
        $officer->puskesmas_id = 5;

        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('pluck->filter->unique->values->all')
            ->andReturn([]);

        $officer->shouldReceive('kaderAreas')->andReturn($hasManyMock);
        $user->setRelation('officer', $officer);

        $query = Patient::query()->accessibleBy($user);
        $sql = $query->toSql();

        $this->assertStringContainsString('1 = 0', $sql);
    }

    /**
     * Test: Administrator bypasses territory restrictions.
     */
    public function test_admin_can_assign_any_territory()
    {
        $policy = new PatientPolicy();
        $admin = new User(['user_type_id' => 1]);

        $this->assertTrue($policy->canAssignTerritory($admin, 999, '99', '99', 999));
    }
}

<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $officerMappings = [
            // Dinkes Provinsi
            [
                'username'        => 'admindinkesprov',
                'officer_type_id' => 1,
                'district_id'     => 1,
                'puskesmas_id'    => null,
            ],
            // Dinkes Kab/Kota
            [
                'username'        => 'admindinkeskota',
                'officer_type_id' => 2,
                'district_id'     => 2,
                'puskesmas_id'    => null,
            ],
            // PJTB Puskesmas
            [
                'username'        => 'pjtb001',
                'officer_type_id' => 3,
                'district_id'     => null,
                'puskesmas_id'    => 1,
            ],
            // Kader Puskesmas
            [
                'username'        => 'kader001',
                'officer_type_id' => 4,
                'district_id'     => null,
                'puskesmas_id'    => 1,
            ],
        ];

        // Safely verify existence of district & puskesmas foreign keys if table has entries
        $firstDistrict = DB::table('districts')->value('id');
        $secondDistrict = DB::table('districts')->skip(1)->value('id') ?? $firstDistrict;
        $firstPuskesmas = DB::table('puskesmas')->value('id');

        foreach ($officerMappings as $mapping) {
            $userId = DB::table('users')->where('username', $mapping['username'])->value('id');
            if (!$userId) {
                continue;
            }

            $districtId = null;
            if ($mapping['district_id'] !== null) {
                $districtId = ($mapping['district_id'] == 1 ? $firstDistrict : $secondDistrict) ?? $mapping['district_id'];
            }

            $puskesmasId = null;
            if ($mapping['puskesmas_id'] !== null) {
                $puskesmasId = $firstPuskesmas ?? $mapping['puskesmas_id'];
            }

            DB::table('officers')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'officer_type_id' => $mapping['officer_type_id'],
                    'district_id'     => $districtId,
                    'puskesmas_id'    => $puskesmasId,
                    'updated_at'      => Carbon::now(),
                    'created_at'      => Carbon::now(),
                ]
            );
        }
    }
}

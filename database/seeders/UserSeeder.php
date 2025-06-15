<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            // Super Admin
            [
                'name' => 'Alamsyah Firdaus',
                'email' => 'alamsyahfirdaus@gmail.com',
                'username' => 'alamsyahfirdaus',
                'gender' => 'L',
                'place_of_birth' => 'Tasikmalaya',
                'date_of_birth' => '1998-07-31',
                'telephone' => '089693839624',
                'email_verified_at' => null,
                'password' => Hash::make('alamsyahfirdaus'),
                'profile' => null,
                'user_type_id' => 1, // Super Admin
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Admin Dinkes Provinsi
            [
                'name' => 'Adm Dinkes Provinsi',
                'email' => 'adm.prov@dinkes.go.id',
                'username' => 'admindinkesprov',
                'gender' => 'L',
                'place_of_birth' => 'Bandung',
                'date_of_birth' => '1985-03-12',
                'telephone' => '081234567891',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'profile' => null,
                'user_type_id' => 1, // Admin Dinkes Provinsi
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Admin Dinkes Kabupaten/Kota
            [
                'name' => 'Adm Dinkes Kota',
                'email' => 'admin.kota@dinkes.go.id',
                'username' => 'admindinkeskota',
                'gender' => 'L',
                'place_of_birth' => 'Tasikmalaya',
                'date_of_birth' => '1986-04-20',
                'telephone' => '081234567892',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'profile' => null,
                'user_type_id' => 1, // Admin Dinkes Kab/Kota
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Penanggung Jawab TB
            [
                'name' => 'Penanggung Jawab TB',
                'email' => 'pjtb@puskesmas.id',
                'username' => 'pjtb001',
                'gender' => 'P',
                'place_of_birth' => 'Tasikmalaya',
                'date_of_birth' => '1990-08-25',
                'telephone' => '082112345678',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'profile' => null,
                'user_type_id' => 2, // Petugas Puskesmas
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Kader Puskesmas
            [
                'name' => 'Kader Puskesmas',
                'email' => 'kader@puskesmas.id',
                'username' => 'kader001',
                'gender' => 'P',
                'place_of_birth' => 'Tasikmalaya',
                'date_of_birth' => '1992-11-14',
                'telephone' => '082223334444',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'profile' => null,
                'user_type_id' => 2, // Petugas Puskesmas
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Pasien
            [
                'name' => 'Firdaus Alamsyah',
                'email' => 'firdaus@example.com',
                'username' => 'firdaus123',
                'gender' => 'L',
                'place_of_birth' => 'Garut',
                'date_of_birth' => '2001-01-01',
                'telephone' => '089911223344',
                'email_verified_at' => null,
                'password' => Hash::make('password123'),
                'profile' => null,
                'user_type_id' => 3, // Pasien
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}

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

            // Administrator (Super Admin)
            [
                'name'              => 'Alamsyah Firdaus',
                'email'             => 'alamsyahfirdaus@gmail.com',
                'username'          => 'alamsyahfirdaus',
                'gender'            => 'L',
                'place_of_birth'    => 'Tasikmalaya',
                'date_of_birth'     => '1998-07-31',
                'phone'             => '089693839624',
                'email_verified_at' => now(),
                'password'          => Hash::make('alamsyahfirdaus'),
                'photo'             => null,
                'user_type_id'      => 1, // Administrator
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

            // Pasien TB
            [
                'name'              => 'Firdaus Alamsyah',
                'email'             => 'firdaus@example.com',
                'username'          => 'firdaus123',
                'gender'            => 'L',
                'place_of_birth'    => 'Garut',
                'date_of_birth'     => '2001-01-01',
                'phone'             => '089911223344',
                'email_verified_at' => now(),
                'password'          => Hash::make('password123'),
                'photo'             => null,
                'user_type_id'      => 2, // Pasien TB
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

            // Petugas (Admin Dinkes Provinsi)
            [
                'name'              => 'Admin Dinkes Provinsi',
                'email'             => 'adm.prov@dinkes.go.id',
                'username'          => 'admindinkesprov',
                'gender'            => 'L',
                'place_of_birth'    => 'Bandung',
                'date_of_birth'     => '1985-03-12',
                'phone'             => '081234567891',
                'email_verified_at' => now(),
                'password'          => Hash::make('password123'),
                'photo'             => null,
                'user_type_id'      => 3, // Petugas
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

            // Petugas (Admin Dinkes Kab/Kota)
            [
                'name'              => 'Admin Dinkes Kota',
                'email'             => 'admin.kota@dinkes.go.id',
                'username'          => 'admindinkeskota',
                'gender'            => 'L',
                'place_of_birth'    => 'Tasikmalaya',
                'date_of_birth'     => '1986-04-20',
                'phone'             => '081234567892',
                'email_verified_at' => now(),
                'password'          => Hash::make('password123'),
                'photo'             => null,
                'user_type_id'      => 3, // Petugas
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

            // Petugas (PJTB Puskesmas)
            [
                'name'              => 'Penanggung Jawab TB',
                'email'             => 'pjtb@puskesmas.id',
                'username'          => 'pjtb001',
                'gender'            => 'P',
                'place_of_birth'    => 'Tasikmalaya',
                'date_of_birth'     => '1990-08-25',
                'phone'             => '082112345678',
                'email_verified_at' => now(),
                'password'          => Hash::make('password123'),
                'photo'             => null,
                'user_type_id'      => 3, // Petugas
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

            // Petugas (Kader Puskesmas)
            [
                'name'              => 'Kader Puskesmas',
                'email'             => 'kader@puskesmas.id',
                'username'          => 'kader001',
                'gender'            => 'P',
                'place_of_birth'    => 'Tasikmalaya',
                'date_of_birth'     => '1992-11-14',
                'phone'             => '082223334444',
                'email_verified_at' => now(),
                'password'          => Hash::make('password123'),
                'photo'             => null,
                'user_type_id'      => 3, // Petugas
                'remember_token'    => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }
}

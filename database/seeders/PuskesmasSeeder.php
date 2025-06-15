<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PuskesmasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('puskesmas')->insert([
            // Kota Tasikmalaya
            ['name' => 'Kawalu',        'code' => 'P3278010201', 'address' => 'Jl. Perintis Kemerdekaan, Kec. Kawalu'],
            ['name' => 'Karanganyar',   'code' => 'P3278010202', 'address' => 'Kel. Karanganyar, Kec. Kawalu'],
            ['name' => 'Urug',          'code' => null,          'address' => 'Jl. Syeh Abdul Muhyi No.2, Kec. Kawalu'],
            ['name' => 'Tamansari',     'code' => 'P3278020201', 'address' => 'Kel. Sukalaksana, Kec. Tamansari'],
            ['name' => 'Cibereum',      'code' => 'P3278030201', 'address' => 'Jl. Raya Manonjaya, Kec. Cibereum'],
            ['name' => 'Purbaratu',     'code' => 'P3278031201', 'address' => 'Kel. Purbaratu, Kec. Purbaratu'],
            ['name' => 'Kahuripan',     'code' => 'P3278040201', 'address' => 'Jl. Siliwangi, Kec. Tawang'],
            ['name' => 'Tawang',        'code' => 'P3278040202', 'address' => 'Kel. Tawangsari, Kec. Tawang'],
            ['name' => 'Cihideung',     'code' => 'P3278050201', 'address' => 'Jl. Paseh No.227, Kec. Cihideung'],
            ['name' => 'Cilembang',     'code' => 'P3278050202', 'address' => 'Jl. Bebedilan, Kec. Cihideung'],

            // Kabupaten Tasikmalaya
            ['name' => 'Cipatujah',       'code' => 'P3206010101', 'address' => 'Jl. Raya Cipatujah 123, Kec. Cipatujah'],
            ['name' => 'Karang Nunggal', 'code' => 'P3206020101', 'address' => 'Ds. Karangnunggal No. 12, Kec. Karangnunggal'],
            ['name' => 'Cikalong',        'code' => 'P3206030101', 'address' => 'Ds. Cikalong, Kec. Cikalong'],
            ['name' => 'Pancatengah',     'code' => 'P3206040201', 'address' => 'Jl. Raya Pancatengah, Kec. Panca Tengah'],
            ['name' => 'Cikatomas',       'code' => 'P3206050101', 'address' => 'Ds. Cikatomas, Kec. Cikatomas'],
            ['name' => 'Cibalong',        'code' => 'P3206060201', 'address' => 'Ds. Cibalong, Kec. Cibalong'],
            ['name' => 'Parungponteng',   'code' => 'P3206061201', 'address' => 'Ds. Parungponteng, Kec. Parung Ponteng'],
            ['name' => 'Bantarkalong',    'code' => 'P3206070201', 'address' => 'Ds. Simpang, Kec. Bantar Kalong'],
            ['name' => 'Bojongasih',      'code' => 'P3206071101', 'address' => 'Ds. Bojongasih, Kec. Bojong Asih'],
            ['name' => 'Culamega',        'code' => 'P3206072201', 'address' => 'Ds. Culamega, Kec. Culamega'],
            ['name' => 'Bojonggambir',    'code' => 'P3206080201', 'address' => 'Ds. Bojonggambir, Kec. Bojonggambir'],
            ['name' => 'Sodonghilir',     'code' => 'P3206090201', 'address' => 'Ds. Sodonghilir, Kec. Sodonghilir'],
            ['name' => 'Taraju',          'code' => 'P3206100101', 'address' => 'Jl. Raya Taraju Rt 006/01, Kec. Taraju'],
            ['name' => 'Salawu',          'code' => 'P3206110201', 'address' => 'Jl. Raya Salawu 118, Kec. Salawu'],
            ['name' => 'Puspahiang',      'code' => 'P3206111201', 'address' => 'Jl. Raya Puspahiang 7, Kec. Puspahiang'],
            ['name' => 'Tanjungjaya',     'code' => 'P3206120201', 'address' => 'Ds. Cibalanarik, Kec. Tanjung Jaya'],
            ['name' => 'Sukaraja',        'code' => 'P3206130101', 'address' => 'Ds. Sukaraja, Kec. Sukaraja'],
            ['name' => 'Salopa',          'code' => 'P3206140201', 'address' => 'Jl. Raya Salopa 226, Kec. Salopa'],
            ['name' => 'Jatiwaras',       'code' => 'P3206141201', 'address' => 'Ds. Jatiwaras, Kec. Jatiwaras'],
            ['name' => 'Cineam',          'code' => 'P3206150201', 'address' => 'Ds. Cineam, Kec. Cineam'],
            ['name' => 'Manonjaya',       'code' => 'P3206160101', 'address' => 'Jl. Perumahan 6, Kec. Manonjaya'],
            ['name' => 'Gunungtanjung',   'code' => 'P3206161201', 'address' => 'Ds. Gunungtanjung, Kec. Gunungtanjung'],
            ['name' => 'Singaparna',      'code' => 'P3206190201', 'address' => 'Ds. Singaparna, Kec. Singaparna'],
            ['name' => 'Tinewati',        'code' => 'P3206190102', 'address' => 'Jl. Raya Singaparna, Kec. Singaparna'],
            ['name' => 'Sukarame',        'code' => 'P3206191101', 'address' => 'Ds. Sukarame, Kec. Sukarame'],
            ['name' => 'Mangunreja',      'code' => 'P3206192201', 'address' => 'Ds. Mangunreja, Kec. Mangunreja'],
            ['name' => 'Cigalontang',     'code' => 'P3206200101', 'address' => 'Ds. Cigalontang, Kec. Cigalontang'],
            ['name' => 'Leuwisari',       'code' => 'P3206210201', 'address' => 'Ds. Leuwisari, Kec. Leuwisari'],
            ['name' => 'Karangjaya',      'code' => 'P3206210202', 'address' => 'Ds. Sirnajaya, Kec. Leuwisari'],
            ['name' => 'Sariwangi',       'code' => 'P3206211201', 'address' => 'Ds. Sariwangi, Kec. Sariwangi'],
            ['name' => 'Cisaruni',        'code' => 'P3206212201', 'address' => 'Jl. Kantor Pos, Kec. Padakembang'],
            ['name' => 'Sukaratu',        'code' => 'P3206221101', 'address' => 'Ds. Sukaratu, Kec. Sukaratu'],
            ['name' => 'Cisayong',        'code' => 'P3206230201', 'address' => 'Ds. Cisayong, Kec. Cisayong'],
            ['name' => 'Sukahening',      'code' => 'P3206231201', 'address' => 'Ds. Sukahening, Kec. Sukahening'],
            ['name' => 'Rajapolah',       'code' => 'P3206240101', 'address' => 'Ds. Rajapolah, Kec. Rajapolah'],
            ['name' => 'Jamanis',         'code' => 'P3206250101', 'address' => 'Ds. Jamanis, Kec. Jamanis'],
            ['name' => 'Ciawi',           'code' => 'P3206260101', 'address' => 'Jl. Panumbangan, Kec. Ciawi'],
            ['name' => 'Kadipaten',       'code' => 'P3206261201', 'address' => 'Pos Ciawi 46156, Kec. Kadipaten'],
            ['name' => 'Pagerageung',     'code' => 'P3206270201', 'address' => 'Tanjaknangsi 18, Kec. Pagerageung'],
            ['name' => 'Sukaresik',       'code' => 'P3206271201', 'address' => 'Ds. Sukaresik, Kec. Sukaresik'],
        ]);
    }
}

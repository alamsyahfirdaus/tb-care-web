<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $table = 'districts';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public static function getAllDistricts()
    {
        return self::with('province')
            ->orderBy('name', 'asc')
            ->get()
            ->mapWithKeys(function ($district) {
                return [
                    $district->id => $district->name . ' - Prov. ' . ($district->province ? $district->province->name : 'Unknown'),
                ];
            })->toArray();
    }

    public static function getDistrictById($id)
    {
        $district = self::with('province')->find($id);

        if (!$district) {
            return [
                'id' => null,
                'name' => '-',
            ];
        }

        $province = $district->province ? $district->province->name : 'Unknown';

        return [
            'id' => $district->id,
            'name' => $district->name . ' - Prov. ' . $province,
        ];
    }
}

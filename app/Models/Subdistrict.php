<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subdistrict extends Model
{
    use HasFactory;

    protected $table = 'subdistricts';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public static function getAllSubdistricts()
    {
        return self::with('district.province')
            ->orderBy('name', 'asc')
            ->get()
            ->mapWithKeys(function ($subdistrict) {
                return [
                    $subdistrict->id => 'Kec. ' . $subdistrict->name . ' - ' . $subdistrict->district->name . ' - Prov. ' . $subdistrict->district->province->name,
                ];
            })->toArray();
    }

    public static function getSubdistrictById($id)
    {
        $subdistrict = self::with('district.province')->find($id);
    
        if (!$subdistrict) {
            return [
                'id' => null,
                'name' => '-',
            ];
        }

        $district = $subdistrict->district;
        $province = $district ? $district->province : null;
    
        $name = 'Kec. ' . $subdistrict->name;
        if ($district) {
            $name .= ' - ' . $district->name;
            if ($province) {
                $name .= ' - Prov. ' . $province->name;
            }
        }

        return [
            'id' => $subdistrict->id,
            'name' => $name,
        ];
    }
}

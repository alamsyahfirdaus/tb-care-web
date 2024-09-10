<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Puskesmas extends Model
{
    use HasFactory;

    protected $table = 'puskesmas';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'subdistrict_id');
    }

    public static function getAllPuskesmas()
    {
        return self::with('subdistrict.district.province')
            ->orderBy('name', 'asc')
            ->get()
            ->mapWithKeys(function ($puskesmas) {
                $subdistrict = $puskesmas->subdistrict;
                $district = $subdistrict ? $subdistrict->district : null;
                $province = $district ? $district->province : null;
    
                $name = $puskesmas->name;
                if ($subdistrict) {
                    $name .= ' (Kec. ' . $subdistrict->name;
                    if ($district) {
                        $name .= ' - ' . $district->name;
                        if ($province) {
                            $name .= ' - Prov. ' . $province->name;
                        }
                    }
                    $name .= ')';
                }
    
                return [
                    $puskesmas->id => $name,
                ];
            })->toArray();
    }

    public static function getPuskesmasById($id)
    {
        $puskesmas = self::with('subdistrict.district.province')->find($id);
    
        if (!$puskesmas) {
            return [
                'id' => null,
                'name' => '-',
            ];
        }
    
        $subdistrict = $puskesmas->subdistrict;
        $district = $subdistrict ? $subdistrict->district : null;
        $province = $district ? $district->province : null;
    
        $name = $puskesmas->name;
        if ($subdistrict) {
            $name .= ' (Kec. ' . $subdistrict->name;
            if ($district) {
                $name .= ' - ' . $district->name;
                if ($province) {
                    $name .= ' - Prov. ' . $province->name;
                }
            }
            $name .= ')';
        }
    
        return [
            'id' => $puskesmas->id,
            'name' => $name,
        ];
    }
    
}

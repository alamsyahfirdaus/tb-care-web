<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Officer extends Model
{
    use HasFactory;

    protected $table = 'officers';
    protected $primaryKey = 'id';

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function puskesmas()
    {
        return $this->belongsTo(Puskesmas::class, 'puskesmas_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function kaderAreas()
    {
        return $this->hasMany(KaderArea::class, 'officer_id');
    }

    public function isKader()
    {
        return (int)$this->officer_type_id === 4;
    }

    public function isPJTB()
    {
        return (int)$this->officer_type_id === 3;
    }

    public function isDinkesKabKota()
    {
        return (int)$this->officer_type_id === 2;
    }

    public function isDinkesProvinsi()
    {
        return (int)$this->officer_type_id === 1;
    }

    public function getOfficerTypeNameAttribute(): string
    {
        return match ((int)$this->officer_type_id) {
            1 => 'Dinkes Provinsi',
            2 => 'Dinkes Kab/Kota',
            3 => 'PJTB Puskesmas',
            4 => 'Kader Puskesmas',
            default => 'Petugas',
        };
    }

    public function getScopeDescriptionAttribute(): string
    {
        if ($this->officer_type_id == 1) {
            $dist = $this->district;
            return $dist && $dist->province ? 'Dinkes Prov. ' . $dist->province->name : 'Dinkes Provinsi';
        }

        if ($this->officer_type_id == 2) {
            return $this->district ? $this->district->name : 'Dinkes Kab/Kota';
        }

        if ($this->officer_type_id == 3) {
            return $this->puskesmas ? 'Puskesmas ' . $this->puskesmas->name : 'Puskesmas';
        }

        if ($this->officer_type_id == 4) {
            $areas = $this->kaderAreas()->with('village')->get();
            if ($areas->isNotEmpty()) {
                $areaStrs = $areas->map(function ($a) {
                    $vil = optional($a->village)->name ?? '';
                    $rw = $a->rw ? ' RW ' . $a->rw : '';
                    $rt = $a->rt ? ' RT ' . $a->rt : '';
                    return trim("{$vil}{$rw}{$rt}");
                })->filter()->values()->all();

                if (!empty($areaStrs)) {
                    return 'Wilayah: ' . implode(', ', $areaStrs);
                }
            }
            return $this->puskesmas ? 'Kader Puskesmas ' . $this->puskesmas->name : 'Kader';
        }

        return 'Petugas';
    }
}

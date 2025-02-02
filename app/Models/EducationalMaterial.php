<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EducationalMaterial extends Model
{
    use HasFactory;

    protected $table = 'educational_materials';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $guarded = [];

    public static function getAllMaterials()
    {
        if (session('role')) {
            $materials = session('role') == 1
                ? self::orderBy('id', 'desc')->get()
                : self::where('user_id', Auth::id())->orderBy('id', 'desc')->get();
        } else {
            $materials = self::where('is_publish', 1)->orderBy('id', 'desc')->paginate(4);
        }        

        return $materials;
    }

    public static function getMaterialById($id)
    {
        $material = self::getAllMaterials();
        return collect($material)->firstWhere('id', $id);
    }
}

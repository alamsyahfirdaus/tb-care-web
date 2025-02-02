<?php

namespace App\Http\Controllers;

use App\Models\EducationalMaterial;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $data = array(
            'title' => 'Beranda'
        );
        return view('home', $data);
    }

    public function dashboard()
    {
        $data = array(
            'title'     => 'Sistem Pengobatan Tuberkulosis',
            'materials' => EducationalMaterial::getAllMaterials(),
        );
        return view('dashboard', $data);
    }
}

<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class  extends Controller
{
    public function index()
    {
        return view('admin.configuracion');
    }
}
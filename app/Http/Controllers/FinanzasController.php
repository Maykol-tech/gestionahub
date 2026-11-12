<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class FinanzasController extends Controller
{
    public function pagina() { return $this->index(); } public function index()
    {
        return view('admin.finanzas');
    }
}
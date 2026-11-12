<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class BotWhatsappController extends Controller
{
    public function index() {
        return view('admin.modulos.bot');
    }
    public function pagina() {
        return $this->index();
    }
}
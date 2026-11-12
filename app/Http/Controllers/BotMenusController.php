<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class BotMenusController extends Controller
{
    public function index() {
        return view('admin.modulos.bot_menus');
    }
    public function pagina() {
        return $this->index();
    }
}
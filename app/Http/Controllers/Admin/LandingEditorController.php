<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LandingConfig;
use Illuminate\Http\Request;

class LandingEditorController extends Controller {
    public function index() {
        $config = LandingConfig::first() ?? new LandingConfig();
        return view('admin.landing_editor', compact('config'));
    }
    public function update(Request $request) {
        $config = LandingConfig::firstOrCreate([]);
        $config->update($request->all());
        return back()->with('success', 'Landing actualizada.');
    }
}
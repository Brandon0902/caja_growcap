<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PanelControlController extends Controller
{
    public function index(Request $request)
    {
        $defaultUrl = route('admin.permisos.index', ['panel' => 1]);

        return view('panel-control.index', [
            'defaultUrl' => $defaultUrl,
        ]);
    }
}

<?php

namespace Modules\Home\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('home::dashboard.index');
    }
}

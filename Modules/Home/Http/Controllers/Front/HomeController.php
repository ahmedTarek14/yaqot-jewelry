<?php

namespace Modules\Home\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

class HomeController extends Controller
{
    public function index()
    {
        // The catalog arrives with the Product module; until then the hero has no call to action.
        $catalogUrl = Route::has('front.catalog') ? route('front.catalog') : null;

        return view('home::front.index', compact('catalogUrl'));
    }
}

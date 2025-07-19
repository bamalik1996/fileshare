<?php

namespace App\Http\Controllers;

use App\Models\SharedText;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return view('home');
    }

    public function aboutUs()
    {
        return view('about');
    }
    public function feedback()
    {
        return view('feedback');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\SharedText;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {


      //  return [ Carbon::now(),SharedText::get()];
        return view('home');
    }

    public function howItWorks()
    {
        return view('how-it-works');
    }

    public function faq()
    {
        return view('faq');
    }

    public function feedback()
    {
        return view('feedback');
    }

    public function comingSoon()
    {
        return view('coming-soon');
    }
}

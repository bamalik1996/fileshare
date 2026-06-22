<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function show(): View
    {
        $path = resource_path('docs/api-v2.md');
        $markdown = File::exists($path) ? File::get($path) : '# API v2\n\nDocumentation pending.';

        return view('docs.api', ['markdown' => $markdown]);
    }
}

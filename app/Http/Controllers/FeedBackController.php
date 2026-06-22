<?php

namespace App\Http\Controllers;

use App\Services\RecaptchaVerifier;
use Illuminate\Http\Request;

class FeedBackController extends Controller
{
    public function __construct(private readonly RecaptchaVerifier $recaptcha)
    {
    }

    public function store(Request $request)
    {
        $this->recaptcha->validateRequest($request);

        $request->validate([
            'type'    => 'required|string|max:255',
            'email'   => 'required|email',
            'message' => 'required|string',
        ]);

        $line = "==============================\n" .
            'Date: ' . now()->format('Y-m-d H:i:s') . "\n" .
            'IP: ' . $request->ip() . "\n" .
            'Type: ' . $request->type . "\n" .
            'Email: ' . $request->email . "\n" .
            "Message:\n" . $request->message . "\n" .
            "==============================\n\n";

        $filePath = storage_path('app/form-submissions.txt');
        file_put_contents($filePath, $line, FILE_APPEND);

        return response()->json(['message' => 'Thank you for your feedback!']);
    }
}

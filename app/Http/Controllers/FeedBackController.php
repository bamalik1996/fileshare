<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FeedBackController extends Controller
{
    public function store(Request $request)
    {
        // Step 1: Validate input fields
        $request->validate([
            'type' => 'required|string|max:255',
            'email' => 'required|email',
            'message' => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        // Step 2: Verify CAPTCHA
        $recaptchaSecret = config('app.recpatcha.RECAPTCHA_SECRET_KEY');
        $captchaResponse = $request->input('g-recaptcha-response');

        $verification = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'  => $recaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $request->ip()
        ]);

        if (!($verification->json()['success'] ?? false)) {
            return response()->json(['message' => 'Captcha verification failed.'], 422);
        }

        // Step 3: Save data to file (Readable format)
        $line = "==============================\n" .
            "Date: " . now()->format('Y-m-d H:i:s') . "\n" .
            "IP: " . $request->ip() . "\n" .
            "Type: " . $request->type . "\n" .
            "Email: " . $request->email . "\n" .
            "Message:\n" . $request->message . "\n" .
            "==============================\n\n";

        // Create file if not exist and append new entry
        $filePath = storage_path('app/form-submissions.txt');
        file_put_contents($filePath, $line, FILE_APPEND);

        // Step 4: Return success
        return response()->json(['message' => 'Thank you for your feedback!']);
    }
}

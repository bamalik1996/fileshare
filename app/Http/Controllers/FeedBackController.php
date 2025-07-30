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
            'g-recaptcha-response' => 'required' // CAPTCHA field
        ]);

        // Step 2: Verify CAPTCHA
        $recaptchaSecret = config('app.recpatcha.RECAPTCHA_SECRET_KEY'); // Add in .env
        $captchaResponse = $request->input('g-recaptcha-response');

        $verification = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $captchaResponse,
            'remoteip' => $request->ip()
        ]);

        if (!($verification->json()['success'] ?? false)) {
            return response()->json(['message' => 'Captcha verification failed.'], 422);
        }

        // Step 3: Save feedback (optional)
        // Feedback::create([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'message' => $request->message,
        // ]);

        // Step 4: Return success response
        return response()->json(['message' => 'Thank you for your feedback!']);
    }
}

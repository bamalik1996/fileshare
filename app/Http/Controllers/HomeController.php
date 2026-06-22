<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\Share;
use App\Models\SharedText;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index(Request $request)
    {


      //  return [ Carbon::now(),SharedText::get()];
        return view('home', [
            // QR slot wiring (task 7.3, design.md > Component 1 frontend).
            // The recipient-facing home view renders an `<img>` pointing at
            // /qr/{slug} for the current principal's active Share, if any.
            // The lookup mirrors ShareController::findActiveShareForIp so
            // both controllers see the same row through the deprecation
            // window. When no active Share exists (first-time visitor, or
            // after expiry), the slot is omitted; the slot reappears once
            // the next save creates / renews the Share aggregate.
            'share' => $this->resolveActiveShareForView($request),
        ]);
    }

    /**
     * Return the most-recent active Share owned by the request's principal,
     * or `null` when none exists. Wrapped in a try/catch so a transient
     * database hiccup (or a bootstrap that runs before migrations) cannot
     * take the marketing/home page down — the QR slot is purely additive.
     */
    private function resolveActiveShareForView(Request $request): ?Share
    {
        try {
            $principal = $request->principal();

            return Share::query()
                ->ownedBy($principal)
                ->where('expires_at', '>', Carbon::now())
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            Log::warning('HomeController: failed to resolve active share for QR slot', [
                'reason' => $e->getMessage(),
            ]);

            return null;
        }
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

    public function smartFileOrganization()
    {
        return view('smart-file-organization');
    }
}

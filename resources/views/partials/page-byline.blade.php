{{-- Visible byline + freshness line for guide/landing pages.
     Usage: @include('partials.page-byline', ['updated' => '2026-10-21']) --}}
<p class="hiw-step-text" style="display:flex;flex-wrap:wrap;align-items:center;gap:.4rem .9rem;font-size:.9rem;opacity:.85;margin:.75rem 0 0;">
    <span><i class="fas fa-user-pen" aria-hidden="true"></i> By {{ config('airtoshare.content_author') }}</span>
    <span><i class="fas fa-rotate" aria-hidden="true"></i> Updated {{ \Carbon\Carbon::parse($updated)->format('M j, Y') }}</span>
</p>

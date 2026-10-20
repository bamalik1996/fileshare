{{-- Dropzone-styled CTA that lands the visitor directly on the homepage
     uploader (home.blade.php scrolls to it when ?share=files is present).
     Usage: @include('partials.share-tool-cta', ['label' => 'optional heading']) --}}
<a href="{{ url('/?share=files') }}" class="hiw-card" style="display:block;text-align:center;padding:1.9rem 1.5rem;border:2px dashed var(--border-color, #c7cfdb);text-decoration:none;">
    <span style="display:block;font-size:1.65rem;margin-bottom:.4rem;"><i class="fas fa-cloud-arrow-up" aria-hidden="true"></i></span>
    <span class="hiw-step-title" style="display:block;">{{ $label ?? 'Send a file right now — free' }}</span>
    <span class="hiw-step-text" style="display:block;margin-top:.35rem;">Opens the share tool · no app, no signup · up to 500&nbsp;MB per file</span>
</a>

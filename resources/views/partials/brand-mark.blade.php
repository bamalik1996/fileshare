{{-- Brand mark: the only place the brand gradient appears.
     Usage: @include('partials.brand-mark', ['size' => 'sm']) --}}
<span class="rd-mark{{ ($size ?? '') === 'sm' ? ' rd-mark--sm' : '' }}" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M7 17L17 7"></path>
        <path d="M9 7h8v8"></path>
    </svg>
</span>

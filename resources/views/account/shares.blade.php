@extends('layouts.app')

@section('title', 'My Shares – AirToShare')
@section('description', 'Manage your AirToShare account shares, favourites, and public gallery links.')

@section('content')
    <div class="account-page">
        <div class="account-hero">
            <h1 class="account-title">
                <i class="fas fa-folder-open" aria-hidden="true"></i>
                My Shares
            </h1>
            <p class="account-subtitle">Manage your saved shares, favourites, and public links.</p>
        </div>

        <div class="info-panel account-stats">
            <div class="info-item">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <strong>Account:</strong> {{ $account->email }}
            </div>
            <div class="info-item">
                <i class="fas fa-share-alt" aria-hidden="true"></i>
                <strong>Active shares:</strong> {{ $shares->count() }}
            </div>
            <div class="info-item">
                <i class="fas fa-star" aria-hidden="true"></i>
                <strong>Favourites:</strong> {{ $favouriteCount }}/50
                <span class="account-stat-hint">Star a share below to pin it</span>
            </div>
        </div>

        <div class="account-toolbar">
            <a href="{{ url('/') }}" class="modern-btn secondary">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                Back to sharing
            </a>
            <a href="{{ url('/') }}" class="modern-btn">
                <i class="fas fa-plus" aria-hidden="true"></i>
                New share
            </a>
            <form method="POST" action="{{ route('account.destroy') }}"
                onsubmit="return confirm('Delete your account and all shares? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="modern-btn danger">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                    Delete account
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="auth-alert auth-alert-success account-alert" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($shares->isEmpty())
            <div class="modern-card account-empty">
                <div class="empty-state-icon">
                    <i class="fas fa-cloud-upload-alt" aria-hidden="true"></i>
                </div>
                <h2 class="account-empty-title">No shares yet</h2>
                <p class="account-empty-text">
                    Upload files or save text on the home page while <strong>logged in</strong>.
                    Your shares will appear here automatically.
                </p>
                <p class="account-empty-hint">
                    Tip: use the <i class="fas fa-star" aria-hidden="true"></i> star on any share card to add it to favourites (up to 50).
                </p>
                <a href="{{ url('/') }}" class="modern-btn">
                    <i class="fas fa-rocket" aria-hidden="true"></i>
                    Start sharing
                </a>
            </div>
        @else
            <div class="account-share-list">
                @foreach ($shares as $share)
                    @php
                        $preview = $share->markdown_source
                            ?: strip_tags((string) ($share->text_content ?? ''));
                        $preview = \Illuminate\Support\Str::limit(trim($preview), 120);
                        $isExpired = $share->isExpired();
                    @endphp
                    <article class="modern-card account-share-card" data-share-uuid="{{ $share->uuid }}">
                        <div class="account-share-card-header">
                            <div class="account-share-meta">
                                @if ($share->is_favourite)
                                    <span class="account-badge account-badge-fav">
                                        <i class="fas fa-star" aria-hidden="true"></i> Favourite
                                    </span>
                                @endif
                                @if ($share->is_e2ee)
                                    <span class="account-badge account-badge-e2ee">
                                        <i class="fas fa-lock" aria-hidden="true"></i> E2EE
                                    </span>
                                @endif
                                @if ($share->password_hash)
                                    <span class="account-badge account-badge-lock">
                                        <i class="fas fa-key" aria-hidden="true"></i> Password
                                    </span>
                                @endif
                                @if ($share->public_slug)
                                    <span class="account-badge account-badge-public">
                                        <i class="fas fa-globe" aria-hidden="true"></i> Public
                                    </span>
                                @endif
                            </div>
                            <button type="button"
                                class="account-icon-btn favourite-btn{{ $share->is_favourite ? ' is-active' : '' }}"
                                title="{{ $share->is_favourite ? 'Remove from favourites' : 'Add to favourites' }}"
                                data-url="{{ route('account.shares.favourite', $share) }}"
                                aria-label="Toggle favourite">
                                <i class="fas fa-star{{ $share->is_favourite ? '' : '-o' }}" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="account-share-preview">
                            @if ($preview !== '')
                                {{ $preview }}
                            @else
                                <span class="account-share-preview-empty">No text content</span>
                            @endif
                        </div>

                        <div class="account-share-details">
                            <div class="account-share-detail">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                @if ($isExpired)
                                    <span class="account-expired">Expired {{ $share->expires_at->diffForHumans() }}</span>
                                @else
                                    Expires {{ $share->expires_at->diffForHumans() }}
                                    <span class="account-expiry-date">({{ $share->expires_at->format('M j, Y g:i A') }})</span>
                                @endif
                            </div>
                            <div class="account-share-detail">
                                <i class="fas fa-paperclip" aria-hidden="true"></i>
                                {{ $share->media_count ?? 0 }} {{ Str::plural('file', $share->media_count ?? 0) }}
                            </div>
                            <div class="account-share-detail account-share-link-row">
                                <i class="fas fa-link" aria-hidden="true"></i>
                                <code class="account-share-link-url">{{ url('/s/' . $share->uuid) }}</code>
                                <button type="button" class="account-copy-btn"
                                    data-copy-text="{{ url('/s/' . $share->uuid) }}"
                                    title="Copy share link" aria-label="Copy share link">
                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="account-share-detail account-share-id">
                                <i class="fas fa-fingerprint" aria-hidden="true"></i>
                                <span class="account-share-id-label">ID</span>
                                <code>{{ Str::limit($share->uuid, 18) }}</code>
                            </div>
                        </div>

                        @if ($share->public_slug)
                            <div class="account-public-link">
                                <i class="fas fa-link" aria-hidden="true"></i>
                                <a href="{{ route('public.share.show', $share->public_slug) }}" target="_blank" rel="noopener">
                                    {{ url('/p/' . $share->public_slug) }}
                                </a>
                                <button type="button" class="account-copy-btn"
                                    data-copy-text="{{ url('/p/' . $share->public_slug) }}"
                                    title="Copy public link" aria-label="Copy public link">
                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                </button>
                            </div>
                        @endif

                        <div class="account-share-actions">
                            <button type="button" class="modern-btn account-copy-btn"
                                data-copy-text="{{ url('/s/' . $share->uuid) }}">
                                <i class="fas fa-copy" aria-hidden="true"></i>
                                Copy link
                            </button>
                            <a class="modern-btn secondary" href="{{ route('share.show', $share) }}">
                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                Open
                            </a>
                            @if ($share->public_slug)
                                <button type="button" class="modern-btn secondary public-btn"
                                    data-action="disable"
                                    data-url="{{ route('account.shares.public.disable', $share) }}">
                                    <i class="fas fa-eye-slash" aria-hidden="true"></i>
                                    Disable public
                                </button>
                            @else
                                <button type="button" class="modern-btn secondary public-btn"
                                    data-action="enable"
                                    data-url="{{ route('account.shares.public.enable', $share) }}">
                                    <i class="fas fa-globe" aria-hidden="true"></i>
                                    Enable public
                                </button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <script>
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrf ? csrf.content : '';

            function toast(type, title, message) {
                if (typeof window.showToast === 'function') {
                    window.showToast(type, title, message);
                }
            }

            document.querySelectorAll('.account-copy-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var text = btn.getAttribute('data-copy-text') || '';
                    if (!text || !navigator.clipboard) return;
                    navigator.clipboard.writeText(text).then(function () {
                        toast('success', 'Copied', 'Copied to clipboard.');
                    }).catch(function () {
                        toast('error', 'Copy failed', 'Could not copy to clipboard.');
                    });
                });
            });

            document.querySelectorAll('.favourite-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.disabled = true;
                    fetch(btn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        if (data.status === 'success') {
                            toast('success', 'Favourites', data.favourited ? 'Added to favourites.' : 'Removed from favourites.');
                            location.reload();
                        } else {
                            toast('error', 'Error', data.message || 'Could not update favourite.');
                            btn.disabled = false;
                        }
                    }).catch(function () {
                        toast('error', 'Error', 'Could not update favourite.');
                        btn.disabled = false;
                    });
                });
            });

            document.querySelectorAll('.public-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.disabled = true;
                    fetch(btn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        if (data.status === 'success') {
                            var msg = btn.dataset.action === 'enable'
                                ? 'Public gallery enabled.'
                                : 'Public gallery disabled.';
                            toast('success', 'Public link', msg);
                            location.reload();
                        } else {
                            toast('error', 'Error', data.message || 'Could not update public link.');
                            btn.disabled = false;
                        }
                    }).catch(function () {
                        toast('error', 'Error', 'Could not update public link.');
                        btn.disabled = false;
                    });
                });
            });
        })();
    </script>
@endsection

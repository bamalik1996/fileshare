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
            <button type="button" id="create-collect-btn" class="modern-btn"
                data-url="{{ route('account.collect.create') }}">
                <i class="fas fa-inbox" aria-hidden="true"></i>
                Create file request
            </button>
            <a href="{{ route('account.settings') }}" class="modern-btn secondary">
                <i class="fas fa-palette" aria-hidden="true"></i>
                Branding
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
                        $stats = $analytics[$share->id] ?? ['views' => 0, 'downloads' => 0];
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
                                @if ($share->isCollect())
                                    <span class="account-badge account-badge-collect">
                                        <i class="fas fa-inbox" aria-hidden="true"></i> File request
                                    </span>
                                @endif
                                <span class="account-badge account-badge-revoked" @if (! $share->isRevoked()) hidden @endif>
                                    <i class="fas fa-ban" aria-hidden="true"></i> Revoked
                                </span>
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
                            <div class="account-share-detail account-share-stats">
                                <span title="Views"><i class="fas fa-eye" aria-hidden="true"></i> {{ $stats['views'] }}</span>
                                <span title="Downloads"><i class="fas fa-download" aria-hidden="true"></i> {{ $stats['downloads'] }}</span>
                                <span title="Download limit" class="account-dl-limit-label">
                                    <i class="fas fa-gauge-high" aria-hidden="true"></i>
                                    {{ $share->download_count }}/<span class="account-dl-limit-max">{{ $share->max_downloads ?? '∞' }}</span>
                                </span>
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

                        @if ($share->isCollect() && $share->collect_slug)
                            <div class="account-public-link">
                                <i class="fas fa-inbox" aria-hidden="true"></i>
                                <a href="{{ route('collect.show', $share->collect_slug) }}" target="_blank" rel="noopener">
                                    {{ url('/collect/' . $share->collect_slug) }}
                                </a>
                                <button type="button" class="account-copy-btn"
                                    data-copy-text="{{ url('/collect/' . $share->collect_slug) }}"
                                    title="Copy upload link" aria-label="Copy upload link">
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
                            <button type="button" class="modern-btn secondary analytics-btn"
                                data-url="{{ route('account.shares.analytics', $share) }}"
                                aria-expanded="false">
                                <i class="fas fa-chart-line" aria-hidden="true"></i>
                                Analytics
                            </button>
                            <button type="button" class="modern-btn secondary limits-btn"
                                aria-expanded="false">
                                <i class="fas fa-sliders-h" aria-hidden="true"></i>
                                Limits
                            </button>
                            <button type="button"
                                class="modern-btn revoke-btn {{ $share->isRevoked() ? '' : 'danger' }}"
                                data-url="{{ route('account.shares.revoke', $share) }}"
                                data-revoked="{{ $share->isRevoked() ? '1' : '0' }}">
                                <i class="fas {{ $share->isRevoked() ? 'fa-undo' : 'fa-ban' }}" aria-hidden="true"></i>
                                <span class="revoke-btn-label">{{ $share->isRevoked() ? 'Restore link' : 'Revoke link' }}</span>
                            </button>
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

                        <div class="account-analytics-panel" hidden>
                            <div class="account-analytics-loading">
                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Loading analytics…
                            </div>
                            <div class="account-analytics-body" hidden></div>
                        </div>

                        <div class="account-limits-panel" hidden
                            data-url="{{ route('account.shares.download-limit', $share) }}">
                            <h4 class="aa-section-title"><i class="fas fa-gauge-high" aria-hidden="true"></i> Download limit</h4>
                            <p class="account-limits-hint">
                                Set the maximum number of downloads for this link. Leave empty for unlimited.
                                A limit of <strong>1</strong> makes the file self-destruct after the first download
                                (burn after reading).
                            </p>
                            <div class="account-limits-row">
                                <input type="number" min="1" max="1000000" class="account-limit-input"
                                    value="{{ $share->max_downloads }}" placeholder="∞ (unlimited)">
                                <button type="button" class="modern-btn limit-save-btn">
                                    <i class="fas fa-check" aria-hidden="true"></i> Save
                                </button>
                                <button type="button" class="modern-btn secondary limit-burn-btn">
                                    <i class="fas fa-fire" aria-hidden="true"></i> Burn after read (1)
                                </button>
                                <button type="button" class="modern-btn secondary limit-clear-btn">
                                    <i class="fas fa-infinity" aria-hidden="true"></i> Unlimited
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .account-share-stats { gap: 1rem; }
        .account-share-stats span { margin-right: .75rem; }
        .account-analytics-panel { margin-top: 1rem; border-top: 1px solid rgba(128,128,128,.25); padding-top: 1rem; }
        .account-analytics-loading { opacity: .7; font-size: .9rem; }
        .aa-metrics { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; }
        .aa-metric { flex: 1 1 110px; background: rgba(128,128,128,.08); border-radius: 10px; padding: .6rem .8rem; }
        .aa-metric b { display: block; font-size: 1.4rem; line-height: 1.2; }
        .aa-metric span { font-size: .75rem; opacity: .7; text-transform: uppercase; letter-spacing: .03em; }
        .aa-section { margin-bottom: 1rem; }
        .aa-section h4 { margin: 0 0 .5rem; font-size: .85rem; text-transform: uppercase; letter-spacing: .03em; opacity: .8; }
        .aa-bar-row { display: flex; align-items: center; gap: .5rem; margin-bottom: .3rem; font-size: .85rem; }
        .aa-bar-label { width: 110px; flex-shrink: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .aa-bar-track { flex: 1; background: rgba(128,128,128,.15); border-radius: 6px; height: 14px; overflow: hidden; }
        .aa-bar-fill { height: 100%; background: linear-gradient(90deg,#6366f1,#8b5cf6); border-radius: 6px; }
        .aa-bar-count { width: 42px; text-align: right; flex-shrink: 0; opacity: .8; }
        .aa-timeline { display: flex; align-items: flex-end; gap: 3px; height: 70px; margin-bottom: .4rem; }
        .aa-timeline-bar { flex: 1; min-width: 3px; display: flex; flex-direction: column; justify-content: flex-end; }
        .aa-timeline-dl { background: #8b5cf6; border-radius: 2px 2px 0 0; }
        .aa-timeline-vw { background: #6366f1; border-radius: 2px 2px 0 0; }
        .aa-recent { max-height: 220px; overflow-y: auto; font-size: .8rem; }
        .aa-recent-row { display: flex; gap: .5rem; padding: .3rem 0; border-bottom: 1px dashed rgba(128,128,128,.2); }
        .aa-tag { font-size: .7rem; padding: .05rem .4rem; border-radius: 999px; background: rgba(128,128,128,.18); }
        .aa-empty { opacity: .6; font-size: .85rem; }
        .account-badge-revoked { background: rgba(239,68,68,.15); color: #ef4444; }
        .account-badge-collect { background: rgba(99,102,241,.15); color: #6366f1; }
        .account-limits-panel { margin-top: 1rem; border-top: 1px solid rgba(128,128,128,.25); padding-top: 1rem; }
        .aa-section-title { margin: 0 0 .4rem; font-size: .9rem; }
        .account-limits-hint { font-size: .82rem; opacity: .75; margin: 0 0 .7rem; }
        .account-limits-row { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
        .account-limit-input { width: 160px; padding: .45rem .6rem; border-radius: 8px; border: 1px solid rgba(128,128,128,.4); background: transparent; color: inherit; }
    </style>

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

            function esc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function barRows(items) {
                if (!items || !items.length) return '<div class="aa-empty">No data yet.</div>';
                var max = items.reduce(function (m, i) { return Math.max(m, i.count); }, 0) || 1;
                return items.map(function (i) {
                    var pct = Math.round((i.count / max) * 100);
                    return '<div class="aa-bar-row">' +
                        '<span class="aa-bar-label">' + esc(i.label) + '</span>' +
                        '<span class="aa-bar-track"><span class="aa-bar-fill" style="width:' + pct + '%"></span></span>' +
                        '<span class="aa-bar-count">' + i.count + '</span></div>';
                }).join('');
            }

            function timeline(items) {
                if (!items || !items.length) return '<div class="aa-empty">No activity in the last 30 days.</div>';
                var max = items.reduce(function (m, i) { return Math.max(m, i.views + i.downloads); }, 0) || 1;
                var bars = items.map(function (i) {
                    var vh = Math.round((i.views / max) * 100);
                    var dh = Math.round((i.downloads / max) * 100);
                    return '<div class="aa-timeline-bar" title="' + esc(i.date) + ': ' + i.views + ' views, ' + i.downloads + ' downloads">' +
                        '<span class="aa-timeline-dl" style="height:' + dh + '%"></span>' +
                        '<span class="aa-timeline-vw" style="height:' + vh + '%"></span></div>';
                }).join('');
                return '<div class="aa-timeline">' + bars + '</div>';
            }

            function recent(items) {
                if (!items || !items.length) return '<div class="aa-empty">No events recorded yet.</div>';
                return items.map(function (e) {
                    var loc = [e.city, e.country].filter(Boolean).join(', ') || 'Unknown';
                    var ua = [e.browser, e.os, e.device].filter(Boolean).join(' · ');
                    return '<div class="aa-recent-row">' +
                        '<span class="aa-tag">' + esc(e.event_type) + '</span>' +
                        '<span>' + esc(loc) + '</span>' +
                        '<span style="opacity:.7">' + esc(ua) + '</span>' +
                        '<span style="margin-left:auto;opacity:.6">' + esc(e.at_human || '') + '</span></div>';
                }).join('');
            }

            function renderAnalytics(body, d) {
                var s = d.summary || {};
                body.innerHTML =
                    '<div class="aa-metrics">' +
                        '<div class="aa-metric"><b>' + (s.views || 0) + '</b><span>Views</span></div>' +
                        '<div class="aa-metric"><b>' + (s.downloads || 0) + '</b><span>Downloads</span></div>' +
                        '<div class="aa-metric"><b>' + (s.unique_visitors || 0) + '</b><span>Unique visitors</span></div>' +
                    '</div>' +
                    '<div class="aa-section"><h4>Last 30 days (views + downloads)</h4>' + timeline(d.timeline) + '</div>' +
                    '<div class="aa-section"><h4>Top countries</h4>' + barRows(d.countries) + '</div>' +
                    '<div class="aa-section"><h4>Devices</h4>' + barRows(d.devices) + '</div>' +
                    '<div class="aa-section"><h4>Browsers</h4>' + barRows(d.browsers) + '</div>' +
                    '<div class="aa-section"><h4>Recent activity (audit log)</h4><div class="aa-recent">' + recent(d.recent) + '</div></div>';
            }

            document.querySelectorAll('.analytics-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var card = btn.closest('.account-share-card');
                    var panel = card.querySelector('.account-analytics-panel');
                    var loading = panel.querySelector('.account-analytics-loading');
                    var body = panel.querySelector('.account-analytics-body');

                    if (!panel.hidden) {
                        panel.hidden = true;
                        btn.setAttribute('aria-expanded', 'false');
                        return;
                    }

                    panel.hidden = false;
                    btn.setAttribute('aria-expanded', 'true');

                    if (body.getAttribute('data-loaded') === '1') {
                        return;
                    }

                    loading.hidden = false;
                    body.hidden = true;

                    fetch(btn.dataset.url, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    }).then(function (r) { return r.json(); }).then(function (res) {
                        if (res.status === 'success') {
                            renderAnalytics(body, res.data);
                            body.setAttribute('data-loaded', '1');
                            loading.hidden = true;
                            body.hidden = false;
                        } else {
                            loading.innerHTML = 'Could not load analytics.';
                        }
                    }).catch(function () {
                        loading.innerHTML = 'Could not load analytics.';
                    });
                });
            });

            var createCollectBtn = document.getElementById('create-collect-btn');
            if (createCollectBtn) {
                createCollectBtn.addEventListener('click', function () {
                    var title = window.prompt('Title for your file request (optional):', 'Send me your files');
                    if (title === null) { return; }
                    createCollectBtn.disabled = true;
                    fetch(createCollectBtn.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ title: title })
                    }).then(function (r) { return r.json(); }).then(function (data) {
                        if (data.status === 'success') {
                            toast('success', 'File request created', 'Share the upload link with others.');
                            if (navigator.clipboard && data.url) {
                                navigator.clipboard.writeText(data.url).catch(function () {});
                            }
                            location.reload();
                        } else {
                            toast('error', 'Error', data.message || 'Could not create file request.');
                            createCollectBtn.disabled = false;
                        }
                    }).catch(function () {
                        toast('error', 'Error', 'Could not create file request.');
                        createCollectBtn.disabled = false;
                    });
                });
            }

            document.querySelectorAll('.limits-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var card = btn.closest('.account-share-card');
                    var panel = card.querySelector('.account-limits-panel');
                    panel.hidden = !panel.hidden;
                    btn.setAttribute('aria-expanded', panel.hidden ? 'false' : 'true');
                });
            });

            function postJson(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(body || {})
                }).then(function (r) { return r.json(); });
            }

            document.querySelectorAll('.revoke-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.disabled = true;
                    postJson(btn.dataset.url, {}).then(function (data) {
                        if (data.status === 'success') {
                            var card = btn.closest('.account-share-card');
                            var revoked = !!data.revoked;
                            btn.dataset.revoked = revoked ? '1' : '0';
                            btn.classList.toggle('danger', !revoked);
                            btn.querySelector('.revoke-btn-label').textContent = revoked ? 'Restore link' : 'Revoke link';
                            btn.querySelector('i').className = 'fas ' + (revoked ? 'fa-undo' : 'fa-ban');
                            var badge = card.querySelector('.account-badge-revoked');
                            if (badge) badge.hidden = !revoked;
                            toast('success', 'Link', revoked ? 'Link revoked.' : 'Link restored.');
                        } else {
                            toast('error', 'Error', data.message || 'Could not update link.');
                        }
                        btn.disabled = false;
                    }).catch(function () {
                        toast('error', 'Error', 'Could not update link.');
                        btn.disabled = false;
                    });
                });
            });

            document.querySelectorAll('.account-limits-panel').forEach(function (panel) {
                var url = panel.dataset.url;
                var input = panel.querySelector('.account-limit-input');
                var card = panel.closest('.account-share-card');

                function save(value) {
                    return postJson(url, { max_downloads: value }).then(function (data) {
                        if (data.status === 'success') {
                            var maxEl = card.querySelector('.account-dl-limit-max');
                            if (maxEl) maxEl.textContent = data.max_downloads == null ? '∞' : data.max_downloads;
                            input.value = data.max_downloads == null ? '' : data.max_downloads;
                            toast('success', 'Download limit', data.max_downloads == null
                                ? 'Set to unlimited.'
                                : 'Limit set to ' + data.max_downloads + '.');
                        } else {
                            toast('error', 'Error', (data.errors && data.errors.max_downloads)
                                ? data.errors.max_downloads[0]
                                : (data.message || 'Could not save limit.'));
                        }
                    }).catch(function () {
                        toast('error', 'Error', 'Could not save limit.');
                    });
                }

                panel.querySelector('.limit-save-btn').addEventListener('click', function () {
                    var v = input.value.trim();
                    save(v === '' ? null : parseInt(v, 10));
                });
                panel.querySelector('.limit-burn-btn').addEventListener('click', function () {
                    save(1);
                });
                panel.querySelector('.limit-clear-btn').addEventListener('click', function () {
                    save(null);
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

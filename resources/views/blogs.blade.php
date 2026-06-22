@extends('layouts.app')

@section('title', 'Blog - Latest Updates & Features | AirToShare')
@section('description',
    'Stay updated with the latest AirToShare features, tips, and announcements. Learn how to make
    the most of your file sharing experience.')
@section('keywords', 'AirToShare blog, file sharing tips, feature updates, tutorials, announcements')

@section('breadcrumb_label', 'Blog')

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "CollectionPage",
  "name": "AirToShare Blog",
  "description": "Latest updates, feature announcements, and tips to get the most out of AirToShare",
  "url": "{{ route('blog.index') }}",
  "mainEntity": {
    "@@type": "ItemList",
    "itemListElement": [
      @foreach($blogs as $index => $blog)
      {
        "@@type": "ListItem",
        "position": {{ $index + 1 }},
        "url": "{{ route('blog.show', $blog['slug']) }}",
        "name": "{{ $blog['title'] }}"
      }{{ !$loop->last ? ',' : '' }}
      @endforeach
    ]
  }
}
</script>
@endsection

@section('content')

@php
    $categoryFilters = [
        'all' => ['label' => 'All posts', 'icon' => 'fa-list'],
        'feature-update' => ['label' => 'Features', 'icon' => 'fa-star'],
        'security' => ['label' => 'Security', 'icon' => 'fa-shield-alt'],
        'ux-update' => ['label' => 'UX', 'icon' => 'fa-palette'],
        'announcement' => ['label' => 'News', 'icon' => 'fa-bullhorn'],
    ];
@endphp

<div class="blog-page">

    <header class="blog-page-hero">
        <span class="blog-page-badge"><i class="fas fa-newspaper" aria-hidden="true"></i> Blog</span>
        <h1 class="blog-page-title">Updates &amp; guides</h1>
        <p class="blog-page-lead">
            Feature announcements, security notes, and tips to get the most out of AirToShare.
        </p>
    </header>

    <div class="blog-page-toolbar">
        <label class="blog-page-search" for="blogSearchInput">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="blogSearchInput" class="blog-page-search-input"
                placeholder="Search articles…" autocomplete="off" spellcheck="false">
        </label>

        <div class="blog-page-filters" role="tablist" aria-label="Filter by category">
            @foreach ($categoryFilters as $slug => $filter)
                <button type="button"
                    class="blog-page-filter @if($slug === 'all') is-active @endif"
                    data-category="{{ $slug }}"
                    role="tab"
                    aria-selected="{{ $slug === 'all' ? 'true' : 'false' }}">
                    <i class="fas {{ $filter['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $filter['label'] }}</span>
                    <span class="blog-page-filter-count" data-count-for="{{ $slug }}">0</span>
                </button>
            @endforeach
        </div>

        <p class="blog-page-meta" id="blogResultsMeta" aria-live="polite"></p>
    </div>

    <div class="blog-page-grid" id="blogGrid">
        @foreach ($blogs as $blog)
            @php($catSlug = \Illuminate\Support\Str::slug($blog['category']))
            <article class="blog-page-card" data-category="{{ $catSlug }}"
                data-search="{{ strtolower($blog['title'] . ' ' . $blog['excerpt'] . ' ' . $blog['category']) }}">
                <a href="{{ route('blog.show', $blog['slug']) }}" class="blog-page-card-media" tabindex="-1" aria-hidden="true">
                    <img src="{{ $blog['image'] }}" alt="" loading="lazy" width="640" height="400">
                    <span class="blog-page-cat blog-page-cat--{{ $catSlug }}">{{ $blog['category'] }}</span>
                </a>
                <div class="blog-page-card-body">
                    <div class="blog-page-card-meta">
                        <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> {{ $blog['date'] }}</span>
                        <span><i class="fas fa-clock" aria-hidden="true"></i> {{ $blog['read_time'] }}</span>
                    </div>
                    <h2 class="blog-page-card-title">
                        <a href="{{ route('blog.show', $blog['slug']) }}">{{ $blog['title'] }}</a>
                    </h2>
                    <p class="blog-page-card-excerpt">{{ $blog['excerpt'] }}</p>
                    <a href="{{ route('blog.show', $blog['slug']) }}" class="blog-page-read-more">
                        Read article
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    <div class="blog-page-empty" id="blogEmpty" hidden>
        <i class="fas fa-search" aria-hidden="true"></i>
        <p><strong>No articles found</strong></p>
        <p>Try a different search term or category.</p>
    </div>

</div>

<script>
(function () {
    'use strict';

    var cards = document.querySelectorAll('.blog-page-card');
    var filters = document.querySelectorAll('.blog-page-filter');
    var searchInput = document.getElementById('blogSearchInput');
    var meta = document.getElementById('blogResultsMeta');
    var empty = document.getElementById('blogEmpty');
    var activeCategory = 'all';

    function countByCategory() {
        var counts = { all: cards.length };
        cards.forEach(function (card) {
            var cat = card.getAttribute('data-category') || '';
            counts[cat] = (counts[cat] || 0) + 1;
        });
        document.querySelectorAll('.blog-page-filter-count').forEach(function (el) {
            var key = el.getAttribute('data-count-for');
            el.textContent = counts[key] !== undefined ? counts[key] : 0;
        });
    }

    function applyFilters() {
        var query = (searchInput && searchInput.value || '').trim().toLowerCase();
        var visible = 0;

        cards.forEach(function (card) {
            var cat = card.getAttribute('data-category') || '';
            var haystack = card.getAttribute('data-search') || '';
            var matchesSearch = !query || haystack.indexOf(query) !== -1;
            var matchesCat = activeCategory === 'all' || cat === activeCategory;
            var show = matchesSearch && matchesCat;

            card.classList.toggle('is-hidden', !show);
            card.hidden = !show;
            if (show) visible++;
        });

        if (empty) empty.hidden = visible > 0;
        if (meta) {
            meta.textContent = visible === 1 ? 'Showing 1 article' : 'Showing ' + visible + ' articles';
        }
    }

    countByCategory();

    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activeCategory = btn.getAttribute('data-category') || 'all';
            filters.forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    applyFilters();
})();
</script>

@endsection

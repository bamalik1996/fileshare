@extends('layouts.app')

@section('title', $blog['title'] . ' | AirToShare Blog')
@section('description', $blog['excerpt'])
@section('keywords', 'AirToShare, ' . $blog['category'] . ', file sharing, ' . $blog['title'])

@section('og_image', url($blog['image']))
@section('twitter_image', url($blog['image']))
@section('og_type', 'article')
@section('og_published_time', date('c', strtotime($blog['date'])))
@section('breadcrumb_parent_name', 'Blog')
@section('breadcrumb_parent_url', route('blog.index'))
@section('breadcrumb_label', $blog['title'])

@section('schema')
    @php
        $blogPostingSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $blog['title'],
            'description' => $blog['excerpt'],
            'image' => url($blog['image']),
            'author' => [
                '@type' => 'Organization',
                'name' => $blog['author'],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'AirToShare',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/logo.svg'),
                ],
            ],
            'datePublished' => date('Y-m-d', strtotime($blog['date'])),
            'dateModified' => date('Y-m-d', strtotime($blog['date'])),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('blog.show', $blog['slug']),
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($blogPostingSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endsection

@section('content')

@php
    $catSlug = \Illuminate\Support\Str::slug($blog['category']);
    $articleUrl = url(route('blog.show', $blog['slug']));
@endphp

<div class="blog-article-page">

    <nav class="blog-article-nav" aria-label="Blog navigation">
        <a href="{{ route('blog.index') }}" class="blog-article-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            All articles
        </a>
    </nav>

    <article class="blog-article">
        <header class="blog-article-header">
            <span class="blog-page-cat blog-page-cat--{{ $catSlug }}">{{ $blog['category'] }}</span>
            <h1 class="blog-article-title">{{ $blog['title'] }}</h1>
            <div class="blog-article-meta">
                <span><i class="fas fa-user" aria-hidden="true"></i> {{ $blog['author'] }}</span>
                <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> {{ $blog['date'] }}</span>
                <span><i class="fas fa-clock" aria-hidden="true"></i> {{ $blog['read_time'] }}</span>
            </div>
        </header>

        <figure class="blog-article-figure">
            <img src="{{ $blog['image'] }}" alt="{{ $blog['title'] }}" width="960" height="540">
        </figure>

        <div class="blog-article-prose">
            {!! $blog['content'] !!}
        </div>

        <footer class="blog-article-share">
            <span class="blog-article-share-label">Share this article</span>
            <div class="blog-article-share-actions">
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($articleUrl) }}&text={{ urlencode($blog['title'] . ' — AirToShare') }}"
                    class="blog-article-share-btn blog-article-share-btn--x" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-x-twitter" aria-hidden="true"></i>
                    Post on X
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($articleUrl) }}"
                    class="blog-article-share-btn blog-article-share-btn--linkedin" target="_blank" rel="noopener noreferrer">
                    <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                    LinkedIn
                </a>
                <button type="button" class="blog-article-share-btn blog-article-share-btn--copy"
                    data-copy-text="{{ $articleUrl }}">
                    <i class="fas fa-link" aria-hidden="true"></i>
                    Copy link
                </button>
            </div>
        </footer>
    </article>

    @if (count($relatedBlogs) > 0)
        <section class="blog-article-related" aria-labelledby="related-posts-heading">
            <h2 class="blog-article-related-heading" id="related-posts-heading">
                <i class="fas fa-newspaper" aria-hidden="true"></i>
                More to read
            </h2>
            <div class="blog-page-grid blog-page-grid--compact">
                @foreach ($relatedBlogs as $related)
                    @php($relCat = \Illuminate\Support\Str::slug($related['category']))
                    <article class="blog-page-card">
                        <a href="{{ route('blog.show', $related['slug']) }}" class="blog-page-card-media" tabindex="-1" aria-hidden="true">
                            <img src="{{ $related['image'] }}" alt="" loading="lazy" width="640" height="400">
                            <span class="blog-page-cat blog-page-cat--{{ $relCat }}">{{ $related['category'] }}</span>
                        </a>
                        <div class="blog-page-card-body">
                            <div class="blog-page-card-meta">
                                <span><i class="fas fa-calendar-alt" aria-hidden="true"></i> {{ $related['date'] }}</span>
                                <span><i class="fas fa-clock" aria-hidden="true"></i> {{ $related['read_time'] }}</span>
                            </div>
                            <h3 class="blog-page-card-title">
                                <a href="{{ route('blog.show', $related['slug']) }}">{{ $related['title'] }}</a>
                            </h3>
                            <p class="blog-page-card-excerpt">{{ \Illuminate\Support\Str::limit($related['excerpt'], 120) }}</p>
                            <a href="{{ route('blog.show', $related['slug']) }}" class="blog-page-read-more">
                                Read article
                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="blog-article-cta">
        <a href="{{ url('/') }}" class="modern-btn blog-article-cta-btn">
            <i class="fas fa-share-alt" aria-hidden="true"></i>
            Try AirToShare
        </a>
        <a href="{{ route('blog.index') }}" class="modern-btn secondary blog-article-cta-btn">
            <i class="fas fa-newspaper" aria-hidden="true"></i>
            Back to blog
        </a>
    </div>

</div>

@endsection

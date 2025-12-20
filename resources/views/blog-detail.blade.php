@extends('layouts.app')

@section('title', $blog['title'] . ' | AirToShare Blog')
@section('description', $blog['excerpt'])
@section('keywords', 'AirToShare, ' . $blog['category'] . ', file sharing, ' . $blog['title'])

@section('schema')
    <script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "BlogPosting",
  "headline": "{{ $blog['title'] }}",
  "description": "{{ $blog['excerpt'] }}",
  "image": "{{ url($blog['image']) }}",
  "author": {
    "@@type": "Organization",
    "name": "{{ $blog['author'] }}"
  },
  "publisher": {
    "@@type": "Organization",
    "name": "AirToShare",
    "logo": {
      "@@type": "ImageObject",
      "url": "{{ url('/favicon.ico') }}"
    }
  },
  "datePublished": "{{ date('Y-m-d', strtotime($blog['date'])) }}",
  "mainEntityOfPage": {
    "@@type": "WebPage",
    "@@id": "{{ route('blog.show', $blog['slug']) }}"
  }
}
</script>
@endsection

@section('content')
    <article class="blog-detail">
        <header class="blog-detail-header">
            <a href="{{ route('blog.index') }}" class="blog-back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Blog
            </a>
            <span class="blog-category">{{ $blog['category'] }}</span>
            <h1 class="blog-detail-title">{{ $blog['title'] }}</h1>
            <div class="blog-detail-meta">
                <span class="meta-item">
                    <i class="fas fa-user"></i>
                    {{ $blog['author'] }}
                </span>
                <span class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    {{ $blog['date'] }}
                </span>
                <span class="meta-item">
                    <i class="fas fa-clock"></i>
                    {{ $blog['read_time'] }}
                </span>
            </div>
        </header>

        <div class="blog-detail-image">
            <img src="{{ $blog['image'] }}" alt="{{ $blog['title'] }}">
        </div>

        <div class="blog-detail-content">
            {!! $blog['content'] !!}
        </div>

        <div class="blog-share-section">
            <h3>Share this article</h3>
            <div class="share-buttons">
                <button class="share-btn twitter" onclick="shareOnTwitter()">
                    <i class="fab fa-x-twitter"></i>
                    X
                </button>
                <button class="share-btn linkedin" onclick="shareOnLinkedIn()">
                    <i class="fab fa-linkedin"></i>
                    LinkedIn
                </button>
                <button class="share-btn copy" onclick="copyArticleLink()">
                    <i class="fas fa-link"></i>
                    Copy Link
                </button>
            </div>
        </div>
    </article>

    @if (count($relatedBlogs) > 0)
        <section class="related-posts">
            <h2 class="related-title">
                <i class="fas fa-newspaper"></i>
                Related Articles
            </h2>
            <div class="related-grid">
                @foreach ($relatedBlogs as $related)
                    <article class="blog-card">
                        <a href="{{ route('blog.show', $related['slug']) }}" class="blog-card-image">
                            <img src="{{ $related['image'] }}" alt="{{ $related['title'] }}" loading="lazy">
                            <span class="blog-category">{{ $related['category'] }}</span>
                        </a>
                        <div class="blog-card-content">
                            <h3 class="blog-card-title">
                                <a href="{{ route('blog.show', $related['slug']) }}">
                                    {{ $related['title'] }}
                                </a>
                            </h3>
                            <p class="blog-card-excerpt">
                                {{ Str::limit($related['excerpt'], 100) }}
                            </p>
                            <a href="{{ route('blog.show', $related['slug']) }}" class="blog-read-more">
                                Read More
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <script>
        function shareOnTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent('{{ $blog['title'] }} - AirToShare');
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
        }

        function shareOnLinkedIn() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank');
        }

        function copyArticleLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showToast('success', 'Link Copied!', 'Article link copied to clipboard');
            });
        }
    </script>
@endsection

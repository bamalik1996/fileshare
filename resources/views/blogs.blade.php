@extends('layouts.app')

@section('title', 'Blog - Latest Updates & Features | AirToShare')
@section('description',
    'Stay updated with the latest AirToShare features, tips, and announcements. Learn how to make
    the most of your file sharing experience.')
@section('keywords', 'AirToShare blog, file sharing tips, feature updates, tutorials, announcements')

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
    <div class="blog-hero">
        <h1 class="blog-hero-title">
            <i class="fas fa-newspaper"></i>
            AirToShare Blog
        </h1>
        <p class="blog-hero-subtitle">
            Latest updates, feature announcements, and tips to get the most out of AirToShare
        </p>
    </div>

    <div class="blog-container">
        <div class="blog-grid">
            @foreach ($blogs as $blog)
                <article class="blog-card">
                    <a href="{{ route('blog.show', $blog['slug']) }}" class="blog-card-image">
                        <img src="{{ $blog['image'] }}" alt="{{ $blog['title'] }}" loading="lazy">
                        <span class="blog-category">{{ $blog['category'] }}</span>
                    </a>
                    <div class="blog-card-content">
                        <div class="blog-meta">
                            <span class="blog-date">
                                <i class="fas fa-calendar-alt"></i>
                                {{ $blog['date'] }}
                            </span>
                            <span class="blog-read-time">
                                <i class="fas fa-clock"></i>
                                {{ $blog['read_time'] }}
                            </span>
                        </div>
                        <h2 class="blog-card-title">
                            <a href="{{ route('blog.show', $blog['slug']) }}">
                                {{ $blog['title'] }}
                            </a>
                        </h2>
                        <p class="blog-card-excerpt">
                            {{ $blog['excerpt'] }}
                        </p>
                        <a href="{{ route('blog.show', $blog['slug']) }}" class="blog-read-more">
                            Read More
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
@endsection

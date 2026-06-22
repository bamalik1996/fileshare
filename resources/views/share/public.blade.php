@extends('layouts.app')

@section('title', 'Shared files – AirToShare')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="modern-card" id="airtoshare-public-view"
         @if($share) data-airtoshare-share-id="{{ $share->id }}" @endif>
        <h1 class="title is-4">Shared content</h1>
        <p class="subtitle is-6">This is a private public link. It is not indexed by search engines.</p>

        @if ($share->text_content)
            <div class="text-container" style="margin-bottom: 2rem;">
                <div class="rich-preview-panel">{!! $share->text_content !!}</div>
            </div>
        @endif

        @if ($media->isNotEmpty())
            <h2 class="title is-5">Files</h2>
            <div class="file-grid">
                @foreach ($media as $file)
                    <div class="column is-12 preview-row file-item"
                         data-preview-uuid="{{ $file['uuid'] }}"
                         data-preview-mime="{{ $file['mime_type'] }}"
                         data-preview-size="{{ $file['size'] }}"
                         data-preview-url="{{ $file['original_url'] }}"
                         data-preview-name="{{ $file['name'] }}">
                        <div class="file-info">
                            <div class="file-name">{{ $file['name'] }}</div>
                            <div class="file-size">{{ number_format($file['size'] / 1024, 1) }} KB</div>
                        </div>
                        <a class="modern-btn is-small preview-download" href="{{ $file['original_url'] }}" download>Download</a>
                    </div>
                @endforeach
            </div>
        @else
            <p>No files in this share.</p>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('title', 'API v2 Reference')

@section('content')
<div class="modern-card">
    <h1 class="title is-4">API v2 Reference</h1>
    <pre style="white-space: pre-wrap; font-family: ui-monospace, monospace; font-size: 0.9rem;">{{ $markdown }}</pre>
</div>
@endsection

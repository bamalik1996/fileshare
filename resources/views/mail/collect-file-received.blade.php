Someone uploaded a file to your AirToShare file request{{ $share->collect_title ? ' ("' . $share->collect_title . '")' : '' }}.

File: {{ $fileName }}
Size: {{ number_format($fileSize / 1024, 1) }} KB

Open your share to view or download it:
{{ url('/s/' . $share->uuid) }}

Manage all shares:
{{ url('/account/shares') }}

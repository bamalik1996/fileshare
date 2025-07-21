<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shared Files from AirToShare</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 30px;
        }
        .message-box {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
        }
        .file-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌐 AirToShare</div>
            <p>Files shared with you</p>
        </div>

        <div class="content">
            <h2>Hello!</h2>
            <p>Someone has shared {{ $file_count }} file(s) with you using AirToShare.</p>

            @if($user_message)
            <div class="message-box">
                <strong>Message from sender:</strong>
                <p>{{ $user_message }}</p>
            </div>
            @endif

            <div class="file-info">
                <strong>📁 Files attached:</strong> {{ $file_count }} file(s)<br>
                <strong>📡 Sent from IP:</strong> {{ $sender_ip }}<br>
                <strong>📅 Sent on:</strong> {{ date('F j, Y \a\t g:i A') }}
            </div>

            <p>The files are attached to this email. You can download them directly from your email client.</p>

            <p><strong>About AirToShare:</strong><br>
            AirToShare is a simple, secure file sharing service that allows instant sharing across devices on the same network. No accounts required, no external servers - just pure peer-to-peer sharing.</p>
        </div>

        <div class="footer">
            <p>This email was sent via AirToShare - Instant File Sharing<br>
            <small>Files are automatically deleted after 6 hours for security</small></p>
        </div>
    </div>
</body>
</html>

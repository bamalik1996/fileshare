<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SharedText;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ShareController extends Controller
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_FILES_PER_IP = 20;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'text/plain', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip', 'application/x-rar-compressed'
    ];

    public function saveText(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid text data.',
                'errors' => $validator->errors()
            ], 422);
        }

        $ip = $request->ip();
        $text = $request->input('text');
        $expiry = Carbon::now()->addHours(6);  // Text expires after 6 hours

        // Sanitize text input
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Update or create text for the IP
        $sharedText = SharedText::updateOrCreate(
            ['ip_address' => $ip],
            ['content' => $text, 'expires_at' => $expiry, 'last_accessed' => Carbon::now()]
        );

        // Cache the result for faster access
        Cache::put("shared_text_{$ip}", $sharedText, 3600);

        Log::info("Text saved for IP: {$ip}");

        return response()->json([
            'status' => 'success',
            'message' => 'Text saved successfully.',
            'expires_at' => $expiry->toDateTimeString(),
            'character_count' => strlen($text)
        ]);
    }


    public function getText(Request $request)
    {
        $ip = $request->ip();

        // Try to get from cache first
        $cachedText = Cache::get("shared_text_{$ip}");
        if ($cachedText && Carbon::parse($cachedText->expires_at)->isFuture()) {
            return response()->json([
                'status' => 'success',
                'text' => $cachedText->content,
                'expires_at' => $cachedText->expires_at,
                'last_accessed' => $cachedText->last_accessed
            ]);
        }

        // Find the shared text based on the IP and check if it's still valid
        $sharedText = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())  // Ensure the text hasn't expired
            ->first();

        // If shared text is found
        if ($sharedText) {
            // Check if it's been more than 1 hour since last access
            if ($sharedText->last_accessed && Carbon::parse($sharedText->last_accessed)->addHour()->isPast()) {
                // If it's past 1 hour since the last access, delete the text
                $sharedText->delete();
                Cache::forget("shared_text_{$ip}");
                return response()->json(['status' => 'error', 'message' => 'Content expired and deleted.']);
            }

            // If the content is still valid, update the last accessed time
            $sharedText->last_accessed = Carbon::now();
            $sharedText->save();

            // Update cache
            Cache::put("shared_text_{$ip}", $sharedText, 3600);

            return response()->json([
                'status' => 'success',
                'text' => $sharedText->content,
                'expires_at' => $sharedText->expires_at,
                'last_accessed' => $sharedText->last_accessed,
                'media' => $sharedText->getMedia()->map(function ($item) {
                    return [
                        'uuid' => $item->uuid,
                        'url' => $item->getUrl(),
                        'name' => $item->name,
                        'size' => $item->size,
                        'mime_type' => $item->mime_type
                    ];
                })
            ]);
        }

        // If no valid content found for the IP
        return response()->json(['status' => 'error', 'message' => 'No active text found for this IP.']);
    }


    public function getMedia(Request $request)
    {
        $ip = $request->ip();
        
        $sharedText = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())  // Ensure the text hasn't expired
            ->first();
            
        if (!$sharedText) {
            return response()->json(['files' => []]);
        }
        
        $files = $sharedText->getMedia()->map(function ($item) {
            return [
                'mime_type' => $item->mime_type,
                'name' => $item->name,
                'file_name' => $item->file_name,
                'uuid' => $item->uuid,
                'preview_url' => $item->getUrl(),
                'original_url' => $item->getUrl(),
                'order' => $item->order_column,
                'custom_properties' => $item->custom_properties,
                'extension' => $item->extension,
                'size' => $this->formatFileSize($item->size),
                'size_bytes' => $item->size,
                'created_at' => $item->created_at->diffForHumans()
            ];
        });

        return response()->json([
            'files' => $files,
            'total_files' => $files->count(),
            'total_size' => $this->formatFileSize($files->sum('size_bytes'))
        ]);
    }


    public function saveMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:' . (self::MAX_FILE_SIZE / 1024)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid file.',
                'errors' => $validator->errors()
            ], 422);
        }

        $ip = $request->ip();
        $file = $request->file('file');
        
        // Check file type
        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File type not allowed.'
            ], 422);
        }
        
        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'status' => 'error',
                'message' => 'File size exceeds limit of ' . $this->formatFileSize(self::MAX_FILE_SIZE)
            ], 422);
        }
        
        $expiry = Carbon::now()->addHours(6);

        $sharedText = SharedText::firstOrCreate(
            ['ip_address' => $ip],
            ['expires_at' => $expiry]
        );
        
        // Check file count limit
        $currentFileCount = $sharedText->getMedia()->count();
        if ($currentFileCount >= self::MAX_FILES_PER_IP) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maximum file limit reached (' . self::MAX_FILES_PER_IP . ' files per IP)'
            ], 422);
        }

        // Generate unique filename
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
        
        $media = $sharedText->addMedia($file)
            ->usingName($originalName)
            ->usingFileName($safeName)
            ->toMediaCollection();
            
        Log::info("File uploaded for IP: {$ip}, File: {$originalName}");

        return response()->json([
            'status' => 'success',
            'message' => 'Media uploaded successfully.',
            'uuid' => $media->uuid,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'size' => $this->formatFileSize($media->size),
            'expires_at' => $expiry->toDateTimeString(),
        ]);
    }

    public function deleteMedia(Request $request)
    {
        $uuid = $request->route('uuid') ?? $request->input('uuid');
        
        if (!$uuid) {
            return response()->json(['success' => false, 'message' => 'UUID required'], 400);
        }
        
        $media = Media::where('uuid', $uuid)->first();

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }
        
        // Security check: ensure the media belongs to the current IP
        $ip = $request->ip();
        $sharedText = $media->model;
        
        if ($sharedText->ip_address !== $ip) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        Log::info("File deleted for IP: {$ip}, UUID: {$uuid}");
        $media->delete(); // This will remove the file from storage as well

        return response()->json(['success' => true]);
    }
    
    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    public function getIpInfo(Request $request)
    {
        $ip = $request->ip();
        $sharedText = SharedText::where('ip_address', $ip)->first();
        
        $info = [
            'ip' => $ip,
            'has_content' => $sharedText ? true : false,
            'files_count' => $sharedText ? $sharedText->getMedia()->count() : 0,
            'max_files' => self::MAX_FILES_PER_IP,
            'max_file_size' => $this->formatFileSize(self::MAX_FILE_SIZE)
        ];
        
        if ($sharedText) {
            $info['expires_at'] = $sharedText->expires_at;
            $info['last_accessed'] = $sharedText->last_accessed;
        }
        
        return response()->json($info);
    }
}

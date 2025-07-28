<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const MAX_FILES_PER_IP = 20;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'text/plain', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip', 'application/x-rar-compressed'
    ];

    /**
     * Store a media file with current user's IP and expiry of 6 hours
     */
    public function store(Request $request)
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
        
        // Check file count limit for this IP
        $currentFileCount = MediaFile::active($ip)->count();
        if ($currentFileCount >= self::MAX_FILES_PER_IP) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maximum file limit reached (' . self::MAX_FILES_PER_IP . ' files per IP)'
            ], 422);
        }

        try {
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;
            
            // Store file in public disk under media directory
            $storagePath = 'media/' . $fileName;
            $file->storeAs('media', $fileName, 'public');
            
            // Verify file was actually saved
            if (!Storage::disk('public')->exists($storagePath)) {
                throw new \Exception('File was not properly saved to storage');
            }
            
            // Create database record
            $mediaFile = MediaFile::create([
                'file_name' => $fileName,
                'original_name' => $originalName,
                'mime_type' => $file->getMimeType(),
                'storage_path' => $storagePath,
                'ip_address' => $ip,
                'file_size' => $file->getSize(),
                'expires_at' => Carbon::now()->addHours(6) // 6 hours expiry
            ]);
            
            Log::info("File uploaded for IP: {$ip}, File: {$originalName}");

            return response()->json([
                'status' => 'success',
                'message' => 'Media uploaded successfully.',
                'data' => [
                    'uuid' => $mediaFile->uuid,
                    'url' => $mediaFile->url,
                    'name' => $mediaFile->original_name,
                    'size' => $mediaFile->formatted_size,
                    'expires_at' => $mediaFile->expires_at->toDateTimeString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error("File upload failed for IP: {$ip}, Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save file. Please try again.'
            ], 500);
        }
    }

    /**
     * Fetch all non-expired media files uploaded from current IP
     */
    public function index(Request $request)
    {
        $ip = $request->ip();
        
        // Get all active (non-expired) files for this IP
        $mediaFiles = MediaFile::active($ip)
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Filter out files that don't exist on disk and log warnings
        $validFiles = $mediaFiles->filter(function ($file) {
            if (!$file->existsOnDisk()) {
                Log::warning("File missing from disk: " . $file->storage_path);
                // Optionally delete the database record
                // $file->delete();
                return false;
            }
            return true;
        });
        
        $files = $validFiles->map(function ($file) {
            return [
                'uuid' => $file->uuid,
                'name' => $file->original_name,
                'file_name' => $file->file_name,
                'mime_type' => $file->mime_type,
                'size' => $file->formatted_size,
                'size_bytes' => $file->file_size,
                'url' => $file->url,
                'is_image' => $file->is_image,
                'extension' => $file->extension,
                'created_at' => $file->created_at->diffForHumans(),
                'expires_at' => $file->expires_at->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'files' => $files,
            'total_files' => $files->count(),
            'total_size' => $this->formatFileSize($files->sum('size_bytes'))
        ]);
    }

    /**
     * Delete a specific media file
     */
    public function destroy(Request $request, $uuid = null)
    {
        $uuid = $uuid ?? $request->input('uuid');
        
        if (!$uuid) {
            return response()->json(['success' => false, 'message' => 'UUID required'], 400);
        }
        
        $ip = $request->ip();
        
        // Find file by UUID and IP (security check)
        $mediaFile = MediaFile::where('uuid', $uuid)
            ->where('ip_address', $ip)
            ->first();

        if (!$mediaFile) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        Log::info("File deleted for IP: {$ip}, UUID: {$uuid}");
        $mediaFile->delete(); // This will also remove the physical file

        return response()->json(['success' => true, 'message' => 'File deleted successfully']);
    }

    /**
     * Delete all media files for current IP
     */
    public function destroyAll(Request $request)
    {
        $ip = $request->ip();
        
        $mediaFiles = MediaFile::active($ip)->get();
        $deletedCount = $mediaFiles->count();
        
        if ($deletedCount === 0) {
            return response()->json(['success' => false, 'message' => 'No files found'], 404);
        }
        
        // Delete all files
        foreach ($mediaFiles as $file) {
            $file->delete();
        }
        
        Log::info("All files deleted for IP: {$ip}, Count: {$deletedCount}");
        
        return response()->json([
            'success' => true, 
            'message' => "Successfully deleted {$deletedCount} files",
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Get IP info including file count and limits
     */
    public function getIpInfo(Request $request)
    {
        $ip = $request->ip();
        $fileCount = MediaFile::active($ip)->count();
        
        return response()->json([
            'ip' => $ip,
            'files_count' => $fileCount,
            'max_files' => self::MAX_FILES_PER_IP,
            'max_file_size' => $this->formatFileSize(self::MAX_FILE_SIZE),
            'has_files' => $fileCount > 0
        ]);
    }

    /**
     * Format file size for display
     */
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
}
<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class MediaController extends Controller
{
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 10MB
    private const MAX_FILES_PER_IP = 20;
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'application/pdf',
        'text/plain',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/x-rar-compressed'
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

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File type not allowed.'
            ], 422);
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return response()->json([
                'status' => 'error',
                'message' => 'File size exceeds limit of ' . $this->formatFileSize(self::MAX_FILE_SIZE)
            ], 422);
        }

        $expiry = now()->addHours(24);

        // ✅ Get existing or new MediaFile and update expiry
        $sharedMedia = MediaFile::firstOrNew(['ip_address' => $ip]);
        $sharedMedia->expires_at = $expiry;
        $sharedMedia->save();

        // Check file count limit
        if ($sharedMedia->getMedia('shared_files')->count() >= self::MAX_FILES_PER_IP) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maximum file limit reached (' . self::MAX_FILES_PER_IP . ' files per IP)'
            ], 422);
        }

        // Generate safe file name
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '_' . time() . '.' . $extension;

        try {
            $media = $sharedMedia->addMedia($file)
                ->usingName($originalName)
                ->usingFileName($safeName)
                ->toMediaCollection('shared_files', 'public');

            if (!$media || !file_exists($media->getPath())) {
                throw new \Exception('File was not properly saved to storage');
            }

            chmod($media->getPath(), 0644);
        } catch (\Exception $e) {
            Log::error("File upload failed for IP: {$ip}, Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save file. Please try again.'
            ], 500);
        }

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



    /**
     * Fetch all non-expired media files uploaded from current IP
     */
    public function index(Request $request)
    {
        $ip = $request->ip();

        $mediaFiles = MediaFile::active($ip)
            ->latest('created_at')
            ->first();

        if (!$mediaFiles) {
            return response()->json([
                'files' => [],
                'total_files' => 0,
                'total_size' => $this->formatFileSize(0),
            ]);
        }

        $files = $mediaFiles->getMedia('shared_files')
            ->map(function ($item) {
                $path = $item->getPath();

                // Check if file exists
                if (!file_exists($path)) {
                    Log::warning("Missing file on disk: {$path}");
                    return null; // Optionally: $item->delete();
                }

                return [
                    'uuid' => $item->uuid,
                    'name' => $item->name,
                    'file_name' => $item->file_name,
                    'mime_type' => $item->mime_type,
                    'extension' => $item->extension,
                    'preview_url' => $item->getFullUrl(),
                    'original_url' => $item->getUrl(),
                    'order' => $item->order_column,
                    'custom_properties' => $item->custom_properties,
                    'size' => $this->formatFileSize($item->size),
                    'size_bytes' => $item->size,
                    'created_at' => $item->created_at->diffForHumans(),
                ];
            })
            ->filter()  // Remove nulls (missing files)
            ->values(); // Reset array indexes

        return response()->json([
            'files' => $files,
            'total_files' => $files->count(),
            'total_size' => $this->formatFileSize($files->sum('size_bytes')),
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

        // Find parent model by IP
        $mediaFile = MediaFile::where('ip_address', $ip)->first();

        if (!$mediaFile) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        // Find media item by UUID
        $mediaItem = $mediaFile->media()->where('uuid', $uuid)->first();

        if (!$mediaItem) {
            return response()->json(['success' => false, 'message' => 'Media not found'], 404);
        }

        $mediaItem->delete();

        Log::info("File deleted for IP: {$ip}, UUID: {$uuid}");

        // If no media left, delete parent
        if ($mediaFile->getMedia('shared_files')->count() === 0) {
            $mediaFile->delete();
        }

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

    public function getIpInfo(Request $request)
    {
        $ip = $request->ip();
        $sharedText = MediaFile::where('ip_address', $ip)->first();

        $info = [
            'ip' => $ip,
            'has_content' => $sharedText ? true : false,
            'files_count' => $sharedText ? $sharedText->getMedia('shared_files')->count() : 0,
            'max_files' => self::MAX_FILES_PER_IP,
            'max_file_size' => $this->formatFileSize(self::MAX_FILE_SIZE)
        ];

        if ($sharedText) {
            $info['expires_at'] = $sharedText->expires_at;
            $info['last_accessed'] = $sharedText->last_accessed;
        }

        return response()->json($info);
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

    public function downloadZip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuids' => 'required|array',
            'uuids.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid request data.'
            ], 422);
        }

        $ip = $request->ip();
        $uuids = $request->input('uuids');

        // Get media files that belong to this IP
        $sharedText = MediaFile::active($ip)
            ->first();

        if (!$sharedText) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files found.'
            ], 404);
        }

        $mediaFiles = $sharedText->getMedia('shared_files')->whereIn('uuid', $uuids);

        if ($mediaFiles->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files found for download.'
            ], 404);
        }

        // Create temporary zip file
        $zipFileName = 'shared-files-' . time() . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create zip file.'
            ], 500);
        }

        foreach ($mediaFiles as $media) {
            $filePath = $media->getPath();
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $media->name);
            }
        }

        $zip->close();

        Log::info("Zip file created for IP: {$ip}, Files: " . count($mediaFiles));

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}

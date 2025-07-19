<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SharedText;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ShareController extends Controller
{
    public function saveText(Request $request)
    {

        $ip = $request->ip();
        $text = $request->input('text');
        $expiry = Carbon::now()->addHours(3);  // Text expires after 3 hours

        // Update or create text for the IP
        $sharedText = SharedText::updateOrCreate(
            ['ip_address' => $ip],
            ['content' => $text, 'expires_at' => $expiry]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Text saved successfully.',
            'expires_at' => $expiry->toDateTimeString(),
        ]);
    }


    public function getText(Request $request)
    {
        $ip = $request->ip();

        // Find the shared text based on the IP and check if it's still valid
        $sharedText = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())  // Ensure the text hasn't expired
            ->first();

        // If shared text is found
        if ($sharedText) {
            // Check if it's been more than 30 minutes since last access
            if (Carbon::parse($sharedText->last_accessed)->addMinutes(30)->isPast()) {
                // If it's past 30 minutes since the last access, delete the text
                $sharedText->delete();
                return response()->json(['status' => 'error', 'message' => 'Content expired and deleted.']);
            }

            // If the content is still valid, update the last accessed time
            $sharedText->last_accessed = Carbon::now();
            $sharedText->save();

            return response()->json([
                'status' => 'success',
                'text' => $sharedText->content,
                'media' => $sharedText->getMedia()->map(function ($item) {
                    return [
                        'uuid' => $item->uuid,
                        'url' => $item->getUrl(),
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
        $files = SharedText::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())  // Ensure the text hasn't expired
            ->first()->getMedia()->map(function ($item) {

                return   [
                    'mime_type' => $item->getTypeFromExtension(),

                    'name' => $item->name,
                    'file_name' => $item->file_name,
                    'uuid' => $item->uuid,
                    'preview_url' => $item->preview_url,
                    'original_url' => $item->original_url,
                    'order' => $item->order_column,
                    'custom_properties' => $item->custom_properties,
                    'extension' => $item->extension,
                    'size' => $item->size,

                ];
            });


        return response()->json([
            'files' => $files
        ]);
    }


    public function saveMedia(Request $request)
    {
        $ip = $request->ip();
        $file = $request->file('file');
        $expiry = Carbon::now()->addHours(3);

        $sharedText = SharedText::firstOrCreate(
            ['ip_address' => $ip],
            ['expires_at' => $expiry]
        );

        $media = $sharedText->addMedia($file)->toMediaCollection();

        return response()->json([
            'status' => 'success',
            'message' => 'Media uploaded successfully.',
            'uuid' => $media->uuid,
            'url' => $media->getUrl(),
            'expires_at' => $expiry->toDateTimeString(),
        ]);
    }

    public function deleteMedia(Request $request)
    {
        $uuid = $request->uuid;
        $media = Media::where('uuid', $uuid)->first();

        if (!$media) {
            return response()->json(['success' => false, 'message' => 'File not found'], 404);
        }

        $media->delete(); // This will remove the file from storage as well

        return response()->json(['success' => true]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaController extends Controller
{
    /**
     * Handle the incoming file upload and attach it to the related model.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'], // Max 50MB
            'related_model' => ['required', 'string'],
            'related_id' => ['required', 'string'],
        ]);

        // Map frontend "related_model" strings to actual Classes
        // Security: Whitelist allowed models!
        $modelMap = [
            'User' => \App\Models\User::class,
            // 'Audition' => \App\Models\Audition::class, // Future models
        ];

        $modelName = $request->input('related_model');

        if (! array_key_exists($modelName, $modelMap)) {
            return response()->json(['message' => 'Invalid model type.'], 422);
        }

        $modelClass = $modelMap[$modelName];
        $id = $request->input('related_id');

        // Find the record
        $record = $modelClass::find($id);

        if (! $record) {
            // OPTIONAL: If using Offline-First, the record might not exist on the server yet if Sync hasn't run.
            // In a strict systems, we might fail. In loose systems, we might create a placeholder or queue.
            // For now, fail if parent not found.
            return response()->json(['message' => 'Parent record not found. Sync parent first.'], 404);
        }

        try {
            // Attach media
            $media = $record->addMediaFromRequest('file')
                ->toMediaCollection('default');

            return response()->json([
                'message' => 'File uploaded successfully',
                'media_id' => $media->id,
                'url' => $media->getUrl(),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Upload failed: '.$e->getMessage());

            return response()->json(['message' => 'Upload failed'], 500);
        }
    }
}

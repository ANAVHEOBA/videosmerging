<?php

namespace App\Http\Controllers;

use App\Services\PythonVideoMerger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;
use App\Http\Requests\VideoMergeRequest;

class VideoController extends Controller
{
    protected $videoMerger;

    public function __construct(PythonVideoMerger $videoMerger)
    {
        $this->videoMerger = $videoMerger;
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/*|max:102400' // 100MB max
        ]);

        try {
            if (!$request->hasFile('video')) {
                throw new Exception('No video file provided');
            }

            $video = $request->file('video');
            $path = $video->store('uploads', 'public');

            if (!$path) {
                throw new Exception('Failed to store video file');
            }

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'path' => $path,
                'url' => Storage::disk('public')->url($path)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Video upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function merge(VideoMergeRequest $request): JsonResponse
    {
        $videoPaths = $request->input('video_paths');

        // Validate that all videos exist
        foreach ($videoPaths as $path) {
            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => "Video not found: {$path}"
                ], 404);
            }
        }

        try {
            // Merge the videos
            $outputPath = $this->videoMerger->merge($videoPaths);

            // Verify the merged file exists
            if (!Storage::disk('public')->exists($outputPath)) {
                throw new Exception('Merged video file was not created');
            }

            // Get the file size to verify it's not empty
            $fileSize = Storage::disk('public')->size($outputPath);
            if ($fileSize === 0) {
                throw new Exception('Merged video file is empty');
            }

            // Return success response with the path to the merged video
            return response()->json([
                'success' => true,
                'message' => 'Videos merged successfully',
                'output_url' => Storage::disk('public')->url($outputPath),
                'file_size' => $fileSize
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Video merging failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function status($jobId): JsonResponse
    {
        try {
            
            return response()->json([
                'success' => true,
                'status' => 'completed', // or 'processing', 'failed', etc.
                'message' => 'Job status retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get job status: ' . $e->getMessage()
            ], 500);
        }
    }
}
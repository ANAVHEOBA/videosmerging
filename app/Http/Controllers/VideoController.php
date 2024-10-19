<?php

namespace App\Http\Controllers;

use App\Services\PythonVideoMerger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http; // Add this import
use App\Jobs\MergeVideosJob;
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
        $outputFilename = 'merged_' . Str::random(10) . '.mp4';

        // Dispatch the job for background processing
        MergeVideosJob::dispatch($videoPaths, $outputFilename);

        return response()->json([
            'success' => true,
            'message' => 'Video merge request is being processed',
            'output_filename' => $outputFilename
        ]);
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

    public function uploadFromLink(Request $request): JsonResponse
    {
        $request->validate([
            'video_link' => 'required|url'
        ]);

        try {
            $videoUrl = $request->input('video_link');

            // Check if URL points to a valid video
            $headers = get_headers($videoUrl, 1);
            if (strpos($headers['Content-Type'], 'video') === false) {
                throw new Exception('Provided URL is not a valid video');
            }

            // Download the video file
            $response = Http::get($videoUrl);
            if ($response->failed()) {
                throw new Exception('Failed to download video from link');
            }

            // Generate a random file name to avoid conflicts
            $fileName = Str::random(10) . '_' . basename(parse_url($videoUrl, PHP_URL_PATH));
            $path = 'uploads/' . $fileName;

            // Store the video
            Storage::disk('public')->put($path, $response->body());

            // Check if video was stored successfully
            if (!Storage::disk('public')->exists($path)) {
                throw new Exception('Failed to store video file');
            }

            return response()->json([
                'success' => true,
                'message' => 'Video downloaded successfully',
                'path' => $path,
                'url' => Storage::disk('public')->url($path)
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Video download failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

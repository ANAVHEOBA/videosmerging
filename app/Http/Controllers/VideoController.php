<?php

namespace App\Http\Controllers;

use App\Http\Requests\VideoUploadRequest;
use App\Http\Requests\VideoMergeRequest;
use App\Services\PythonVideoMerger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Jobs\MergeVideosJob;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    private $videoMerger;

    public function __construct(PythonVideoMerger $videoMerger)
    {
        $this->videoMerger = $videoMerger;
    }

    public function upload(VideoUploadRequest $request): JsonResponse
    {
        $file = $request->file('video');
        $path = $file->store('videos', 'public');

        return response()->json([
            'success' => true,
            'message' => 'Video uploaded successfully',
            'path' => $path
        ]);
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

        // Dispatch merge job
        $jobId = Str::random(10);
        MergeVideosJob::dispatch($videoPaths, $jobId);

        return response()->json([
            'success' => true,
            'message' => 'Video merge job queued',
            'job_id' => $jobId
        ]);
    }

    public function status(string $jobId): JsonResponse
    {
        $status = cache()->get("video_merge_{$jobId}");

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}
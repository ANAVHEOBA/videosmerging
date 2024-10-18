<?php
namespace App\Http\Controllers;

use App\Http\Requests\VideoUploadRequest;
use App\Http\Requests\VideoMergeRequest;
use App\Services\PythonVideoMerger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Exception;

class VideoController extends Controller
{
    private $videoMerger;

    public function __construct(PythonVideoMerger $videoMerger)
    {
        $this->videoMerger = $videoMerger;
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
}
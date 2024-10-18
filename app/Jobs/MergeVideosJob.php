<?php

namespace App\Jobs;

use App\Services\PythonVideoMerger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class MergeVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $videoPaths;
    private $jobId;

    public function __construct(array $videoPaths, string $jobId)
    {
        $this->videoPaths = $videoPaths;
        $this->jobId = $jobId;
    }

    public function handle(PythonVideoMerger $merger): void
    {
        try {
            Cache::put("video_merge_{$this->jobId}", 'processing', 3600);
            
            $outputPath = $merger->merge($this->videoPaths);
            
            Cache::put("video_merge_{$this->jobId}", [
                'status' => 'completed',
                'output_path' => $outputPath
            ], 3600);
        } catch (\Exception $e) {
            Cache::put("video_merge_{$this->jobId}", [
                'status' => 'failed',
                'error' => $e->getMessage()
            ], 3600);
        }
    }
}
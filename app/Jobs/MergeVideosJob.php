<?php

namespace App\Jobs;

use App\Services\PythonVideoMerger;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MergeVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoPaths;
    protected $outputFilename;

    public function __construct(array $videoPaths, string $outputFilename = null)
    {
        $this->videoPaths = $videoPaths;
        $this->outputFilename = $outputFilename;
    }

    public function handle(PythonVideoMerger $videoMerger)
    {
        $videoMerger->merge($this->videoPaths, $this->outputFilename);
    }
}

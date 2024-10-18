<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class PythonVideoMerger
{
    private $pythonScript;
    private $outputDir;

    public function __construct()
    {
        $this->pythonScript = storage_path('app/scripts/video_merger.py');
        $this->outputDir = storage_path('app/public/merged');
        
        // Ensure the output directory exists
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function merge(array $videoPaths, string $outputFilename = null): string
    {
        // Generate output filename if not provided
        $outputFilename = $outputFilename ?? 'merged_' . Str::random(10) . '.mp4';
        $outputPath = $this->outputDir . '/' . $outputFilename;

        // Convert storage paths to absolute paths
        $absolutePaths = array_map(function ($path) {
            return storage_path('app/public/' . $path);
        }, $videoPaths);

        // Prepare Python command
        $process = new Process([
            'python3',
            $this->pythonScript,
            '--input',
            implode(',', $absolutePaths),
            '--output',
            $outputPath
        ]);

        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception('Video merging failed: ' . $process->getErrorOutput());
        }

        return 'merged/' . $outputFilename;
    }
}
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
        $this->outputDir = 'merged'; 
    }

    public function merge(array $videoPaths, string $outputFilename = null): string
    {
        
        $outputFilename = $outputFilename ?? 'merged_' . Str::random(10) . '.mp4';
        
        
        Storage::disk('public')->makeDirectory($this->outputDir);
        
        
        $outputPath = $this->outputDir . '/' . $outputFilename;
        $absoluteOutputPath = storage_path('app/public/' . $outputPath);

       
        $absolutePaths = array_map(function ($path) {
            return storage_path('app/public/' . $path);
        }, $videoPaths);

        
        $process = new Process([
            'python3',
            $this->pythonScript,
            '--input',
            implode(',', $absolutePaths),
            '--output',
            $absoluteOutputPath
        ]);

        $process->setTimeout(3600); 
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception('Video merging failed: ' . $process->getErrorOutput());
        }

        
        return $outputPath;
    }
}
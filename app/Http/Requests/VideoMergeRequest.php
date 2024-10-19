<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoMergeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'video_paths' => 'required|array|min:2',
            'video_paths.*' => 'required|string'
        ];
    }

    public function messages()
    {
        return [
            'video_paths.required' => 'At least two video paths are required',
            'video_paths.array' => 'Video paths must be provided as an array',
            'video_paths.min' => 'At least two videos are required for merging',
            'video_paths.*.required' => 'All video paths must be valid strings',
            'video_paths.*.string' => 'All video paths must be valid strings'
        ];
    }
}
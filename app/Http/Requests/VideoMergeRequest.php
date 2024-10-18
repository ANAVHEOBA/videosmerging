<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoMergeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'video_paths' => 'required|array|min:2',
            'video_paths.*' => 'required|string'
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VideoUploadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'video' => 'required|file|mimes:mp4,avi,mov|max:102400' // 100MB max
        ];
    }
}
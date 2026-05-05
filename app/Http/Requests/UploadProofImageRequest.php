<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProofImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'proof_image' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,gif,bmp,webp,pdf',
                'max:10240', // 10 MB
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'proof_image.required' => 'A proof image is required.',
            'proof_image.file' => 'The proof image must be a valid file.',
            'proof_image.mimes' => 'The proof image must be a file of type: jpeg, png, jpg, gif, bmp, webp, pdf.',
            'proof_image.max' => 'The proof image may not be greater than 10 MB.',
        ];
    }
}

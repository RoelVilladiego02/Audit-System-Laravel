<?php

namespace App\Services;

use App\Models\AuditAnswer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Exception;

class ProofImageService
{
    /**
     * Storage disk for proof images
     */
    protected string $disk = 'public';

    /**
     * Base directory for proof images
     */
    protected string $basePath = 'proof-images';

    /**
     * Configuration loaded from config/proof_images.php
     */
    protected array $config = [];

    public function __construct()
    {
        $this->config = Config::get('proof_images', []);
    }

    /**
     * Upload and validate a proof image for an audit answer
     * 
     * @param AuditAnswer $answer The audit answer to attach the image to
     * @param UploadedFile $file The uploaded file
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function uploadProofImage(AuditAnswer $answer, UploadedFile $file): array
    {
        try {
            Log::debug('ProofImageService.uploadProofImage START', [
                'answer_id' => $answer->id,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'is_valid' => $file->isValid(),
                'error_code' => $file->getError(),
            ]);

            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                Log::warning('File validation failed', [
                    'answer_id' => $answer->id,
                    'file_name' => $file->getClientOriginalName(),
                    'validation_error' => $validation['message']
                ]);
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }

            Log::debug('File validation passed', [
                'answer_id' => $answer->id,
                'file_name' => $file->getClientOriginalName(),
            ]);

            // Store the file
            $filename = $file->getClientOriginalName();
            $path = $this->storeFile($file, $answer->id);

            if (!$path) {
                Log::error('Failed to store file - storeFile returned falsy', [
                    'answer_id' => $answer->id,
                    'file_name' => $filename,
                ]);
                throw new Exception('Failed to store file');
            }

            Log::debug('File stored successfully', [
                'answer_id' => $answer->id,
                'file_name' => $filename,
                'path' => $path,
            ]);

            // Store proof image in database with validation
            try {
                $isValid = $answer->storeProofImage($path, $filename);
                
                Log::info('Proof image stored in database', [
                    'answer_id' => $answer->id,
                    'filename' => $filename,
                    'path' => $path,
                    'validated' => $isValid
                ]);
            } catch (Exception $updateError) {
                Log::error('Failed to update answer record with proof image', [
                    'answer_id' => $answer->id,
                    'file_name' => $filename,
                    'error' => $updateError->getMessage(),
                    'trace' => $updateError->getTraceAsString()
                ]);
                throw $updateError;
            }

            return [
                'success' => $isValid,
                'message' => $isValid 
                    ? $this->getMessage('success')
                    : $answer->proof_image_validation_error ?? $this->getMessage('generic_name'),
                'data' => [
                    'path' => $path,
                    'filename' => $filename,
                    'validated' => $isValid
                ]
            ];
        } catch (Exception $e) {
            Log::error('CRITICAL: Error uploading proof image', [
                'answer_id' => $answer->id,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate uploaded file
     * 
     * @param UploadedFile $file
     * @return array ['valid' => bool, 'message' => string]
     */
    protected function validateFile(UploadedFile $file): array
    {
        try {
            if (!$file->isValid()) {
                $errorCode = $file->getError();
                $errorMap = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive.',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
                ];
                $message = $errorMap[$errorCode] ?? "Upload error code: $errorCode";
                
                Log::warning('Upload validation failed - file invalid', [
                    'file_name' => $file->getClientOriginalName(),
                    'error_code' => $errorCode,
                    'error_message' => $message,
                ]);
                
                return [
                    'valid' => false,
                    'message' => $message
                ];
            }

            $maxSize = $this->config['max_file_size_kb'] ?? 10240;
            
            // Check file size
            $fileSize = $file->getSize();
            $maxSizeBytes = $maxSize * 1024;
            if ($fileSize > $maxSizeBytes) {
                $message = "File size ({$fileSize} bytes) exceeds maximum ({$maxSizeBytes} bytes / {$maxSize} KB)";
                Log::warning('File size validation failed', [
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $fileSize,
                    'max_size' => $maxSizeBytes,
                ]);
                return [
                    'valid' => false,
                    'message' => str_replace(':max_size', $maxSize, $this->getMessage('file_too_large'))
                ];
            }

            // Check file extension
            $allowedExtensions = $this->config['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'pdf'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (!in_array($extension, $allowedExtensions)) {
                Log::warning('File extension validation failed', [
                    'file_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'allowed' => $allowedExtensions,
                ]);
                return [
                    'valid' => false,
                    'message' => str_replace(':extensions', implode(', ', $allowedExtensions), $this->getMessage('invalid_extension'))
                ];
            }

            Log::debug('File validation passed', [
                'file_name' => $file->getClientOriginalName(),
                'extension' => $extension,
                'size' => $fileSize,
            ]);

            return [
                'valid' => true,
                'message' => $this->getMessage('success')
            ];
        } catch (Exception $e) {
            Log::error('Exception during file validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'valid' => false,
                'message' => 'Error validating file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Store file in storage
     * 
     * @param UploadedFile $file
     * @param int $answerId
     * @return string|bool The stored path or false on failure
     */
    protected function storeFile(UploadedFile $file, int $answerId): string|bool
    {
        try {
            if (!$file->isValid()) {
                Log::warning('File is not valid', [
                    'answer_id' => $answerId,
                    'error' => $file->getError(),
                    'file_name' => $file->getClientOriginalName(),
                ]);
                return false;
            }

            $filename = $file->getClientOriginalName();
            
            // Create directory structure: proof-images/{year}/{month}/{day}/{answer_id}/
            $storagePath = $this->basePath . '/' . 
                date('Y') . '/' . 
                date('m') . '/' . 
                date('d') . '/' . 
                $answerId;

            Log::debug('Storing file', [
                'answer_id' => $answerId,
                'filename' => $filename,
                'storage_path' => $storagePath,
                'disk' => $this->disk,
            ]);

            $path = Storage::disk($this->disk)->putFileAs(
                $storagePath,
                $file,
                $filename
            );

            if (!$path) {
                Log::error('Storage::putFileAs returned null/false', [
                    'answer_id' => $answerId,
                    'filename' => $filename,
                    'storage_path' => $storagePath,
                ]);
                return false;
            }

            Log::debug('File stored successfully', [
                'answer_id' => $answerId,
                'filename' => $filename,
                'stored_path' => $path,
            ]);

            return $path;
        } catch (Exception $e) {
            Log::error('Exception in storeFile', [
                'answer_id' => $answerId,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Delete a proof image
     * 
     * @param AuditAnswer $answer
     * @return bool
     */
    public function deleteProofImage(AuditAnswer $answer): bool
    {
        try {
            if (!$answer->proof_image_path) {
                return true; // Nothing to delete
            }

            if (Storage::disk($this->disk)->exists($answer->proof_image_path)) {
                Storage::disk($this->disk)->delete($answer->proof_image_path);
            }

            // Clear the database fields
            $answer->update([
                'proof_image_path' => null,
                'proof_image_name' => null,
                'proof_image_validated' => false,
                'proof_image_validation_error' => null
            ]);

            Log::info('Proof image deleted', [
                'answer_id' => $answer->id,
                'path' => $answer->proof_image_path
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error deleting proof image', [
                'answer_id' => $answer->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get public URL for a proof image
     * 
     * @param AuditAnswer $answer
     * @return string|null
     */
    public function getProofImageUrl(AuditAnswer $answer): ?string
    {
        if (!$answer->proof_image_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($answer->proof_image_path);
    }

    /**
     * Check if a proof image file exists
     * 
     * @param AuditAnswer $answer
     * @return bool
     */
    public function proofImageExists(AuditAnswer $answer): bool
    {
        if (!$answer->proof_image_path) {
            return false;
        }

        return Storage::disk($this->disk)->exists($answer->proof_image_path);
    }

    /**
     * Re-validate all proof images for a submission
     * Useful when validation rules change
     * 
     * @param int $submissionId
     * @return array ['total' => int, 'validated' => int, 'failed' => int]
     */
    public function revalidateSubmissionImages(int $submissionId): array
    {
        $answers = AuditAnswer::where('audit_submission_id', $submissionId)
            ->withProofImage()
            ->get();

        $stats = [
            'total' => $answers->count(),
            'validated' => 0,
            'failed' => 0
        ];

        foreach ($answers as $answer) {
            $validation = $answer->validateProofImageName($answer->proof_image_name);
            
            $answer->update([
                'proof_image_validated' => $validation['valid'],
                'proof_image_validation_error' => !$validation['valid'] ? $validation['message'] : null
            ]);

            if ($validation['valid']) {
                $stats['validated']++;
            } else {
                $stats['failed']++;
            }

            Log::info('Re-validated proof image', [
                'answer_id' => $answer->id,
                'valid' => $validation['valid']
            ]);
        }

        return $stats;
    }

    /**
     * Get statistics about proof images in a submission
     * 
     * @param int $submissionId
     * @return array
     */
    public function getSubmissionImageStats(int $submissionId): array
    {
        $answers = AuditAnswer::where('audit_submission_id', $submissionId)->get();
        
        $yesAnswers = $answers->filter(function($a) {
            return $a->requiresProofImage();
        });

        $answersWithImages = $yesAnswers->filter(function($a) {
            return $a->hasProofImage();
        });

        $validatedImages = $answersWithImages->filter(function($a) {
            return $a->proof_image_validated;
        });

        return [
            'total_answers' => $answers->count(),
            'yes_answers' => $yesAnswers->count(),
            'answers_with_images' => $answersWithImages->count(),
            'validated_images' => $validatedImages->count(),
            'missing_images' => $yesAnswers->count() - $answersWithImages->count(),
            'invalid_images' => $answersWithImages->count() - $validatedImages->count(),
            'completion_percentage' => $yesAnswers->count() > 0 
                ? round(($validatedImages->count() / $yesAnswers->count()) * 100, 2)
                : 100
        ];
    }

    /**
     * Get message from configuration
     * 
     * @param string $key Message key
     * @param array $replacements Optional replacements
     * @return string
     */
    protected function getMessage(string $key, array $replacements = []): string
    {
        $messages = $this->config['messages'] ?? [];
        $message = $messages[$key] ?? 'Validation error occurred.';
        
        foreach ($replacements as $search => $replace) {
            $message = str_replace(':' . $search, $replace, $message);
        }
        
        return $message;
    }
}

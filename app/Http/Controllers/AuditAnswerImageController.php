<?php

namespace App\Http\Controllers;

use App\Models\AuditAnswer;
use App\Models\AuditSubmission;
use App\Services\ProofImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class AuditAnswerImageController extends Controller
{
    protected ProofImageService $imageService;

    public function __construct(ProofImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Upload a proof image for an audit answer
     * POST /api/audit-answers/{answer}/proof-image
     * 
     * @param Request $request
     * @param int $answer Answer ID
     * @return JsonResponse
     */
    public function uploadProofImage(Request $request, int $answer): JsonResponse
    {
        try {
            // 📊 DEBUG: Comprehensive logging BEFORE validation
            Log::debug('========== IMAGE UPLOAD REQUEST START ==========', [
                'timestamp' => now(),
                'answer_id_requested' => $answer,
                'user_id' => auth()->id(),
                'request_method' => $request->method(),
                'request_url' => $request->url(),
                'request_path' => $request->path(),
            ]);
            
            Log::debug('REQUEST HEADERS:', [
                'content_type' => $request->header('Content-Type'),
                'authorization' => $request->header('Authorization') ? 'Bearer ' . substr($request->header('Authorization'), 7, 20) . '...' : 'MISSING',
                'accept' => $request->header('Accept'),
                'all_headers' => array_filter($request->headers->all(), fn($k) => !in_array($k, ['authorization']), ARRAY_FILTER_USE_KEY),
            ]);

            Log::debug('REQUEST FILES & DATA:', [
                'has_files' => count($request->allFiles()) > 0,
                'files_keys' => array_keys($request->allFiles()),
                'has_proof_image' => $request->hasFile('proof_image'),
                'file_details' => $request->file('proof_image') ? [
                    'name' => $request->file('proof_image')->getClientOriginalName(),
                    'type' => $request->file('proof_image')->getMimeType(),
                    'size' => $request->file('proof_image')->getSize(),
                    'temp_path' => $request->file('proof_image')->getRealPath(),
                    'is_valid' => $request->file('proof_image')->isValid(),
                    'error' => $request->file('proof_image')->getError(),
                ] : 'NO FILE',
                'all_input_keys' => array_keys($request->all()),
                'input_data_sample' => array_slice($request->all(), 0, 5),
            ]);

            Log::debug('DATABASE CHECK BEFORE VALIDATION:', [
                'total_audit_answers' => AuditAnswer::count(),
                'answers_for_user' => AuditAnswer::whereHas('auditSubmission', fn($q) => 
                    $q->where('user_id', auth()->id())
                )->count(),
            ]);

            // Validate file is present and valid BEFORE validation rules
            if (!$request->hasFile('proof_image')) {
                Log::warning('NO FILE UPLOADED - validation will fail', [
                    'answer_id' => $answer,
                    'has_file' => false,
                    'all_files' => array_keys($request->allFiles()),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No file was uploaded. Please select a file and try again.',
                    'debug' => 'proof_image field is empty'
                ], 422);
            }

            // Now run standard validation
            $validated = $request->validate([
                'proof_image' => 'required|file|mimes:jpeg,png,jpg,gif,bmp,webp,pdf|max:10240'
            ]);

            Log::debug('VALIDATION PASSED - Looking for answer:', [
                'answer_id' => $answer,
                'validated_file_name' => $validated['proof_image']->getClientOriginalName(),
            ]);

            // ✅ CRITICAL: Load answer WITH submission relationship to avoid lazy loading issues
            $auditAnswer = AuditAnswer::with('auditSubmission')->findOrFail($answer);
            
            Log::debug('Answer found and loaded:', [
                'answer_id' => $auditAnswer->id,
                'submission_id' => $auditAnswer->audit_submission_id,
                'has_submission_relation' => $auditAnswer->auditSubmission !== null,
                'submission_user_id' => $auditAnswer->auditSubmission?->user_id,
                'current_user_id' => auth()->id(),
            ]);
            
            // Authorization check - user can only upload for their own submissions
            if (!$this->authorizeForAnswer($auditAnswer)) {
                Log::warning('Unauthorized image upload attempt', [
                    'answer_id' => $answer,
                    'user_id' => auth()->id(),
                    'submission_user_id' => $auditAnswer->auditSubmission?->user_id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to upload proof for this answer. Make sure you\'re working on your own submission.'
                ], 403);
            }

            // Upload and validate the image
            $result = $this->imageService->uploadProofImage(
                $auditAnswer,
                $request->file('proof_image')
            );

            $statusCode = $result['success'] ? 200 : 422;
            
            return response()->json($result, $statusCode);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('========== MODEL NOT FOUND ==========', [
                'answer_id_requested' => $answer,
                'user_id' => auth()->id(),
                'exception_type' => class_basename($e),
                'exception_message' => $e->getMessage(),
                'total_answers_in_db' => AuditAnswer::count(),
                'user_submission_answers' => AuditAnswer::whereHas('auditSubmission', fn($q) => 
                    $q->where('user_id', auth()->id())
                )->count(),
                'answer_found_in_any_submission' => AuditAnswer::where('id', $answer)->exists(),
                'answer_belongs_to_user' => AuditAnswer::where('id', $answer)
                    ->whereHas('auditSubmission', fn($q) => $q->where('user_id', auth()->id()))
                    ->exists(),
                'answer_details' => AuditAnswer::where('id', $answer)->first() ? [
                    'id' => AuditAnswer::where('id', $answer)->first()->id,
                    'submission_id' => AuditAnswer::where('id', $answer)->first()->audit_submission_id,
                    'submission_user_id' => AuditAnswer::where('id', $answer)->first()?->auditSubmission?->user_id,
                ] : null,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Audit answer not found. This answer ID does not exist or may belong to a different submission. Please save your draft again and try uploading.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('========== ERROR UPLOADING PROOF IMAGE ==========', [
                'answer_id' => $answer,
                'user_id' => auth()->id(),
                'exception_type' => class_basename($e),
                'exception_message' => $e->getMessage(),
                'file_line' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the image. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete a proof image for an audit answer
     * DELETE /api/audit-answers/{answer}/proof-image
     * 
     * @param int $answer Answer ID
     * @return JsonResponse
     */
    public function deleteProofImage(int $answer): JsonResponse
    {
        try {
            $auditAnswer = AuditAnswer::findOrFail($answer);
            
            // Authorization check
            if (!$this->authorizeForAnswer($auditAnswer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete proof for this answer.'
                ], 403);
            }

            // Delete the image
            if (!$this->imageService->deleteProofImage($auditAnswer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete proof image.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Proof image deleted successfully.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit answer not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting proof image', [
                'answer_id' => $answer,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the image.'
            ], 500);
        }
    }

    /**
     * Get proof image URL for an audit answer
     * GET /api/audit-answers/{answer}/proof-image/url
     * 
     * @param int $answer Answer ID
     * @return JsonResponse
     */
    public function getProofImageUrl(int $answer): JsonResponse
    {
        try {
            $auditAnswer = AuditAnswer::findOrFail($answer);
            
            // Authorization check
            if (!$this->authorizeForAnswer($auditAnswer)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this answer.'
                ], 403);
            }

            $url = $this->imageService->getProofImageUrl($auditAnswer);

            return response()->json([
                'success' => true,
                'url' => $url,
                'has_image' => !is_null($url),
                'image_data' => [
                    'filename' => $auditAnswer->proof_image_name,
                    'validated' => $auditAnswer->proof_image_validated,
                    'validation_error' => $auditAnswer->proof_image_validation_error,
                    'is_valid_answer' => $auditAnswer->isAnswerValid()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit answer not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving proof image URL', [
                'answer_id' => $answer,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the image.'
            ], 500);
        }
    }

    /**
     * Get submission image statistics
     * GET /api/audit-submissions/{submission}/image-stats
     * 
     * @param int $submission Submission ID
     * @return JsonResponse
     */
    public function getSubmissionImageStats(int $submission): JsonResponse
    {
        try {
            $auditSubmission = AuditSubmission::findOrFail($submission);
            
            // Authorization check
            $user = auth()->user();
            if ($user->id !== $auditSubmission->user_id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this submission.'
                ], 403);
            }

            $stats = $this->imageService->getSubmissionImageStats($submission);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit submission not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving submission image stats', [
                'submission_id' => $submission,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving statistics.'
            ], 500);
        }
    }

    /**
     * Revalidate all proof images in a submission (Admin only)
     * POST /api/audit-submissions/{submission}/revalidate-images
     * 
     * @param int $submission Submission ID
     * @return JsonResponse
     */
    public function revalidateSubmissionImages(int $submission): JsonResponse
    {
        try {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only admins can revalidate images.'
                ], 403);
            }

            $auditSubmission = AuditSubmission::findOrFail($submission);
            $stats = $this->imageService->revalidateSubmissionImages($submission);

            return response()->json([
                'success' => true,
                'message' => 'Images revalidated successfully.',
                'data' => $stats
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit submission not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error revalidating submission images', [
                'submission_id' => $submission,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while revalidating images.'
            ], 500);
        }
    }

    /**
     * Get all answers requiring proof images in a submission
     * GET /api/audit-submissions/{submission}/answers-needing-images
     * 
     * @param int $submission Submission ID
     * @return JsonResponse
     */
    public function getAnswersNeedingImages(int $submission): JsonResponse
    {
        try {
            $auditSubmission = AuditSubmission::findOrFail($submission);
            
            // Authorization check
            $user = auth()->user();
            if ($user->id !== $auditSubmission->user_id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this submission.'
                ], 403);
            }

            $answers = $auditSubmission->answers()
                ->with('question')
                ->requiresProofImage()
                ->get()
                ->map(function ($answer) {
                    return [
                        'id' => $answer->id,
                        'question' => $answer->question->question,
                        'answer' => $answer->answer,
                        'has_image' => $answer->hasProofImage(),
                        'image_validated' => $answer->proof_image_validated,
                        'validation_error' => $answer->proof_image_validation_error,
                        'status' => $answer->isAnswerValid() ? 'valid' : 'invalid'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total_yes_answers' => $answers->count(),
                    'answers_with_valid_images' => $answers->where('status', 'valid')->count(),
                    'answers_needing_images' => $answers->where('has_image', false)->count(),
                    'answers_with_invalid_images' => $answers->where('status', 'invalid')
                        ->where('has_image', true)
                        ->count(),
                    'answers' => $answers->values()
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Audit submission not found.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving answers needing images', [
                'submission_id' => $submission,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving answers.'
            ], 500);
        }
    }

    /**
     * Validate that an answer exists and belongs to the current user's submission
     * GET /api/audit-answers/{answer}/validate-ownership
     * 
     * @param int $answer Answer ID
     * @return JsonResponse
     */
    public function validateAnswerOwnership(int $answer): JsonResponse
    {
        try {
            $auditAnswer = AuditAnswer::find($answer);
            
            if (!$auditAnswer) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Answer does not exist'
                ]);
            }

            if (!$this->authorizeForAnswer($auditAnswer)) {
                return response()->json([
                    'success' => false,
                    'valid' => false,
                    'message' => 'Answer does not belong to your submission'
                ]);
            }

            return response()->json([
                'success' => true,
                'valid' => true,
                'answer_id' => $auditAnswer->id,
                'question_id' => $auditAnswer->audit_question_id,
                'submission_id' => $auditAnswer->audit_submission_id,
                'question_text' => $auditAnswer->auditQuestion?->question ?? 'Unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Error validating answer ownership', [
                'answer_id' => $answer,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while validating the answer.'
            ], 500);
        }
    }

    /**
     * Check authorization for an audit answer
     * Users can only access/modify answers in their own submissions or if they're admin
     * 
     * @param AuditAnswer $answer
     * @return bool
     */
    private function authorizeForAnswer(AuditAnswer $answer): bool
    {
        $user = auth()->user();
        $submission = $answer->auditSubmission;
        
        // Debug logging
        Log::debug('Authorization check for answer:', [
            'answer_id' => $answer->id,
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'submission_id' => $submission?->id,
            'submission_user_id' => $submission?->user_id,
            'submission_is_null' => $submission === null,
            'user_is_null' => $user === null,
        ]);
        
        // Check if submission exists and is properly loaded
        if (!$submission) {
            Log::error('Authorization failed: submission is null or not loaded', [
                'answer_id' => $answer->id,
                'submission_loaded_relations' => $answer->getRelations(),
            ]);
            return false;
        }
        
        // Check authorization: user owns the submission OR is admin
        $isOwner = $user->id === $submission->user_id;
        $isAdmin = $user->role === 'admin';
        
        if ($isOwner || $isAdmin) {
            Log::debug('Authorization GRANTED', [
                'answer_id' => $answer->id,
                'user_id' => $user->id,
                'is_owner' => $isOwner,
                'is_admin' => $isAdmin,
            ]);
            return true;
        }
        
        Log::warning('Authorization DENIED', [
            'answer_id' => $answer->id,
            'user_id' => $user->id,
            'submission_user_id' => $submission->user_id,
            'user_role' => $user->role,
            'is_owner' => $isOwner,
            'is_admin' => $isAdmin,
        ]);
        
        return false;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AuditQuestionController extends Controller
{
    /**
     * Display a listing of audit questions.
     */
    public function index(): JsonResponse
    {
        try {
            $questions = AuditQuestion::active()
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json($questions);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve questions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created audit question.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question' => 'required|string|max:1000',
                'description' => 'nullable|string|max:2000',
                'category' => 'required|string|max:255',
                'possible_answers' => 'required|array|min:1',
                'possible_answers.*' => 'required|string|max:255',
                'risk_criteria' => 'required|array',
                'risk_criteria.high' => 'nullable|array',
                'risk_criteria.high.*' => 'string|max:255',
                'risk_criteria.medium' => 'nullable|array', 
                'risk_criteria.medium.*' => 'string|max:255',
                'risk_criteria.low' => 'nullable|array',
                'risk_criteria.low.*' => 'string|max:255',
                'possible_recommendation' => 'nullable|string|max:2000',
            ]);

            // Ensure unique possible answers
            $validated['possible_answers'] = array_unique($validated['possible_answers']);

            // Validate that risk criteria answers exist in possible answers (excluding "Others")
            $this->validateRiskCriteria($validated['risk_criteria'], $validated['possible_answers']);

            $question = AuditQuestion::create($validated);
            
            return response()->json([
                'message' => 'Question created successfully.',
                'data' => $question
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create question.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified audit question.
     */
    public function show(AuditQuestion $auditQuestion): JsonResponse
    {
        try {
            // Include usage statistics for admins
            $data = $auditQuestion->toArray();
            
            if (auth()->user() && auth()->user()->isAdmin()) {
                $data['usage_stats'] = $auditQuestion->getUsageStats();
                $data['formatted_risk_criteria'] = $auditQuestion->formatted_risk_criteria;
            }
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Question not found.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified audit question.
     */
    public function update(Request $request, AuditQuestion $auditQuestion): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question' => 'required|string|max:1000',
                'description' => 'nullable|string|max:2000',
                'category' => 'required|string|max:255',
                'possible_answers' => 'required|array|min:1',
                'possible_answers.*' => 'required|string|max:255',
                'risk_criteria' => 'required|array',
                'risk_criteria.high' => 'nullable|array',
                'risk_criteria.high.*' => 'string|max:255',
                'risk_criteria.medium' => 'nullable|array',
                'risk_criteria.medium.*' => 'string|max:255', 
                'risk_criteria.low' => 'nullable|array',
                'risk_criteria.low.*' => 'string|max:255',
                'possible_recommendation' => 'nullable|string|max:2000',
            ]);

            // Check if question is being used in answers
            if ($auditQuestion->answers()->exists()) {
                // Only allow minor updates if question is in use
                $allowedFields = ['description', 'category'];
                $updateData = array_intersect_key($validated, array_flip($allowedFields));
                
                if (empty($updateData)) {
                    return response()->json([
                        'message' => 'Cannot modify question structure that is referenced in existing answers.',
                        'suggestion' => 'Create a new question instead or archive this one.'
                    ], 409);
                }
                
                $auditQuestion->update($updateData);
            } else {
                // Ensure unique possible answers
                $validated['possible_answers'] = array_unique($validated['possible_answers']);
                
                // Validate risk criteria
                $this->validateRiskCriteria($validated['risk_criteria'], $validated['possible_answers']);
                
                $auditQuestion->update($validated);
            }
            
            return response()->json([
                'message' => 'Question updated successfully.',
                'data' => $auditQuestion->fresh()
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update question.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive (soft delete) the specified audit question.
     */
    public function destroy(AuditQuestion $auditQuestion): JsonResponse
    {
        try {
            // Use soft delete to maintain data integrity
            $auditQuestion->delete();
            
            return response()->json([
                'message' => 'Question archived successfully.',
                'note' => 'Question is archived but existing audit data remains intact.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to archive question.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get questions with comprehensive statistics (admin only).
     */
    public function statistics(): JsonResponse
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $questions = AuditQuestion::withCount([
                'answers',
                'answers as high_risk_count' => function ($query) {
                    $query->where(function($q) {
                        $q->where('admin_risk_level', 'high')
                          ->orWhere(function($subQ) {
                              $subQ->whereNull('admin_risk_level')
                                   ->where('system_risk_level', 'high');
                          });
                    });
                },
                'answers as pending_review_count' => function ($query) {
                    $query->pendingReview();
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($question) {
                $usageStats = $question->getUsageStats();
                
                return [
                    'id' => $question->id,
                    'question' => $question->question,
                    'description' => $question->description,
                    'category' => $question->category,
                    'possible_answers' => $question->possible_answers,
                    'formatted_risk_criteria' => $question->formatted_risk_criteria,
                    'answers_count' => $question->answers_count,
                    'high_risk_count' => $question->high_risk_count,
                    'pending_review_count' => $question->pending_review_count,
                    'usage_stats' => $usageStats,
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                ];
            });

            return response()->json($questions);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve question statistics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get archived questions (admin only).
     */
    public function archived(): JsonResponse
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $questions = AuditQuestion::onlyTrashed()
                ->withCount('answers')
                ->orderBy('deleted_at', 'desc')
                ->get();

            return response()->json($questions);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve archived questions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore archived question (admin only).
     */
    public function restore($id): JsonResponse
    {
        try {
            if (!auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $question = AuditQuestion::onlyTrashed()->findOrFail($id);
            $question->restore();

            return response()->json([
                'message' => 'Question restored successfully.',
                'data' => $question
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to restore question.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate that risk criteria answers exist in possible answers, excluding "Others".
     */
    private function validateRiskCriteria(array $riskCriteria, array $possibleAnswers): void
    {
        // Remove "Others" from validation since it allows custom answers not in risk_criteria
        $possibleAnswersWithoutOthers = array_diff($possibleAnswers, ['Others']);
        
        foreach ($riskCriteria as $level => $answers) {
            if (!is_array($answers)) continue;
            
            foreach ($answers as $answer) {
                if (!in_array($answer, $possibleAnswersWithoutOthers, true)) {
                    throw ValidationException::withMessages([
                        "risk_criteria.{$level}" => "Risk criteria answer '{$answer}' must be one of the possible answers (excluding 'Others')."
                    ]);
                }
            }
        }
    }
}
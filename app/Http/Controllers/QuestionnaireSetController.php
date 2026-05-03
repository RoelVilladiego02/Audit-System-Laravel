<?php

namespace App\Http\Controllers;

use App\Models\AuditQuestionnaireSet;
use App\Models\AuditQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class QuestionnaireSetController extends Controller
{
    /**
     * Display a listing of all questionnaire sets.
     */
    public function index(): JsonResponse
    {
        try {
            $sets = AuditQuestionnaireSet::withCount(['questions', 'submissions'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($sets);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve questionnaire sets.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display only active questionnaire sets (for users to choose from).
     */
    public function activeOnly(): JsonResponse
    {
        try {
            $sets = AuditQuestionnaireSet::active()
                ->withCount('questions')
                ->orderBy('name')
                ->get()
                ->map(function ($set) {
                    return [
                        'id' => $set->id,
                        'name' => $set->name,
                        'description' => $set->description,
                        'questions_count' => $set->questions_count,
                    ];
                });

            return response()->json($sets);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve active questionnaire sets.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created questionnaire set.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:audit_questionnaire_sets,name',
                'description' => 'nullable|string|max:2000',
                'status' => 'required|in:draft,active,archived',
                'question_ids' => 'nullable|array',
                'question_ids.*' => 'integer|exists:audit_questions,id',
            ]);

            return DB::transaction(function () use ($validated, $request) {
                $set = AuditQuestionnaireSet::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'status' => $validated['status'],
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);

                // Associate questions with the set
                if (!empty($validated['question_ids'])) {
                    AuditQuestion::whereIn('id', $validated['question_ids'])
                        ->update(['questionnaire_set_id' => $set->id]);
                }

                return response()->json([
                    'message' => 'Questionnaire set created successfully.',
                    'data' => $set->load('questions')
                ], 201);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create questionnaire set.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified questionnaire set.
     */
    public function show(AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $data = $set->load([
                'questions:id,question,description,category,questionnaire_set_id,possible_answers,risk_criteria,possible_recommendation',
                'creator:id,name,email',
                'updater:id,name,email'
            ])->toArray();

            // Add statistics if admin
            if (auth()->user() && auth()->user()->isAdmin()) {
                $data['statistics'] = $set->getStatistics();
                $data['question_count'] = $set->questions()->count();
                $data['categories'] = $set->questions()->distinct()->pluck('category')->toArray();
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Questionnaire set not found.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified questionnaire set.
     */
    public function update(Request $request, AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:audit_questionnaire_sets,name,' . $set->id,
                'description' => 'nullable|string|max:2000',
                'status' => 'required|in:draft,active,archived',
                'question_ids' => 'nullable|array',
                'question_ids.*' => 'integer|exists:audit_questions,id',
            ]);

            return DB::transaction(function () use ($validated, $set, $request) {
                // Check if set is in use (has submissions in non-draft status)
                if ($set->submissions()->whereIn('status', ['submitted', 'under_review', 'completed'])->exists()) {
                    // Only allow status and description changes
                    if ($request->has('question_ids')) {
                        return response()->json([
                            'message' => 'Cannot modify questions in a set that has active submissions.',
                            'suggestion' => 'Archive this set and create a new one with the modified questions.'
                        ], 409);
                    }
                }

                $set->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'status' => $validated['status'],
                    'updated_by' => $request->user()->id,
                ]);

                // Update question associations if provided
                if (isset($validated['question_ids'])) {
                    // Remove old associations
                    $set->questions()->update(['questionnaire_set_id' => null]);
                    
                    // Add new associations
                    AuditQuestion::whereIn('id', $validated['question_ids'])
                        ->update(['questionnaire_set_id' => $set->id]);
                }

                return response()->json([
                    'message' => 'Questionnaire set updated successfully.',
                    'data' => $set->fresh()->load('questions')
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update questionnaire set.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete (soft delete) the specified questionnaire set.
     */
    public function destroy(AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            if (!$set->canBeDeleted()) {
                return response()->json([
                    'message' => 'Cannot delete a questionnaire set with active submissions.',
                    'suggestion' => 'Archive the set or wait until all submissions are completed.'
                ], 409);
            }

            $set->delete();

            return response()->json([
                'message' => 'Questionnaire set deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete questionnaire set.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate a questionnaire set.
     */
    public function duplicate(Request $request, AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:audit_questionnaire_sets,name',
            ]);

            $newSet = $set->duplicate($validated['name'], $request->user()->id);

            return response()->json([
                'message' => 'Questionnaire set duplicated successfully.',
                'data' => $newSet->load('questions')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to duplicate questionnaire set.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for a questionnaire set.
     */
    public function statistics(AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $stats = $set->getStatistics();

            return response()->json([
                'name' => $set->name,
                'status' => $set->status,
                'question_count' => $set->questions()->count(),
                'statistics' => $stats,
                'categories' => $set->questions()->distinct()->pluck('category')->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve statistics.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add questions to a questionnaire set.
     */
    public function addQuestions(Request $request, AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question_ids' => 'required|array|min:1',
                'question_ids.*' => 'integer|exists:audit_questions,id',
            ]);

            return DB::transaction(function () use ($validated, $set) {
                AuditQuestion::whereIn('id', $validated['question_ids'])
                    ->update(['questionnaire_set_id' => $set->id]);

                return response()->json([
                    'message' => 'Questions added to set successfully.',
                    'question_count' => $set->fresh()->questions()->count(),
                ]);
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add questions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove questions from a questionnaire set.
     */
    public function removeQuestions(Request $request, AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $validated = $request->validate([
                'question_ids' => 'required|array|min:1',
                'question_ids.*' => 'integer|exists:audit_questions,id',
            ]);

            AuditQuestion::whereIn('id', $validated['question_ids'])
                ->where('questionnaire_set_id', $set->id)
                ->update(['questionnaire_set_id' => null]);

            return response()->json([
                'message' => 'Questions removed from set successfully.',
                'question_count' => $set->fresh()->questions()->count(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove questions.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive a questionnaire set.
     */
    public function archive(AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $set->update(['status' => 'archived']);

            return response()->json([
                'message' => 'Questionnaire set archived successfully.',
                'data' => $set,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to archive questionnaire set.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a questionnaire set.
     */
    public function restore(AuditQuestionnaireSet $set): JsonResponse
    {
        try {
            $set->restore();

            return response()->json([
                'message' => 'Questionnaire set restored successfully.',
                'data' => $set,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to restore questionnaire set.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

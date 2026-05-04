<?php

namespace App\Http\Controllers;

use App\Models\VulnerabilitySubmission;
use App\Models\AuditSubmission;
use App\Models\Vulnerability;
use App\Models\AuditAnswer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    /**
     * Get analytics data for vulnerabilities and/or audits.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $timeRange = $request->input('timeRange', 'week');
            $userId = $request->input('userId');
            $type = $request->input('type', 'all');
            $startDateInput = $request->input('startDate');
            $endDateInput = $request->input('endDate');

            $startDate = $this->getStartDate($timeRange, $startDateInput, $endDateInput);

            Log::info('Analytics Request', [
                'timeRange' => $timeRange,
                'userId' => $userId,
                'type' => $type,
                'startDate' => $startDate->toDateTimeString()
            ]);

            // Authorization checks
            if ($userId && !$request->user()->isAdmin() && $userId != $request->user()->id) {
                return response()->json(['message' => 'Unauthorized to access other users\' analytics'], 403);
            }
            // Restrict non-admins from accessing global analytics (no userId)
            if (!$userId && !$request->user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized: Non-admin users can only access their own analytics'], 403);
            }

            // Get data based on type
            if ($type === 'vulnerability') {
                $data = $this->getVulnerabilityAnalytics($startDate, $userId);
            } elseif ($type === 'audit') {
                $data = $this->getAuditAnalytics($startDate, $userId);
            } else {
                $data = $this->getCombinedAnalytics($startDate, $userId);
            }

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Failed to generate analytics: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to generate analytics.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Calculate start date based on time range or custom dates.
     */
    private function getStartDate(string $timeRange, ?string $startDateInput = null, ?string $endDateInput = null): Carbon
    {
        if ($timeRange === 'custom' && $startDateInput) {
            try {
                $startDate = Carbon::parse($startDateInput);
                if ($endDateInput) {
                    $endDate = Carbon::parse($endDateInput);
                    if ($endDate->isBefore($startDate)) {
                        throw new \Exception('End date must be after start date');
                    }
                }
                return $startDate;
            } catch (\Exception $e) {
                Log::warning('Invalid custom date range', ['error' => $e->getMessage()]);
                return Carbon::now()->subWeek();
            }
        }

        return match($timeRange) {
            'week' => Carbon::now()->subWeek(),
            'year' => Carbon::now()->subYear(),
            'quarter' => Carbon::now()->subMonths(3),
            'month' => Carbon::now()->subMonth(),
            'all' => Carbon::createFromTimestamp(0),
            default => Carbon::now()->subWeek(),
        };
    }

    /**
     * Get vulnerability analytics data.
     */
    private function getVulnerabilityAnalytics(Carbon $startDate, ?int $userId = null): array
    {
        $query = VulnerabilitySubmission::query()
            ->when($userId, function ($q) use ($userId) {
                return $q->where('user_id', (int) $userId);
            })
            ->where('created_at', '>=', $startDate);

        return [
            'type' => 'vulnerability',
            'totalSubmissions' => (int) $query->count(),
            'riskDistribution' => $this->getVulnerabilityRiskDistribution($query->clone()),
            'averageRiskScore' => $this->getAverageRiskScore($query->clone()),
            'statusDistribution' => $this->getStatusDistribution($query->clone()),
            'assignmentStats' => $this->getAssignmentStats($query->clone()),
            'commonVulnerabilities' => $this->getCommonVulnerabilities($query->clone()),
            'severityDistribution' => $this->getVulnerabilitySeverityDistribution($query->clone()),
            'resolutionStats' => $this->getVulnerabilityResolutionStats($query->clone()),
            'sourceBreakdown' => $this->getVulnerabilitySourceBreakdown($query->clone()),
        ];
    }

    /**
     * Get audit analytics data.
     */
    private function getAuditAnalytics(Carbon $startDate, ?int $userId = null): array
    {
        $query = AuditSubmission::query()
            ->when($userId, function ($q) use ($userId) {
                return $q->where('user_id', (int) $userId);
            })
            ->where('created_at', '>=', $startDate);

        return [
            'type' => 'audit',
            'totalSubmissions' => (int) $query->count(),
            'riskDistribution' => $this->getAuditRiskDistribution($query->clone()),
            'riskProportion' => $this->getAuditRiskProportion($query->clone()),
            'answerRiskDistribution' => $this->getAnswerRiskDistribution($query->clone()),
            'averageRiskScore' => $this->getAuditAverageRiskScore($query->clone()),
            'commonHighRisks' => $this->getCommonHighRiskAudits($query->clone()),
            'reviewStats' => $this->getAuditReviewStats($query->clone()),
            'questionCategoryStats' => $this->getQuestionCategoryStats($query->clone()),
            'adminVsSystemRisk' => $this->getAdminVsSystemRiskComparison($query->clone()),
        ];
    }

    /**
     * Get combined vulnerability and audit analytics.
     */
    private function getCombinedAnalytics(Carbon $startDate, ?int $userId = null): array
    {
        $vulnData = $this->getVulnerabilityAnalytics($startDate, $userId);
        $auditData = $this->getAuditAnalytics($startDate, $userId);

        // Get audit-to-vulnerability conversion stats
        $auditQuery = AuditSubmission::query()
            ->when($userId, function ($q) use ($userId) {
                return $q->where('user_id', (int) $userId);
            })
            ->where('created_at', '>=', $startDate);

        $conversionStats = $this->getAuditToVulnerabilityConversion($auditQuery);
        
        // Get questionnaire set breakdown
        $questionnaireSetStats = $this->getQuestionnaireSetBreakdown($startDate, $userId);
        
        // Get advanced questionnaire set analytics
        $setPerformanceMetrics = $this->getQuestionnaireSetPerformanceMetrics($startDate, $userId);
        $questionEffectivenessAnalysis = $this->getQuestionEffectivenessAnalysis($startDate, $userId);
        $setToRiskCorrelation = $this->getSetToRiskCorrelation($startDate, $userId);

        return [
            'type' => 'combined',
            'vulnerability' => $vulnData,
            'audit' => $auditData,
            'summary' => [
                'totalSubmissions' => $vulnData['totalSubmissions'] + $auditData['totalSubmissions'],
                'vulnerabilitySubmissions' => $vulnData['totalSubmissions'],
                'auditSubmissions' => $auditData['totalSubmissions'],
            ],
            'conversionStats' => $conversionStats,
            'by_questionnaire_set' => $questionnaireSetStats,
            'questionnaire_set_performance' => $setPerformanceMetrics,
            'question_effectiveness' => $questionEffectivenessAnalysis,
            'set_risk_correlation' => $setToRiskCorrelation,
        ];
    }

    /**
     * Get vulnerability risk distribution.
     */
    private function getVulnerabilityRiskDistribution($query): array
    {
        $distribution = $query->groupBy('risk_level')
            ->select('risk_level', DB::raw('count(*) as count'))
            ->pluck('count', 'risk_level')
            ->toArray();

        return [
            'high' => (int) ($distribution['high'] ?? 0),
            'medium' => (int) ($distribution['medium'] ?? 0),
            'low' => (int) ($distribution['low'] ?? 0)
        ];
    }

    /**
     * Get audit risk distribution.
     */
    private function getAuditRiskDistribution($query): array
    {
        $distribution = $query->groupBy('system_overall_risk')
            ->select('system_overall_risk as risk', DB::raw('count(*) as count'))
            ->pluck('count', 'risk')
            ->toArray();

        return [
            'high' => (int) ($distribution['high'] ?? 0),
            'medium' => (int) ($distribution['medium'] ?? 0),
            'low' => (int) ($distribution['low'] ?? 0)
        ];
    }

    /**
     * Get proportional risk distribution for audit submissions.
     */
    private function getAuditRiskProportion($query): array
    {
        $totalSubmissions = $query->count();

        $distribution = $this->getAuditRiskDistribution($query);

        return [
            'high' => $totalSubmissions > 0 ? round($distribution['high'] / $totalSubmissions * 100, 1) : 0,
            'medium' => $totalSubmissions > 0 ? round($distribution['medium'] / $totalSubmissions * 100, 1) : 0,
            'low' => $totalSubmissions > 0 ? round($distribution['low'] / $totalSubmissions * 100, 1) : 0,
            'total_submissions' => (int) $totalSubmissions
        ];
    }

    /**
     * Get risk level distribution for individual audit answers.
     */
    private function getAnswerRiskDistribution($query): array
    {
        $totalAnswers = AuditAnswer::whereIn('audit_submission_id', $query->pluck('id'))
            ->count();

        $distribution = AuditAnswer::whereIn('audit_submission_id', $query->pluck('id'))
            ->groupBy(DB::raw('COALESCE(admin_risk_level, system_risk_level)'))
            ->select(
                DB::raw('COALESCE(admin_risk_level, system_risk_level) as risk_level'),
                DB::raw('count(*) as count')
            )
            ->pluck('count', 'risk_level')
            ->toArray();

        return [
            'high' => $totalAnswers > 0 ? round(($distribution['high'] ?? 0) / $totalAnswers * 100, 1) : 0,
            'medium' => $totalAnswers > 0 ? round(($distribution['medium'] ?? 0) / $totalAnswers * 100, 1) : 0,
            'low' => $totalAnswers > 0 ? round(($distribution['low'] ?? 0) / $totalAnswers * 100, 1) : 0,
            'total_answers' => (int) $totalAnswers
        ];
    }

    /**
     * Calculate average risk score for vulnerabilities.
     */
    private function getAverageRiskScore($query): float
    {
        return round((float) ($query->avg('risk_score') ?? 0), 1);
    }

    /**
     * Calculate average risk score for audits.
     */
    private function getAuditAverageRiskScore($query): float
    {
        $avg = $query->select(DB::raw("
            AVG(CASE 
                WHEN system_overall_risk = 'high' THEN 3 
                WHEN system_overall_risk = 'medium' THEN 2 
                WHEN system_overall_risk = 'low' THEN 1 
                ELSE 0
            END) as avg_score
        "))->first()->avg_score;

        return round((float) ($avg ?? 0), 1);
    }

    /**
     * Get status distribution for vulnerabilities.
     */
    private function getStatusDistribution($query): array
    {
        $distribution = $query->groupBy('status')
            ->select('status', DB::raw('count(*) as count'))
            ->pluck('count', 'status')
            ->toArray();

        return [
            'open' => (int) ($distribution['open'] ?? 0),
            'in_progress' => (int) ($distribution['in_progress'] ?? 0),
            'resolved' => (int) ($distribution['resolved'] ?? 0),
            'closed' => (int) ($distribution['closed'] ?? 0)
        ];
    }

    /**
     * Get assignment statistics for vulnerabilities.
     */
    private function getAssignmentStats($query): array
    {
        $total = $query->count();
        $assigned = $query->whereNotNull('assigned_to')->count();
        $unassigned = $total - $assigned;

        return [
            'assigned' => (int) $assigned,
            'unassigned' => (int) $unassigned,
            'assignmentRate' => $total > 0 ? round(($assigned / $total) * 100, 1) : 0
        ];
    }

    /**
     * Get vulnerability severity distribution.
     */
    private function getVulnerabilitySeverityDistribution($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [
                'low' => 0,
                'medium' => 0,
                'high' => 0
            ];
        }

        $distribution = Vulnerability::whereIn('vulnerability_submission_id', $submissionIds)
            ->groupBy('severity')
            ->select('severity', DB::raw('count(*) as count'))
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'low' => (int) ($distribution['low'] ?? 0),
            'medium' => (int) ($distribution['medium'] ?? 0),
            'high' => (int) ($distribution['high'] ?? 0)
        ];
    }


    /**
     * Get common vulnerabilities.
     */
    private function getCommonVulnerabilities($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [];
        }

        return Vulnerability::whereIn('vulnerability_submission_id', $submissionIds)
            ->groupBy('category')
            ->select(
                'category',
                DB::raw('count(*) as total_count'),
                DB::raw('COUNT(CASE WHEN is_resolved = 1 THEN 1 END) as resolved_count')
            )
            ->orderByDesc('total_count')
            ->limit(5)
            ->get()
            ->map(function($vuln) {
                return [
                    'category' => (string) $vuln->category,
                    'count' => (int) $vuln->total_count,
                    'resolvedCount' => (int) $vuln->resolved_count,
                    'resolutionRate' => round(($vuln->resolved_count / $vuln->total_count) * 100, 1)
                ];
            })->toArray();
    }

    /**
     * Get common high-risk audit questions.
     */
    private function getCommonHighRiskAudits($query): array
    {
        $submissionIds = $query->where('system_overall_risk', 'high')->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [];
        }

        return DB::table('audit_answers')
            ->join('audit_questions', 'audit_answers.audit_question_id', '=', 'audit_questions.id')
            ->whereIn('audit_answers.audit_submission_id', $submissionIds)
            ->where('audit_answers.system_risk_level', 'high')
            ->groupBy('audit_questions.question')
            ->select(
                'audit_questions.question',
                DB::raw('count(*) as count')
            )
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'question' => (string) $item->question,
                    'count' => (int) $item->count
                ];
            })->toArray();
    }

    /**
     * Get vulnerability resolution statistics.
     */
    private function getVulnerabilityResolutionStats($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [
                'total_vulnerabilities' => 0,
                'resolved_vulnerabilities' => 0,
                'unresolved_vulnerabilities' => 0,
                'resolution_rate' => 0,
                'avg_resolution_time_hours' => 0
            ];
        }

        $totalVulns = Vulnerability::whereIn('vulnerability_submission_id', $submissionIds)->count();
        $resolvedVulns = Vulnerability::whereIn('vulnerability_submission_id', $submissionIds)
            ->where('is_resolved', true)
            ->count();
        $unresolvedVulns = $totalVulns - $resolvedVulns;

        // Calculate average resolution time
        $avgResolutionTime = Vulnerability::whereIn('vulnerability_submission_id', $submissionIds)
            ->where('is_resolved', true)
            ->whereNotNull('resolved_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours'))
            ->first()
            ->avg_hours ?? 0;

        return [
            'total_vulnerabilities' => (int) $totalVulns,
            'resolved_vulnerabilities' => (int) $resolvedVulns,
            'unresolved_vulnerabilities' => (int) $unresolvedVulns,
            'resolution_rate' => $totalVulns > 0 ? round(($resolvedVulns / $totalVulns) * 100, 1) : 0,
            'avg_resolution_time_hours' => round((float) $avgResolutionTime, 1)
        ];
    }

    /**
     * Get vulnerability source breakdown (manual vs audit-generated).
     */
    private function getVulnerabilitySourceBreakdown($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [
                'manual_submissions' => 0,
                'audit_generated_submissions' => 0,
                'total_submissions' => 0
            ];
        }

        // Count manual submissions (those not generated from audits)
        $manualSubmissions = $query->whereDoesntHave('user.auditSubmissions', function($q) {
            $q->where('status', 'completed');
        })->count();

        // Count audit-generated submissions (those with titles starting with "High Risk Audit")
        $auditGeneratedSubmissions = $query->where('title', 'LIKE', 'High Risk Audit - %')->count();

        return [
            'manual_submissions' => (int) $manualSubmissions,
            'audit_generated_submissions' => (int) $auditGeneratedSubmissions,
            'total_submissions' => (int) $submissionIds->count()
        ];
    }

    /**
     * Get audit review statistics.
     */
    private function getAuditReviewStats($query): array
    {
        $totalSubmissions = $query->count();
        $pendingReview = $query->clone()->where('status', 'submitted')->count();
        $underReview = $query->clone()->where('status', 'under_review')->count();
        $completed = $query->clone()->where('status', 'completed')->count();

        // Calculate average review time for completed audits
        $avgReviewTime = $query->clone()->where('status', 'completed')
            ->whereNotNull('reviewed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, reviewed_at)) as avg_hours'))
            ->first()
            ->avg_hours ?? 0;

        return [
            'total_submissions' => (int) $totalSubmissions,
            'pending_review' => (int) $pendingReview,
            'under_review' => (int) $underReview,
            'completed' => (int) $completed,
            'completion_rate' => $totalSubmissions > 0 ? round(($completed / $totalSubmissions) * 100, 1) : 0,
            'avg_review_time_hours' => round((float) $avgReviewTime, 1)
        ];
    }

    /**
     * Get question category statistics.
     */
    private function getQuestionCategoryStats($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [];
        }

        return AuditAnswer::whereIn('audit_submission_id', $submissionIds)
            ->join('audit_questions', 'audit_answers.audit_question_id', '=', 'audit_questions.id')
            ->groupBy('audit_questions.category')
            ->select(
                'audit_questions.category',
                DB::raw('count(*) as total_answers'),
                DB::raw('sum(case when COALESCE(audit_answers.admin_risk_level, audit_answers.system_risk_level) = "high" then 1 else 0 end) as high_risk_count'),
                DB::raw('sum(case when COALESCE(audit_answers.admin_risk_level, audit_answers.system_risk_level) = "medium" then 1 else 0 end) as medium_risk_count'),
                DB::raw('sum(case when COALESCE(audit_answers.admin_risk_level, audit_answers.system_risk_level) = "low" then 1 else 0 end) as low_risk_count')
            )
            ->orderByDesc('total_answers')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $total = $item->total_answers;
                return [
                    'category' => (string) $item->category,
                    'total_answers' => (int) $total,
                    'high_risk_count' => (int) $item->high_risk_count,
                    'medium_risk_count' => (int) $item->medium_risk_count,
                    'low_risk_count' => (int) $item->low_risk_count,
                    'high_risk_percentage' => $total > 0 ? round(($item->high_risk_count / $total) * 100, 1) : 0
                ];
            })->toArray();
    }

    /**
     * Get admin vs system risk comparison.
     */
    private function getAdminVsSystemRiskComparison($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [
                'system_risk_distribution' => ['high' => 0, 'medium' => 0, 'low' => 0],
                'admin_risk_distribution' => ['high' => 0, 'medium' => 0, 'low' => 0],
                'admin_overrides' => 0,
                'total_submissions' => 0
            ];
        }

        // System risk distribution
        $systemRisk = AuditSubmission::whereIn('id', $submissionIds)
            ->whereNotNull('system_overall_risk')
            ->groupBy('system_overall_risk')
            ->select('system_overall_risk as risk', DB::raw('count(*) as count'))
            ->pluck('count', 'risk')
            ->toArray();

        // Admin risk distribution
        $adminRisk = AuditSubmission::whereIn('id', $submissionIds)
            ->whereNotNull('admin_overall_risk')
            ->groupBy('admin_overall_risk')
            ->select('admin_overall_risk as risk', DB::raw('count(*) as count'))
            ->pluck('count', 'risk')
            ->toArray();

        // Count admin overrides
        $adminOverrides = AuditSubmission::whereIn('id', $submissionIds)
            ->whereNotNull('admin_overall_risk')
            ->whereNotNull('system_overall_risk')
            ->whereColumn('admin_overall_risk', '!=', 'system_overall_risk')
            ->count();

        return [
            'system_risk_distribution' => [
                'high' => (int) ($systemRisk['high'] ?? 0),
                'medium' => (int) ($systemRisk['medium'] ?? 0),
                'low' => (int) ($systemRisk['low'] ?? 0)
            ],
            'admin_risk_distribution' => [
                'high' => (int) ($adminRisk['high'] ?? 0),
                'medium' => (int) ($adminRisk['medium'] ?? 0),
                'low' => (int) ($adminRisk['low'] ?? 0)
            ],
            'admin_overrides' => (int) $adminOverrides,
            'total_submissions' => (int) $submissionIds->count()
        ];
    }

    /**
     * Get audit-to-vulnerability conversion statistics.
     */
    private function getAuditToVulnerabilityConversion($query): array
    {
        $submissionIds = $query->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [
                'total_audits' => 0,
                'audits_with_vulnerabilities' => 0,
                'conversion_rate' => 0,
                'total_vulnerabilities_created' => 0
            ];
        }

        $totalAudits = $submissionIds->count();
        
        // Count audits that have generated vulnerabilities
        $auditsWithVulns = VulnerabilitySubmission::whereIn('user_id', function($q) use ($submissionIds) {
            $q->select('user_id')
              ->from('audit_submissions')
              ->whereIn('id', $submissionIds);
        })
        ->where('title', 'LIKE', 'High Risk Audit - %')
        ->count();

        // Count total vulnerabilities created from these audits
        $totalVulnsCreated = VulnerabilitySubmission::whereIn('user_id', function($q) use ($submissionIds) {
            $q->select('user_id')
              ->from('audit_submissions')
              ->whereIn('id', $submissionIds);
        })
        ->where('title', 'LIKE', 'High Risk Audit - %')
        ->withCount('vulnerabilities')
        ->get()
        ->sum('vulnerabilities_count');

        return [
            'total_audits' => (int) $totalAudits,
            'audits_with_vulnerabilities' => (int) $auditsWithVulns,
            'conversion_rate' => $totalAudits > 0 ? round(($auditsWithVulns / $totalAudits) * 100, 1) : 0,
            'total_vulnerabilities_created' => (int) $totalVulnsCreated
        ];
    }

    /**
     * Get analytics breakdown by questionnaire set.
     */
    private function getQuestionnaireSetBreakdown(Carbon $startDate, ?int $userId = null): array
    {
        $query = AuditSubmission::query()
            ->where('created_at', '>=', $startDate)
            ->with('questionnaireSet:id,name')
            ->select('questionnaire_set_id', DB::raw('count(*) as submission_count'), 
                     DB::raw("ROUND(AVG(CASE WHEN COALESCE(admin_overall_risk, system_overall_risk) = 'high' THEN 1 ELSE 0 END) * 100, 2) as high_risk_percentage"))
            ->groupBy('questionnaire_set_id');
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get()->map(function($item) {
            return [
                'set_id' => $item->questionnaire_set_id,
                'set_name' => $item->questionnaireSet->name ?? 'Unknown Set',
                'submission_count' => (int) $item->submission_count,
                'high_risk_percentage' => (float) $item->high_risk_percentage,
            ];
        })->toArray();
    }

    /**
     * Get questionnaire set performance metrics.
     * Shows: high-risk submissions, completion rates, avg review time, difficulty levels
     */
    private function getQuestionnaireSetPerformanceMetrics(Carbon $startDate, ?int $userId = null): array
    {
        $query = AuditSubmission::query()
            ->where('created_at', '>=', $startDate)
            ->with('questionnaireSet:id,name,status');
        
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $sets = $query->get()->groupBy('questionnaire_set_id');

        return $sets->map(function($submissions) {
            $total = $submissions->count();
            $completed = $submissions->where('status', 'completed')->count();
            $highRisk = $submissions->where('system_overall_risk', 'high')->count();
            $mediumRisk = $submissions->where('system_overall_risk', 'medium')->count();

            // Calculate average review time in hours for completed submissions
            $avgReviewTime = 0;
            $completedSubmissions = $submissions->where('status', 'completed')->filter(fn($s) => $s->reviewed_at);
            if ($completedSubmissions->count() > 0) {
                $totalTime = $completedSubmissions->sum(function($submission) {
                    return $submission->reviewed_at->diffInHours($submission->created_at);
                });
                $avgReviewTime = round($totalTime / $completedSubmissions->count(), 2);
            }

            // Set difficulty level based on high-risk percentage
            $highRiskPercentage = $total > 0 ? round(($highRisk / $total) * 100, 2) : 0;
            $difficulty = $highRiskPercentage >= 60 ? 'high' : ($highRiskPercentage >= 30 ? 'medium' : 'low');

            $setName = $submissions->first()?->questionnaireSet?->name ?? 'Unknown Set';

            return [
                'set_name' => $setName,
                'total_submissions' => $total,
                'completed_submissions' => $completed,
                'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                'high_risk_count' => $highRisk,
                'medium_risk_count' => $mediumRisk,
                'high_risk_percentage' => $highRiskPercentage,
                'average_review_time_hours' => $avgReviewTime,
                'difficulty_level' => $difficulty,
                'risk_distribution' => [
                    'high' => $highRisk,
                    'medium' => $mediumRisk,
                    'low' => $total - $highRisk - $mediumRisk,
                ]
            ];
        })->values()->toArray();
    }

    /**
     * Get question effectiveness analysis.
     * Shows: high-risk questions per set, vulnerability triggers, answer distributions, problematic questions
     */
    private function getQuestionEffectivenessAnalysis(Carbon $startDate, ?int $userId = null): array
    {
        $submissionQuery = AuditSubmission::query()
            ->where('created_at', '>=', $startDate);
        
        if ($userId) {
            $submissionQuery->where('user_id', $userId);
        }

        $submissionIds = $submissionQuery->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [
                'high_risk_questions' => [],
                'problematic_questions' => [],
                'question_analysis_by_set' => []
            ];
        }

        // Get high-risk questions with their frequencies
        $highRiskQuestions = AuditAnswer::whereIn('audit_submission_id', $submissionIds)
            ->where(function($q) {
                $q->where('admin_risk_level', 'high')
                  ->orWhere('system_risk_level', 'high');
            })
            ->with('question:id,question,category,questionnaire_set_id')
            ->select('audit_question_id', DB::raw('count(*) as high_risk_count'))
            ->groupBy('audit_question_id')
            ->orderByDesc('high_risk_count')
            ->limit(10)
            ->get();

        // Get questions that frequently trigger "Others" (custom answers) - indicates ambiguity
        $problematicAnswers = AuditAnswer::whereIn('audit_submission_id', $submissionIds)
            ->where('is_custom_answer', true)
            ->with('question:id,question,category,questionnaire_set_id')
            ->select('audit_question_id', DB::raw('count(*) as custom_answer_count'))
            ->groupBy('audit_question_id')
            ->orderByDesc('custom_answer_count')
            ->limit(10)
            ->get();

        return [
            'high_risk_questions' => $highRiskQuestions->map(function($q) {
                return [
                    'question_id' => $q->audit_question_id,
                    'question_text' => $q->question?->question ?? 'Unknown',
                    'category' => $q->question?->category ?? 'Unknown',
                    'high_risk_count' => (int) $q->high_risk_count,
                    'questionnaire_set_id' => $q->question?->questionnaire_set_id,
                ];
            })->toArray(),
            'problematic_questions' => $problematicAnswers->map(function($q) {
                return [
                    'question_id' => $q->audit_question_id,
                    'question_text' => $q->question?->question ?? 'Unknown',
                    'category' => $q->question?->category ?? 'Unknown',
                    'custom_answer_count' => (int) $q->custom_answer_count,
                    'questionnaire_set_id' => $q->question?->questionnaire_set_id,
                    'issue' => 'Question ambiguity - users providing custom answers instead of predefined options'
                ];
            })->toArray(),
            'question_analysis_by_set' => []
        ];
    }

    /**
     * Get set-to-risk correlation analysis.
     * Shows: risk distribution comparison across sets, heatmap data, admin overrides by set
     */
    private function getSetToRiskCorrelation(Carbon $startDate, ?int $userId = null): array
    {
        $submissionQuery = AuditSubmission::query()
            ->where('created_at', '>=', $startDate)
            ->with('questionnaireSet:id,name');
        
        if ($userId) {
            $submissionQuery->where('user_id', $userId);
        }

        $submissions = $submissionQuery->get();

        if ($submissions->isEmpty()) {
            return [
                'risk_heatmap' => [],
                'set_risk_comparison' => [],
                'admin_override_analysis' => []
            ];
        }

        // Build risk heatmap: set vs risk level
        $riskHeatmap = $submissions->groupBy('questionnaire_set_id')->map(function($setSubmissions) {
            $setName = $setSubmissions->first()?->questionnaireSet?->name ?? 'Unknown Set';
            $total = $setSubmissions->count();

            $highCount = $setSubmissions->where('system_overall_risk', 'high')->count();
            $mediumCount = $setSubmissions->where('system_overall_risk', 'medium')->count();
            $lowCount = $setSubmissions->where('system_overall_risk', 'low')->count();
            $pendingCount = $total - $highCount - $mediumCount - $lowCount;

            return [
                'set_name' => $setName,
                'total' => $total,
                'high' => ['count' => $highCount, 'percentage' => $total > 0 ? round(($highCount / $total) * 100, 1) : 0],
                'medium' => ['count' => $mediumCount, 'percentage' => $total > 0 ? round(($mediumCount / $total) * 100, 1) : 0],
                'low' => ['count' => $lowCount, 'percentage' => $total > 0 ? round(($lowCount / $total) * 100, 1) : 0],
                'pending' => ['count' => $pendingCount, 'percentage' => $total > 0 ? round(($pendingCount / $total) * 100, 1) : 0],
            ];
        })->values()->toArray();

        // Admin override analysis by set
        $adminOverrideAnalysis = $submissions->groupBy('questionnaire_set_id')->map(function($setSubmissions) {
            $setName = $setSubmissions->first()?->questionnaireSet?->name ?? 'Unknown Set';
            
            // Count overrides: where admin_overall_risk differs from system_overall_risk
            $overrideCount = $setSubmissions->filter(function($submission) {
                return $submission->admin_overall_risk && 
                       $submission->admin_overall_risk !== ($submission->system_overall_risk ?? 'pending');
            })->count();

            $total = $setSubmissions->count();

            return [
                'set_name' => $setName,
                'total_submissions' => $total,
                'override_count' => $overrideCount,
                'override_percentage' => $total > 0 ? round(($overrideCount / $total) * 100, 1) : 0,
            ];
        })->values()->toArray();

        return [
            'risk_heatmap' => $riskHeatmap,
            'set_risk_comparison' => $this->sortRiskHeatmapByHighRisk($riskHeatmap),
            'admin_override_analysis' => $adminOverrideAnalysis,
            'highest_risk_set' => $riskHeatmap ? $riskHeatmap[0]['set_name'] ?? null : null,
        ];
    }

    /**
     * Helper method to sort risk heatmap by high-risk percentage.
     */
    private function sortRiskHeatmapByHighRisk(array $heatmap): array
    {
        usort($heatmap, function($a, $b) {
            return $b['high']['percentage'] <=> $a['high']['percentage'];
        });
        return $heatmap;
    }
}
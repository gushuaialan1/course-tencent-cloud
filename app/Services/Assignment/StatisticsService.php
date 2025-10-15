<?php
/**
 * 作业统计服务
 * 
 * 提供作业相关的各种统计数据
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Services\Service;

class StatisticsService extends Service
{
    /**
     * 获取作业统计信息
     * 
     * @param int $assignmentId
     * @return array
     */
    public function getAssignmentStats(int $assignmentId): array
    {
        // 查询所有提交记录
        $submissions = SubmissionModel::find([
            'conditions' => 'assignment_id = :id: AND delete_time = 0',
            'bind' => ['id' => $assignmentId]
        ]);

        $stats = [
            'total_submissions' => 0,
            'draft_count' => 0,
            'submitted_count' => 0,
            'grading_count' => 0,
            'graded_count' => 0,
            'returned_count' => 0,
            'average_score' => 0,
            'highest_score' => 0,
            'lowest_score' => null,
            'on_time_count' => 0,
            'late_count' => 0,
            'pass_count' => 0,
            'pass_rate' => 0
        ];

        if (!$submissions || count($submissions) === 0) {
            return $stats;
        }

        $stats['total_submissions'] = count($submissions);
        $gradedScores = [];
        $passThreshold = 60; // 及格线

        foreach ($submissions as $submission) {
            // 按状态统计
            switch ($submission->status) {
                case SubmissionModel::STATUS_DRAFT:
                    $stats['draft_count']++;
                    break;
                case SubmissionModel::STATUS_SUBMITTED:
                    $stats['submitted_count']++;
                    break;
                case SubmissionModel::STATUS_AUTO_GRADED:
                case SubmissionModel::STATUS_GRADING:
                    $stats['grading_count']++;
                    break;
                case SubmissionModel::STATUS_GRADED:
                    $stats['graded_count']++;
                    break;
                case SubmissionModel::STATUS_RETURNED:
                    $stats['returned_count']++;
                    break;
            }

            // 统计迟交
            if ($submission->is_late) {
                $stats['late_count']++;
            } else if ($submission->status !== SubmissionModel::STATUS_DRAFT) {
                $stats['on_time_count']++;
            }

            // 统计分数（只统计已批改的）
            if (in_array($submission->status, [
                SubmissionModel::STATUS_AUTO_GRADED,
                SubmissionModel::STATUS_GRADED
            ]) && $submission->score !== null) {
                $gradedScores[] = $submission->score;
                
                // 统计及格人数
                $percentage = $submission->getScorePercentage();
                if ($percentage >= $passThreshold) {
                    $stats['pass_count']++;
                }
            }
        }

        // 计算分数统计
        if (!empty($gradedScores)) {
            $stats['average_score'] = round(array_sum($gradedScores) / count($gradedScores), 2);
            $stats['highest_score'] = max($gradedScores);
            $stats['lowest_score'] = min($gradedScores);
            $stats['pass_rate'] = round(($stats['pass_count'] / count($gradedScores)) * 100, 2);
        }

        return $stats;
    }

    /**
     * 获取成绩分布
     * 
     * @param int $assignmentId
     * @return array
     */
    public function getGradeDistribution(int $assignmentId): array
    {
        // 查询已批改的提交记录
        $submissions = SubmissionModel::find([
            'conditions' => 'assignment_id = :id: AND status IN ({statuses:array}) AND delete_time = 0',
            'bind' => [
                'id' => $assignmentId,
                'statuses' => [SubmissionModel::STATUS_AUTO_GRADED, SubmissionModel::STATUS_GRADED]
            ]
        ]);

        $distribution = [
            '0-59' => 0,    // 不及格
            '60-69' => 0,   // 及格
            '70-79' => 0,   // 中等
            '80-89' => 0,   // 良好
            '90-100' => 0   // 优秀
        ];

        if (!$submissions || count($submissions) === 0) {
            return $distribution;
        }

        foreach ($submissions as $submission) {
            $percentage = $submission->getScorePercentage();
            
            if ($percentage < 60) {
                $distribution['0-59']++;
            } elseif ($percentage < 70) {
                $distribution['60-69']++;
            } elseif ($percentage < 80) {
                $distribution['70-79']++;
            } elseif ($percentage < 90) {
                $distribution['80-89']++;
            } else {
                $distribution['90-100']++;
            }
        }

        return $distribution;
    }

    /**
     * 获取学生的作业完成进度
     * 
     * @param int $userId
     * @param int $courseId
     * @return array
     */
    public function getUserProgress(int $userId, int $courseId): array
    {
        // 获取课程的所有已发布作业
        $assignments = AssignmentModel::find([
            'conditions' => 'course_id = :course_id: AND status = :status: AND delete_time = 0',
            'bind' => [
                'course_id' => $courseId,
                'status' => AssignmentModel::STATUS_PUBLISHED
            ]
        ]);

        $stats = [
            'total_assignments' => count($assignments),
            'completed_count' => 0,
            'pending_count' => 0,
            'average_score' => 0,
            'total_score' => 0,
            'max_possible_score' => 0
        ];

        if (count($assignments) === 0) {
            return $stats;
        }

        $scores = [];

        foreach ($assignments as $assignment) {
            $stats['max_possible_score'] += $assignment->max_score;

            // 查询学生的提交记录
            $submission = SubmissionModel::findFirst([
                'conditions' => 'assignment_id = :assignment_id: AND user_id = :user_id: AND delete_time = 0',
                'bind' => [
                    'assignment_id' => $assignment->id,
                    'user_id' => $userId
                ]
            ]);

            if ($submission && in_array($submission->status, [
                SubmissionModel::STATUS_AUTO_GRADED,
                SubmissionModel::STATUS_GRADED
            ])) {
                $stats['completed_count']++;
                if ($submission->score !== null) {
                    $scores[] = $submission->score;
                    $stats['total_score'] += $submission->score;
                }
            } else {
                $stats['pending_count']++;
            }
        }

        // 计算平均分
        if (!empty($scores)) {
            $stats['average_score'] = round(array_sum($scores) / count($scores), 2);
        }

        return $stats;
    }

    /**
     * 获取课程的作业完成率
     * 
     * @param int $courseId
     * @return array
     */
    public function getCourseCompletionRate(int $courseId): array
    {
        // 获取课程的所有已发布作业
        $assignments = AssignmentModel::find([
            'conditions' => 'course_id = :course_id: AND status = :status: AND delete_time = 0',
            'bind' => [
                'course_id' => $courseId,
                'status' => AssignmentModel::STATUS_PUBLISHED
            ]
        ]);

        $stats = [
            'total_assignments' => count($assignments),
            'total_submissions' => 0,
            'total_graded' => 0,
            'completion_rate' => 0
        ];

        if (count($assignments) === 0) {
            return $stats;
        }

        foreach ($assignments as $assignment) {
            $submissions = SubmissionModel::count([
                'conditions' => 'assignment_id = :id: AND delete_time = 0',
                'bind' => ['id' => $assignment->id]
            ]);
            $stats['total_submissions'] += $submissions;

            $graded = SubmissionModel::count([
                'conditions' => 'assignment_id = :id: AND status IN ({statuses:array}) AND delete_time = 0',
                'bind' => [
                    'id' => $assignment->id,
                    'statuses' => [SubmissionModel::STATUS_AUTO_GRADED, SubmissionModel::STATUS_GRADED]
                ]
            ]);
            $stats['total_graded'] += $graded;
        }

        if ($stats['total_submissions'] > 0) {
            $stats['completion_rate'] = round(($stats['total_graded'] / $stats['total_submissions']) * 100, 2);
        }

        return $stats;
    }
}


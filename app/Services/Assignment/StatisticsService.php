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
        // 获取作业信息
        $assignment = AssignmentModel::findFirst($assignmentId);
        if (!$assignment) {
            return [
                'total' => 0,
                'submitted' => 0,
                'graded' => 0,
                'pending' => 0,
                'avg_score' => 0
            ];
        }
        
        // 获取选课学生总数
        $totalStudents = 0;
        if ($assignment->course_id) {
            $totalStudents = \App\Models\CourseUser::count([
                'conditions' => 'course_id = :course_id: AND deleted = 0',
                'bind' => ['course_id' => $assignment->course_id]
            ]);
        }
        
        // 查询所有提交记录（不包括草稿）
        $submissions = SubmissionModel::find([
            'conditions' => 'assignment_id = :id: AND status != :draft: AND delete_time = 0',
            'bind' => [
                'id' => $assignmentId,
                'draft' => SubmissionModel::STATUS_DRAFT
            ]
        ]);

        $submittedCount = 0;
        $gradedCount = 0;
        $pendingCount = 0;
        $gradedScores = [];

        foreach ($submissions as $submission) {
            // 统计已提交（包括所有非草稿状态）
            $submittedCount++;
            
            // 按状态统计
            if (in_array($submission->status, [
                SubmissionModel::STATUS_AUTO_GRADED,
                SubmissionModel::STATUS_GRADED
            ])) {
                $gradedCount++;
                if ($submission->score !== null) {
                    $gradedScores[] = $submission->score;
                }
            } elseif (in_array($submission->status, [
                SubmissionModel::STATUS_SUBMITTED,
                SubmissionModel::STATUS_GRADING
            ])) {
                $pendingCount++;
            }
        }

        // 计算平均分
        $avgScore = 0;
        if (!empty($gradedScores)) {
            $avgScore = round(array_sum($gradedScores) / count($gradedScores), 1);
        }

        return [
            'total' => $totalStudents,
            'submitted' => $submittedCount,
            'graded' => $gradedCount,
            'pending' => $pendingCount,
            'avg_score' => $avgScore
        ];
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


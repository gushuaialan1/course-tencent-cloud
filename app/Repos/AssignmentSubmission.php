<?php
/**
 * 作业提交仓储类
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Repos;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as AssignmentSubmissionModel;
use Phalcon\Mvc\Model\Query\Builder;

class AssignmentSubmission extends Repository
{
    /**
     * 根据ID查找提交记录
     *
     * @param int $id
     * @return AssignmentSubmissionModel|null
     */
    public function findById(int $id): ?AssignmentSubmissionModel
    {
        return AssignmentSubmissionModel::findFirst([
            'conditions' => 'id = :id: AND delete_time = 0',
            'bind' => ['id' => $id]
        ]);
    }

    /**
     * 根据作业ID和用户ID查找提交记录
     *
     * @param int $assignmentId
     * @param int $userId
     * @return AssignmentSubmissionModel|null
     */
    public function findByAssignmentAndUser(int $assignmentId, int $userId): ?AssignmentSubmissionModel
    {
        return AssignmentSubmissionModel::findFirst([
            'conditions' => 'assignment_id = :assignment_id: AND user_id = :user_id: AND delete_time = 0',
            'bind' => [
                'assignment_id' => $assignmentId,
                'user_id' => $userId
            ]
        ]);
    }

    /**
     * 根据作业ID查找所有提交记录
     *
     * @param int $assignmentId
     * @param array $options
     * @return array
     */
    public function findByAssignmentId(int $assignmentId, array $options = []): array
    {
        $conditions = ['assignment_id = :assignment_id:', 'delete_time = 0'];
        $bind = ['assignment_id' => $assignmentId];

        // 状态过滤
        if (!empty($options['status'])) {
            $conditions[] = 'status = :status:';
            $bind['status'] = $options['status'];
        }

        // 评分状态过滤
        if (!empty($options['grade_status'])) {
            $conditions[] = 'grade_status = :grade_status:';
            $bind['grade_status'] = $options['grade_status'];
        }

        // 批改老师过滤
        if (!empty($options['grader_id'])) {
            $conditions[] = 'grader_id = :grader_id:';
            $bind['grader_id'] = $options['grader_id'];
        }

        // 迟交过滤
        if (isset($options['is_late'])) {
            $conditions[] = 'is_late = :is_late:';
            $bind['is_late'] = $options['is_late'];
        }

        $queryOptions = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'submit_time DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $queryOptions['offset'] = $options['offset'];
            }
        }

        return AssignmentSubmissionModel::find($queryOptions)->toArray();
    }

    /**
     * 根据用户ID查找提交记录
     *
     * @param int $userId
     * @param array $options
     * @return array
     */
    public function findByUserId(int $userId, array $options = []): array
    {
        $conditions = ['user_id = :user_id:', 'delete_time = 0'];
        $bind = ['user_id' => $userId];

        // 课程过滤
        if (!empty($options['course_id'])) {
            $conditions[] = 'assignment_id IN (SELECT id FROM kg_assignment WHERE course_id = :course_id: AND delete_time = 0)';
            $bind['course_id'] = $options['course_id'];
        }

        // 状态过滤
        if (!empty($options['status'])) {
            $conditions[] = 'status = :status:';
            $bind['status'] = $options['status'];
        }

        $queryOptions = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'create_time DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $queryOptions['offset'] = $options['offset'];
            }
        }

        return AssignmentSubmissionModel::find($queryOptions)->toArray();
    }

    /**
     * 创建提交记录
     *
     * @param array $data
     * @return AssignmentSubmissionModel
     */
    public function create(array $data): AssignmentSubmissionModel
    {
        $submission = new AssignmentSubmissionModel();
        
        $submission->assignment_id = $data['assignment_id'] ?? 0;
        $submission->user_id = $data['user_id'] ?? 0;
        $submission->max_score = $data['max_score'] ?? 100.00;
        $submission->status = $data['status'] ?? AssignmentSubmissionModel::STATUS_DRAFT;
        $submission->attempt_count = $data['attempt_count'] ?? 1;

        // JSON字段
        if (!empty($data['content'])) {
            $submission->setContentData($data['content']);
        }
        if (!empty($data['attachments'])) {
            $submission->setAttachmentsData($data['attachments']);
        }

        $submission->save();
        
        return $submission;
    }

    /**
     * 更新提交记录
     *
     * @param AssignmentSubmissionModel $submission
     * @param array $data
     * @return bool
     */
    public function update(AssignmentSubmissionModel $submission, array $data): bool
    {
        // 基本信息
        if (isset($data['score'])) {
            $submission->score = $data['score'];
        }
        if (isset($data['feedback'])) {
            $submission->feedback = $data['feedback'];
        }
        if (isset($data['grader_id'])) {
            $submission->grader_id = $data['grader_id'];
        }
        if (isset($data['status'])) {
            $submission->status = $data['status'];
        }
        if (isset($data['grade_status'])) {
            $submission->grade_status = $data['grade_status'];
        }
        if (isset($data['attempt_count'])) {
            $submission->attempt_count = $data['attempt_count'];
        }
        if (isset($data['duration'])) {
            $submission->duration = $data['duration'];
        }

        // JSON字段
        if (isset($data['content'])) {
            $submission->setContentData($data['content']);
        }
        if (isset($data['attachments'])) {
            $submission->setAttachmentsData($data['attachments']);
        }
        if (isset($data['grade_details'])) {
            $submission->setGradeDetailsData($data['grade_details']);
        }

        return $submission->save();
    }

    /**
     * 删除提交记录(软删除)
     *
     * @param AssignmentSubmissionModel $submission
     * @return bool
     */
    public function delete(AssignmentSubmissionModel $submission): bool
    {
        return $submission->delete();
    }

    /**
     * 提交作业
     *
     * @param AssignmentSubmissionModel $submission
     * @param array $options
     * @return bool
     */
    public function submit(AssignmentSubmissionModel $submission, array $options = []): bool
    {
        return $submission->submitAssignment($options);
    }

    /**
     * 开始批改
     *
     * @param AssignmentSubmissionModel $submission
     * @param int $graderId
     * @return bool
     */
    public function startGrading(AssignmentSubmissionModel $submission, int $graderId): bool
    {
        return $submission->startGrading($graderId);
    }

    /**
     * 完成批改
     *
     * @param AssignmentSubmissionModel $submission
     * @param float $score
     * @param string $feedback
     * @param array $gradeDetails
     * @return bool
     */
    public function completeGrading(AssignmentSubmissionModel $submission, float $score, string $feedback = '', array $gradeDetails = []): bool
    {
        return $submission->completeGrading($score, $feedback, $gradeDetails);
    }

    /**
     * 退回作业
     *
     * @param AssignmentSubmissionModel $submission
     * @param string $reason
     * @return bool
     */
    public function returnSubmission(AssignmentSubmissionModel $submission, string $reason = ''): bool
    {
        return $submission->returnSubmission($reason);
    }

    /**
     * 获取待批改队列
     *
     * @param array $options
     * @return array
     */
    public function getPendingGrading(array $options = []): array
    {
        $conditions = [
            'status = :status:',
            'grade_status = :grade_status:',
            'delete_time = 0'
        ];
        $bind = [
            'status' => AssignmentSubmissionModel::STATUS_SUBMITTED,
            'grade_status' => AssignmentSubmissionModel::GRADE_STATUS_PENDING
        ];

        // 按课程过滤
        if (!empty($options['course_id'])) {
            $conditions[] = 'assignment_id IN (SELECT id FROM kg_assignment WHERE course_id = :course_id: AND delete_time = 0)';
            $bind['course_id'] = $options['course_id'];
        }

        // 按作业过滤
        if (!empty($options['assignment_id'])) {
            $conditions[] = 'assignment_id = :assignment_id:';
            $bind['assignment_id'] = $options['assignment_id'];
        }

        $queryOptions = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'submit_time ASC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $queryOptions['offset'] = $options['offset'];
            }
        }

        return AssignmentSubmissionModel::find($queryOptions)->toArray();
    }

    /**
     * 获取提交统计数据
     *
     * @param array $options
     * @return array
     */
    public function getStatistics(array $options = []): array
    {
        $builder = new Builder();
        $builder->from(AssignmentSubmissionModel::class)
                ->where('delete_time = 0');

        // 按作业过滤
        if (!empty($options['assignment_id'])) {
            $builder->andWhere('assignment_id = :assignment_id:', ['assignment_id' => $options['assignment_id']]);
        }

        // 按课程过滤
        if (!empty($options['course_id'])) {
            $builder->join(AssignmentModel::class, 'a.id = assignment_id', 'a')
                    ->andWhere('a.course_id = :course_id:', ['course_id' => $options['course_id']]);
        }

        // 按用户过滤
        if (!empty($options['user_id'])) {
            $builder->andWhere('user_id = :user_id:', ['user_id' => $options['user_id']]);
        }

        // 时间范围过滤
        if (!empty($options['start_time'])) {
            $builder->andWhere('submit_time >= :start_time:', ['start_time' => $options['start_time']]);
        }
        if (!empty($options['end_time'])) {
            $builder->andWhere('submit_time <= :end_time:', ['end_time' => $options['end_time']]);
        }

        $total = $builder->getQuery()->execute()->count();

        // 按状态统计
        $statusStats = [];
        foreach (AssignmentSubmissionModel::getStatuses() as $status => $name) {
            $statusBuilder = clone $builder;
            $statusCount = $statusBuilder->andWhere('status = :status:', ['status' => $status])
                                       ->getQuery()->execute()->count();
            $statusStats[$status] = $statusCount;
        }

        // 按评分状态统计
        $gradeStatusStats = [];
        foreach (AssignmentSubmissionModel::getGradeStatuses() as $status => $name) {
            $gradeStatusBuilder = clone $builder;
            $gradeStatusCount = $gradeStatusBuilder->andWhere('grade_status = :grade_status:', ['grade_status' => $status])
                                                  ->getQuery()->execute()->count();
            $gradeStatusStats[$status] = $gradeStatusCount;
        }

        // 迟交统计
        $lateBuilder = clone $builder;
        $lateCount = $lateBuilder->andWhere('is_late = 1')->getQuery()->execute()->count();

        // 平均分统计
        $averageScore = 0;
        $scoreBuilder = clone $builder;
        $scoreBuilder->columns(['AVG(score) as avg_score'])
                     ->andWhere('status = :status:', ['status' => AssignmentSubmissionModel::STATUS_GRADED])
                     ->andWhere('score IS NOT NULL');
        $scoreResult = $scoreBuilder->getQuery()->execute();
        if ($scoreResult->count() > 0) {
            $averageScore = round($scoreResult->getFirst()->avg_score, 2);
        }

        return [
            'total' => $total,
            'by_status' => $statusStats,
            'by_grade_status' => $gradeStatusStats,
            'late_count' => $lateCount,
            'average_score' => $averageScore
        ];
    }

    /**
     * 批量分配批改老师
     *
     * @param array $submissionIds
     * @param int $graderId
     * @return int 影响的行数
     */
    public function batchAssignGrader(array $submissionIds, int $graderId): int
    {
        if (empty($submissionIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($submissionIds), '?'));
        $params = array_merge($submissionIds, [$graderId, AssignmentSubmissionModel::GRADE_STATUS_GRADING, time()]);

        $phql = "UPDATE App\Models\AssignmentSubmission SET grader_id = ?{$count}, grade_status = ?{$count2}, update_time = ?{$count3} 
                 WHERE id IN ({$placeholders}) 
                 AND status = ?{$count4} 
                 AND grade_status = ?{$count5} 
                 AND delete_time = 0";
        
        $count = count($submissionIds) + 1;
        $count2 = $count + 1;
        $count3 = $count2 + 1;
        $count4 = $count3 + 1;
        $count5 = $count4 + 1;

        $params[] = AssignmentSubmissionModel::STATUS_SUBMITTED;
        $params[] = AssignmentSubmissionModel::GRADE_STATUS_PENDING;

        $query = $this->modelsManager->createQuery(
            str_replace(['{$count}', '{$count2}', '{$count3}', '{$count4}', '{$count5}'], 
                       [$count, $count2, $count3, $count4, $count5], $phql)
        );
        $result = $query->execute($params);

        return $result->getRowsUpdated();
    }

    /**
     * 获取用户的作业完成情况
     *
     * @param int $userId
     * @param int $courseId
     * @return array
     */
    public function getUserProgress(int $userId, int $courseId): array
    {
        // 获取课程所有作业
        $assignmentBuilder = new Builder();
        $totalAssignments = $assignmentBuilder->from(AssignmentModel::class)
                                             ->where('course_id = :course_id:', ['course_id' => $courseId])
                                             ->andWhere('status = :status:', ['status' => AssignmentModel::STATUS_PUBLISHED])
                                             ->andWhere('delete_time = 0')
                                             ->getQuery()->execute()->count();

        // 获取用户提交情况
        $submissionBuilder = new Builder();
        $submissionBuilder->from(AssignmentSubmissionModel::class)
                         ->join(AssignmentModel::class, 'a.id = assignment_id', 'a')
                         ->where('user_id = :user_id:', ['user_id' => $userId])
                         ->andWhere('a.course_id = :course_id:', ['course_id' => $courseId])
                         ->andWhere('a.status = :status:', ['status' => AssignmentModel::STATUS_PUBLISHED])
                         ->andWhere('AssignmentSubmission.delete_time = 0')
                         ->andWhere('a.delete_time = 0');

        $totalSubmissions = $submissionBuilder->getQuery()->execute()->count();

        // 已完成的作业(已提交)
        $submittedBuilder = clone $submissionBuilder;
        $submittedCount = $submittedBuilder->andWhere('AssignmentSubmission.status IN (:statuses:)', [
            'statuses' => [AssignmentSubmissionModel::STATUS_SUBMITTED, AssignmentSubmissionModel::STATUS_GRADED]
        ])->getQuery()->execute()->count();

        // 已批改的作业
        $gradedBuilder = clone $submissionBuilder;
        $gradedCount = $gradedBuilder->andWhere('AssignmentSubmission.status = :graded_status:', [
            'graded_status' => AssignmentSubmissionModel::STATUS_GRADED
        ])->getQuery()->execute()->count();

        // 平均分
        $averageScore = 0;
        if ($gradedCount > 0) {
            $scoreBuilder = clone $submissionBuilder;
            $scoreBuilder->columns(['AVG(AssignmentSubmission.score) as avg_score'])
                         ->andWhere('AssignmentSubmission.status = :graded_status:', [
                             'graded_status' => AssignmentSubmissionModel::STATUS_GRADED
                         ])
                         ->andWhere('AssignmentSubmission.score IS NOT NULL');
            $scoreResult = $scoreBuilder->getQuery()->execute();
            if ($scoreResult->count() > 0) {
                $averageScore = round($scoreResult->getFirst()->avg_score, 2);
            }
        }

        return [
            'total_assignments' => $totalAssignments,
            'submitted_count' => $submittedCount,
            'graded_count' => $gradedCount,
            'completion_rate' => $totalAssignments > 0 ? round(($submittedCount / $totalAssignments) * 100, 2) : 0,
            'average_score' => $averageScore
        ];
    }
}

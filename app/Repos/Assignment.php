<?php
/**
 * 作业仓储类
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Repos;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as AssignmentSubmissionModel;
use Phalcon\Mvc\Model\Query\Builder;

class Assignment extends Repository
{
    /**
     * 根据ID查找作业
     *
     * @param int $id
     * @return AssignmentModel|null
     */
    public function findById(int $id): ?AssignmentModel
    {
        return AssignmentModel::findFirst([
            'conditions' => 'id = :id: AND delete_time = 0',
            'bind' => ['id' => $id]
        ]);
    }

    /**
     * 根据课程ID查找作业
     *
     * @param int $courseId
     * @param array $options
     * @return array
     */
    public function findByCourseId(int $courseId, array $options = []): array
    {
        $conditions = ['course_id = :course_id:', 'delete_time = 0'];
        $bind = ['course_id' => $courseId];

        // 作业类型过滤
        if (!empty($options['type'])) {
            $conditions[] = 'assignment_type = :type:';
            $bind['type'] = $options['type'];
        }

        // 状态过滤
        if (!empty($options['status'])) {
            $conditions[] = 'status = :status:';
            $bind['status'] = $options['status'];
        }

        // 章节过滤
        if (!empty($options['chapter_id'])) {
            $conditions[] = 'chapter_id = :chapter_id:';
            $bind['chapter_id'] = $options['chapter_id'];
        }

        // 创建者过滤
        if (!empty($options['owner_id'])) {
            $conditions[] = 'owner_id = :owner_id:';
            $bind['owner_id'] = $options['owner_id'];
        }

        $queryOptions = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'id DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $queryOptions['offset'] = $options['offset'];
            }
        }

        return AssignmentModel::find($queryOptions)->toArray();
    }

    /**
     * 创建作业
     *
     * @param array $data
     * @return AssignmentModel
     */
    public function create(array $data): AssignmentModel
    {
        $assignment = new AssignmentModel();
        
        // 基本信息
        $assignment->title = $data['title'] ?? '';
        $assignment->description = $data['description'] ?? '';
        $assignment->course_id = $data['course_id'] ?? 0;
        $assignment->chapter_id = $data['chapter_id'] ?? 0;
        $assignment->assignment_type = $data['assignment_type'] ?? AssignmentModel::TYPE_MIXED;
        $assignment->max_score = $data['max_score'] ?? 100.00;
        $assignment->due_date = $data['due_date'] ?? 0;
        $assignment->allow_late = $data['allow_late'] ?? 0;
        $assignment->late_penalty = $data['late_penalty'] ?? 0.00;
        $assignment->grade_mode = $data['grade_mode'] ?? AssignmentModel::GRADE_MODE_MANUAL;
        $assignment->instructions = $data['instructions'] ?? '';
        $assignment->max_attempts = $data['max_attempts'] ?? 1;
        $assignment->time_limit = $data['time_limit'] ?? 0;
        $assignment->status = $data['status'] ?? AssignmentModel::STATUS_DRAFT;
        $assignment->publish_time = $data['publish_time'] ?? 0;
        $assignment->owner_id = $data['owner_id'] ?? 0;

        // JSON字段
        if (!empty($data['attachments'])) {
            $assignment->setAttachmentsData($data['attachments']);
        }
        if (!empty($data['rubric'])) {
            $assignment->setRubricData($data['rubric']);
        }
        if (!empty($data['content'])) {
            $assignment->setContentData($data['content']);
        }
        if (!empty($data['reference_answer'])) {
            $assignment->setReferenceAnswerData($data['reference_answer']);
        }
        if (!empty($data['visibility'])) {
            $assignment->setVisibilityData($data['visibility']);
        }

        $assignment->save();
        
        return $assignment;
    }

    /**
     * 更新作业
     *
     * @param AssignmentModel $assignment
     * @param array $data
     * @return bool
     */
    public function update(AssignmentModel $assignment, array $data): bool
    {
        // 基本信息
        if (isset($data['title'])) {
            $assignment->title = $data['title'];
        }
        if (isset($data['description'])) {
            $assignment->description = $data['description'];
        }
        if (isset($data['assignment_type'])) {
            $assignment->assignment_type = $data['assignment_type'];
        }
        if (isset($data['max_score'])) {
            $assignment->max_score = $data['max_score'];
        }
        if (isset($data['due_date'])) {
            $assignment->due_date = $data['due_date'];
        }
        if (isset($data['allow_late'])) {
            $assignment->allow_late = $data['allow_late'];
        }
        if (isset($data['late_penalty'])) {
            $assignment->late_penalty = $data['late_penalty'];
        }
        if (isset($data['grade_mode'])) {
            $assignment->grade_mode = $data['grade_mode'];
        }
        if (isset($data['instructions'])) {
            $assignment->instructions = $data['instructions'];
        }
        if (isset($data['max_attempts'])) {
            $assignment->max_attempts = $data['max_attempts'];
        }
        if (isset($data['time_limit'])) {
            $assignment->time_limit = $data['time_limit'];
        }
        if (isset($data['status'])) {
            $assignment->status = $data['status'];
        }
        if (isset($data['publish_time'])) {
            $assignment->publish_time = $data['publish_time'];
        }

        // JSON字段
        if (isset($data['attachments'])) {
            $assignment->setAttachmentsData($data['attachments']);
        }
        if (isset($data['rubric'])) {
            $assignment->setRubricData($data['rubric']);
        }
        if (isset($data['content'])) {
            $assignment->setContentData($data['content']);
        }
        if (isset($data['reference_answer'])) {
            $assignment->setReferenceAnswerData($data['reference_answer']);
        }
        if (isset($data['visibility'])) {
            $assignment->setVisibilityData($data['visibility']);
        }

        return $assignment->save();
    }

    /**
     * 删除作业(软删除)
     *
     * @param AssignmentModel $assignment
     * @return bool
     */
    public function delete(AssignmentModel $assignment): bool
    {
        return $assignment->delete();
    }

    /**
     * 发布作业
     *
     * @param AssignmentModel $assignment
     * @return bool
     */
    public function publish(AssignmentModel $assignment): bool
    {
        $assignment->status = AssignmentModel::STATUS_PUBLISHED;
        $assignment->publish_time = time();
        
        return $assignment->save();
    }

    /**
     * 关闭作业
     *
     * @param AssignmentModel $assignment
     * @return bool
     */
    public function close(AssignmentModel $assignment): bool
    {
        $assignment->status = AssignmentModel::STATUS_CLOSED;
        
        return $assignment->save();
    }

    /**
     * 归档作业
     *
     * @param AssignmentModel $assignment
     * @return bool
     */
    public function archive(AssignmentModel $assignment): bool
    {
        $assignment->status = AssignmentModel::STATUS_ARCHIVED;
        
        return $assignment->save();
    }

    /**
     * 获取作业统计数据
     *
     * @param array $options
     * @return array
     */
    public function getStatistics(array $options = []): array
    {
        $builder = new Builder();
        $builder->from(AssignmentModel::class)
                ->where('delete_time = 0');

        // 按课程过滤
        if (!empty($options['course_id'])) {
            $builder->andWhere('course_id = :course_id:', ['course_id' => $options['course_id']]);
        }

        // 按创建者过滤
        if (!empty($options['owner_id'])) {
            $builder->andWhere('owner_id = :owner_id:', ['owner_id' => $options['owner_id']]);
        }

        // 时间范围过滤
        if (!empty($options['start_time'])) {
            $builder->andWhere('create_time >= :start_time:', ['start_time' => $options['start_time']]);
        }
        if (!empty($options['end_time'])) {
            $builder->andWhere('create_time <= :end_time:', ['end_time' => $options['end_time']]);
        }

        $total = $builder->getQuery()->execute()->count();

        // 按状态统计
        $statusStats = [];
        foreach (AssignmentModel::getStatuses() as $status => $name) {
            $statusBuilder = clone $builder;
            $statusCount = $statusBuilder->andWhere('status = :status:', ['status' => $status])
                                       ->getQuery()->execute()->count();
            $statusStats[$status] = $statusCount;
        }

        // 按类型统计
        $typeStats = [];
        foreach (AssignmentModel::getTypes() as $type => $name) {
            $typeBuilder = clone $builder;
            $typeCount = $typeBuilder->andWhere('assignment_type = :type:', ['type' => $type])
                                   ->getQuery()->execute()->count();
            $typeStats[$type] = $typeCount;
        }

        return [
            'total' => $total,
            'by_status' => $statusStats,
            'by_type' => $typeStats
        ];
    }

    /**
     * 获取即将到期的作业
     *
     * @param int $hours 多少小时内到期
     * @return array
     */
    public function getUpcomingDue(int $hours = 24): array
    {
        $startTime = time();
        $endTime = time() + ($hours * 3600);

        return AssignmentModel::find([
            'conditions' => 'status = :status: AND due_date BETWEEN :start_time: AND :end_time: AND delete_time = 0',
            'bind' => [
                'status' => AssignmentModel::STATUS_PUBLISHED,
                'start_time' => $startTime,
                'end_time' => $endTime
            ],
            'order' => 'due_date ASC'
        ])->toArray();
    }

    /**
     * 获取已过期的作业
     *
     * @param array $options
     * @return array
     */
    public function getOverdue(array $options = []): array
    {
        $conditions = [
            'status = :status:',
            'due_date > 0',
            'due_date < :current_time:',
            'delete_time = 0'
        ];
        $bind = [
            'status' => AssignmentModel::STATUS_PUBLISHED,
            'current_time' => time()
        ];

        // 按课程过滤
        if (!empty($options['course_id'])) {
            $conditions[] = 'course_id = :course_id:';
            $bind['course_id'] = $options['course_id'];
        }

        $queryOptions = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => 'due_date DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $queryOptions['offset'] = $options['offset'];
            }
        }

        return AssignmentModel::find($queryOptions)->toArray();
    }

    /**
     * 批量操作作业状态
     *
     * @param array $assignmentIds
     * @param string $status
     * @return int 影响的行数
     */
    public function batchUpdateStatus(array $assignmentIds, string $status): int
    {
        if (empty($assignmentIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
        $params = array_merge($assignmentIds, [$status, time()]);

        $phql = "UPDATE App\Models\Assignment SET status = ?{$count}, update_time = ?{$count2} WHERE id IN ({$placeholders}) AND delete_time = 0";
        $count = count($assignmentIds) + 1;
        $count2 = $count + 1;

        $query = $this->modelsManager->createQuery(str_replace(['{$count}', '{$count2}'], [$count, $count2], $phql));
        $result = $query->execute($params);

        return $result->getRowsUpdated();
    }

    /**
     * 复制作业
     *
     * @param AssignmentModel $sourceAssignment
     * @param array $overrideData
     * @return AssignmentModel
     */
    public function duplicate(AssignmentModel $sourceAssignment, array $overrideData = []): AssignmentModel
    {
        $newAssignment = new AssignmentModel();
        
        // 复制基本信息
        $newAssignment->title = ($overrideData['title'] ?? $sourceAssignment->title) . ' (副本)';
        $newAssignment->description = $overrideData['description'] ?? $sourceAssignment->description;
        $newAssignment->course_id = $overrideData['course_id'] ?? $sourceAssignment->course_id;
        $newAssignment->chapter_id = $overrideData['chapter_id'] ?? $sourceAssignment->chapter_id;
        $newAssignment->assignment_type = $overrideData['assignment_type'] ?? $sourceAssignment->assignment_type;
        $newAssignment->max_score = $overrideData['max_score'] ?? $sourceAssignment->max_score;
        $newAssignment->due_date = $overrideData['due_date'] ?? 0; // 新作业默认无截止时间
        $newAssignment->allow_late = $overrideData['allow_late'] ?? $sourceAssignment->allow_late;
        $newAssignment->late_penalty = $overrideData['late_penalty'] ?? $sourceAssignment->late_penalty;
        $newAssignment->grade_mode = $overrideData['grade_mode'] ?? $sourceAssignment->grade_mode;
        $newAssignment->instructions = $overrideData['instructions'] ?? $sourceAssignment->instructions;
        $newAssignment->max_attempts = $overrideData['max_attempts'] ?? $sourceAssignment->max_attempts;
        $newAssignment->time_limit = $overrideData['time_limit'] ?? $sourceAssignment->time_limit;
        $newAssignment->status = $overrideData['status'] ?? AssignmentModel::STATUS_DRAFT;
        $newAssignment->owner_id = $overrideData['owner_id'] ?? $sourceAssignment->owner_id;

        // 复制JSON字段
        $newAssignment->attachments = $sourceAssignment->attachments;
        $newAssignment->rubric = $sourceAssignment->rubric;
        $newAssignment->content = $sourceAssignment->content;
        $newAssignment->reference_answer = $sourceAssignment->reference_answer;
        $newAssignment->visibility = $sourceAssignment->visibility;

        $newAssignment->save();
        
        return $newAssignment;
    }
}

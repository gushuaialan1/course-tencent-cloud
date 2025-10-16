<?php
/**
 * 作业管理服务
 * 
 * 负责作业的创建、更新、删除和查询
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Services\Service;

class AssignmentService extends Service
{
    /**
     * 创建作业
     * 
     * @param array $data 作业数据
     * @return AssignmentModel
     * @throws \Exception
     */
    public function create(array $data): AssignmentModel
    {
        // 验证必填字段
        $this->validateRequired($data, ['title', 'course_id', 'content']);

        $assignment = new AssignmentModel();
        
        // 基本信息
        $assignment->title = $data['title'];
        $assignment->description = $data['description'] ?? '';
        $assignment->course_id = $data['course_id'];
        $assignment->chapter_id = $data['chapter_id'] ?? 0;
        $assignment->owner_id = $data['owner_id'];
        
        // 题目内容（确保格式正确）
        $content = $data['content'];
        if (!isset($content['questions'])) {
            throw new \Exception('作业内容必须包含questions数组');
        }
        $assignment->setContentData($content);
        
        // 计算总分（从题目中计算）
        $totalScore = 0;
        foreach ($content['questions'] as $question) {
            $totalScore += $question['score'] ?? 0;
        }
        $assignment->max_score = $totalScore;
        
        // 作业设置
        $assignment->due_date = $data['due_date'] ?? 0;
        $assignment->allow_late = $data['allow_late'] ?? 0;
        $assignment->late_penalty = $data['late_penalty'] ?? 0;
        $assignment->max_attempts = $data['max_attempts'] ?? 1;
        $assignment->time_limit = $data['time_limit'] ?? 0;
        
        // 评分模式
        $assignment->grade_mode = $data['grade_mode'] ?? AssignmentModel::GRADE_MODE_MANUAL;
        
        // 作业说明和附件
        $assignment->instructions = $data['instructions'] ?? '';
        if (isset($data['attachments'])) {
            $assignment->setAttachmentsData($data['attachments']);
        }
        
        // 状态
        $assignment->status = $data['status'] ?? AssignmentModel::STATUS_DRAFT;
        if ($assignment->status === AssignmentModel::STATUS_PUBLISHED) {
            $assignment->publish_time = time();
        }
        
        // 保存
        if (!$assignment->save()) {
            throw new \Exception('创建作业失败：' . implode(', ', $assignment->getMessages()));
        }
        
        return $assignment;
    }

    /**
     * 更新作业
     * 
     * @param int $id
     * @param array $data
     * @return AssignmentModel
     * @throws \Exception
     */
    public function update(int $id, array $data): AssignmentModel
    {
        $assignment = AssignmentModel::findFirst($id);
        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        // 如果作业已有提交，限制某些字段的修改
        if ($this->hasSubmissions($id)) {
            // 不允许修改总分（会影响已有提交的评分）
            if (isset($data['max_score']) && $data['max_score'] != $assignment->max_score) {
                throw new \Exception('作业已有提交记录，不允许修改总分');
            }
        }

        // 更新基本信息
        if (isset($data['title'])) {
            $assignment->title = $data['title'];
        }
        if (isset($data['description'])) {
            $assignment->description = $data['description'];
        }
        if (isset($data['chapter_id'])) {
            $assignment->chapter_id = $data['chapter_id'];
        }
        
        // 更新题目内容
        if (isset($data['content'])) {
            $content = $data['content'];
            if (!isset($content['questions'])) {
                throw new \Exception('作业内容必须包含questions数组');
            }
            $assignment->setContentData($content);
            
            // 重新计算总分
            $totalScore = 0;
            foreach ($content['questions'] as $question) {
                $totalScore += $question['score'] ?? 0;
            }
            $assignment->max_score = $totalScore;
        }
        
        // 更新作业设置
        if (isset($data['due_date'])) {
            $assignment->due_date = $data['due_date'];
        }
        if (isset($data['allow_late'])) {
            $assignment->allow_late = $data['allow_late'];
        }
        if (isset($data['late_penalty'])) {
            $assignment->late_penalty = $data['late_penalty'];
        }
        if (isset($data['max_attempts'])) {
            $assignment->max_attempts = $data['max_attempts'];
        }
        if (isset($data['time_limit'])) {
            $assignment->time_limit = $data['time_limit'];
        }
        if (isset($data['grade_mode'])) {
            $assignment->grade_mode = $data['grade_mode'];
        }
        if (isset($data['instructions'])) {
            $assignment->instructions = $data['instructions'];
        }
        if (isset($data['attachments'])) {
            $assignment->setAttachmentsData($data['attachments']);
        }
        
        // 更新状态
        if (isset($data['status'])) {
            $oldStatus = $assignment->status;
            $assignment->status = $data['status'];
            
            // 如果从草稿变为发布，记录发布时间
            if ($oldStatus === AssignmentModel::STATUS_DRAFT && 
                $data['status'] === AssignmentModel::STATUS_PUBLISHED) {
                $assignment->publish_time = time();
            }
        }
        
        // 保存
        if (!$assignment->save()) {
            throw new \Exception('更新作业失败：' . implode(', ', $assignment->getMessages()));
        }
        
        return $assignment;
    }

    /**
     * 删除作业（软删除）
     * 
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        $assignment = AssignmentModel::findFirst($id);
        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        if (!$assignment->delete()) {
            throw new \Exception('删除作业失败：' . implode(', ', $assignment->getMessages()));
        }

        return true;
    }

    /**
     * 获取作业列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public function getList(array $params = []): array
    {
        $repo = new \App\Repos\Assignment();
        
        // 构建查询条件
        $conditions = ['delete_time = 0'];
        $bind = [];
        
        // 课程筛选
        if (!empty($params['course_id'])) {
            $conditions[] = 'course_id = :course_id:';
            $bind['course_id'] = $params['course_id'];
        }
        
        // 状态筛选
        if (!empty($params['status'])) {
            $conditions[] = 'status = :status:';
            $bind['status'] = $params['status'];
        }
        
        // 标题搜索
        if (!empty($params['title'])) {
            $conditions[] = 'title LIKE :title:';
            $bind['title'] = '%' . $params['title'] . '%';
        }
        
        // 章节筛选
        if (!empty($params['chapter_id'])) {
            $conditions[] = 'chapter_id = :chapter_id:';
            $bind['chapter_id'] = $params['chapter_id'];
        }
        
        // 分页参数
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 15;
        $sort = $params['sort'] ?? 'id DESC';
        
        // 执行查询
        $pager = $repo->paginate([
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind
        ], $sort, $page, $limit);
        
        $assignments = [];
        if ($pager->total_items > 0) {
            foreach ($pager->items as $item) {
                $assignments[] = $item->toArray();
            }
        }
        
        return [
            'assignments' => $assignments,
            'pager' => $pager
        ];
    }

    /**
     * 获取作业详情
     * 
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function getDetail(int $id): array
    {
        $assignment = AssignmentModel::findFirst($id);
        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        $data = $assignment->toArray();
        
        // 解析题目数据
        $questions = $assignment->getQuestions();
        $data['questions'] = $questions;
        $data['question_count'] = count($questions);
        
        // 判断是否超期
        $data['is_overdue'] = ($assignment->due_date > 0 && time() > $assignment->due_date);
        
        // 加载课程信息
        if ($assignment->course_id) {
            $course = \App\Models\Course::findFirst($assignment->course_id);
            if ($course) {
                $data['course'] = [
                    'id' => $course->id,
                    'title' => $course->title
                ];
            }
        }
        
        // 附加统计信息
        $data['submission_stats'] = $assignment->getSubmissionStats();
        
        return $data;
    }

    /**
     * 发布作业
     * 
     * @param int $id
     * @return AssignmentModel
     * @throws \Exception
     */
    public function publish(int $id): AssignmentModel
    {
        $assignment = AssignmentModel::findFirst($id);
        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        if ($assignment->status !== AssignmentModel::STATUS_DRAFT) {
            throw new \Exception('只能发布草稿状态的作业');
        }

        // 验证作业是否完整
        $questions = $assignment->getQuestions();
        if (empty($questions)) {
            throw new \Exception('作业至少需要包含一个题目');
        }

        $assignment->status = AssignmentModel::STATUS_PUBLISHED;
        $assignment->publish_time = time();

        if (!$assignment->save()) {
            throw new \Exception('发布作业失败：' . implode(', ', $assignment->getMessages()));
        }

        return $assignment;
    }

    /**
     * 检查作业是否有提交记录
     * 
     * @param int $assignmentId
     * @return bool
     */
    private function hasSubmissions(int $assignmentId): bool
    {
        $count = \App\Models\AssignmentSubmission::count([
            'conditions' => 'assignment_id = :id: AND delete_time = 0',
            'bind' => ['id' => $assignmentId]
        ]);

        return $count > 0;
    }

    /**
     * 验证必填字段
     * 
     * @param array $data
     * @param array $required
     * @throws \Exception
     */
    private function validateRequired(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \Exception("缺少必填字段: {$field}");
            }
        }
    }
}


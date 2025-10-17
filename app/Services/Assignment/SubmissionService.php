<?php
/**
 * 作业提交服务
 * 
 * 负责学生提交作业、保存草稿等功能
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Services\Service;

class SubmissionService extends Service
{
    /**
     * 保存草稿
     * 
     * @param int $assignmentId
     * @param int $userId
     * @param array $answers 答案数据
     * @param array $options 其他选项
     * @return SubmissionModel
     * @throws \Exception
     */
    public function saveAsDraft(int $assignmentId, int $userId, array $answers, array $options = []): SubmissionModel
    {
        // 查询是否已有草稿或提交记录
        $submission = SubmissionModel::findFirst([
            'conditions' => 'assignment_id = :assignment_id: AND user_id = :user_id: AND delete_time = 0',
            'bind' => [
                'assignment_id' => $assignmentId,
                'user_id' => $userId
            ],
            'order' => 'id DESC'
        ]);

        if (!$submission) {
            // 创建新的提交记录
            $submission = new SubmissionModel();
            $submission->assignment_id = $assignmentId;
            $submission->user_id = $userId;
            $submission->status = SubmissionModel::STATUS_DRAFT;
            
            // 获取作业信息
            $assignment = AssignmentModel::findFirst($assignmentId);
            if ($assignment) {
                $submission->max_score = $assignment->max_score;
            }
        }

        // 更新答案内容（使用新格式）
        $submission->setContentData(['answers' => $answers]);

        // 设置附件（如果有）
        if (isset($options['attachments'])) {
            $submission->setAttachmentsData($options['attachments']);
        }

        // 保存
        if (!$submission->save()) {
            throw new \Exception('保存草稿失败：' . implode(', ', $submission->getMessages()));
        }

        return $submission;
    }

    /**
     * 提交作业
     * 
     * @param int $assignmentId
     * @param int $userId
     * @param array $answers 答案数据
     * @param array $options 其他选项
     * @return array
     * @throws \Exception
     */
    public function submit(int $assignmentId, int $userId, array $answers, array $options = []): array
    {
        // 加载作业
        $assignment = AssignmentModel::findFirst($assignmentId);
        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        // 检查是否可以提交
        $canSubmitResult = $this->canSubmit($assignmentId, $userId);
        if (!$canSubmitResult['allowed']) {
            throw new \Exception($canSubmitResult['reason']);
        }

        // 查询是否已有提交记录
        $submission = SubmissionModel::findFirst([
            'conditions' => 'assignment_id = :assignment_id: AND user_id = :user_id: AND delete_time = 0',
            'bind' => [
                'assignment_id' => $assignmentId,
                'user_id' => $userId
            ],
            'order' => 'id DESC'
        ]);

        if (!$submission) {
            // 创建新的提交记录
            $submission = new SubmissionModel();
            $submission->assignment_id = $assignmentId;
            $submission->user_id = $userId;
            $submission->attempt_count = 1;
        } else {
            // 更新提交次数
            $submission->attempt_count += 1;
        }

        // 设置状态和时间
        $submission->status = SubmissionModel::STATUS_SUBMITTED;
        $submission->submit_time = time();
        $submission->max_score = $assignment->max_score;

        // 检查是否迟交
        if ($assignment->due_date > 0 && $submission->submit_time > $assignment->due_date) {
            $submission->is_late = 1;
        } else {
            $submission->is_late = 0;
        }

        // 设置IP和用户代理
        if (isset($options['submit_ip'])) {
            $submission->submit_ip = $options['submit_ip'];
        }
        if (isset($options['user_agent'])) {
            $submission->user_agent = $options['user_agent'];
        }

        // 设置答案内容（使用新格式）
        $submission->setContentData(['answers' => $answers]);

        // 设置附件（如果有）
        if (isset($options['attachments'])) {
            $submission->setAttachmentsData($options['attachments']);
        }

        // 保存提交记录
        if (!$submission->save()) {
            throw new \Exception('提交失败：' . implode(', ', $submission->getMessages()));
        }

        // 根据作业的评分模式决定是否自动评分
        $autoGraded = false;
        $allGraded = false;
        
        if (in_array($assignment->grade_mode, [AssignmentModel::GRADE_MODE_AUTO, AssignmentModel::GRADE_MODE_MIXED])) {
            try {
                // 调用自动评分服务
                $gradingService = new GradingService();
                $autoGradeResult = $gradingService->autoGrade($submission->id);
                
                // 重新加载submission以获取最新状态
                $submission = SubmissionModel::findFirst($submission->id);
                
                $autoGraded = true;
                $allGraded = !($autoGradeResult['has_manual_question'] ?? true);
            } catch (\Exception $e) {
                // 自动评分失败，记录错误但不影响提交
                error_log('自动评分失败: ' . $e->getMessage());
                // 重新加载submission
                $submission = SubmissionModel::findFirst($submission->id);
            }
        }

        return [
            'submission' => $submission,
            'auto_graded' => $autoGraded,
            'all_graded' => $allGraded
        ];
    }

    /**
     * 检查是否可以提交
     * 
     * @param int $assignmentId
     * @param int $userId
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function canSubmit(int $assignmentId, int $userId): array
    {
        // 加载作业
        $assignment = AssignmentModel::findFirst($assignmentId);
        if (!$assignment) {
            return ['allowed' => false, 'reason' => '作业不存在'];
        }

        // 检查作业状态
        if ($assignment->status !== AssignmentModel::STATUS_PUBLISHED) {
            return ['allowed' => false, 'reason' => '作业未发布'];
        }

        // 检查截止时间
        if ($assignment->due_date > 0 && time() > $assignment->due_date) {
            if (!$assignment->allow_late) {
                return ['allowed' => false, 'reason' => '已超过截止时间'];
            }
        }

        // 检查提交次数
        if ($assignment->max_attempts > 0) {
            $submission = SubmissionModel::findFirst([
                'conditions' => 'assignment_id = :assignment_id: AND user_id = :user_id: AND delete_time = 0',
                'bind' => [
                    'assignment_id' => $assignmentId,
                    'user_id' => $userId
                ]
            ]);

            if ($submission && $submission->attempt_count >= $assignment->max_attempts) {
                // 如果已批改且是returned状态，允许重新提交
                if ($submission->status === SubmissionModel::STATUS_RETURNED) {
                    return ['allowed' => true, 'reason' => ''];
                }
                return ['allowed' => false, 'reason' => '已达到最大提交次数'];
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * 获取学生的提交记录
     * 
     * @param int $assignmentId
     * @param int $userId
     * @return SubmissionModel|null
     */
    public function getSubmission(int $assignmentId, int $userId): ?SubmissionModel
    {
        $submission = SubmissionModel::findFirst([
            'conditions' => 'assignment_id = :assignment_id: AND user_id = :user_id: AND delete_time = 0',
            'bind' => [
                'assignment_id' => $assignmentId,
                'user_id' => $userId
            ],
            'order' => 'id DESC'
        ]);
        
        // Phalcon 的 findFirst 返回 false 而不是 null，需要转换
        return $submission ?: null;
    }
}


<?php
/**
 * 作业提交验证器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Validators;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as AssignmentSubmissionModel;

class AssignmentSubmission extends Validator
{
    protected $errors = [];

    /**
     * 验证提交数据
     *
     * @param array $data
     * @param AssignmentModel|null $assignment
     * @return bool
     */
    public function validate(array $data, AssignmentModel $assignment = null): bool
    {
        $this->errors = [];

        $this->checkAssignment($data, $assignment);
        $this->checkContent($data, $assignment);
        $this->checkAttachments($data, $assignment);
        $this->checkScore($data);

        return empty($this->errors);
    }

    /**
     * 检查作业
     */
    protected function checkAssignment(array $data, AssignmentModel $assignment = null)
    {
        if (empty($data['assignment_id']) && !$assignment) {
            $this->errors[] = '作业ID不能为空';
            return;
        }

        if (!$assignment) {
            $assignment = AssignmentModel::findFirst([
                'conditions' => 'id = :id:',
                'bind' => ['id' => $data['assignment_id']]
            ]);

            if (!$assignment) {
                $this->errors[] = '作业不存在';
                return;
            }
        }

        // 检查作业状态
        if ($assignment->status !== AssignmentModel::STATUS_PUBLISHED) {
            $this->errors[] = '作业未发布';
        }

        // 检查截止时间
        if ($assignment->due_date > 0 && time() > $assignment->due_date && !$assignment->allow_late) {
            $this->errors[] = '作业已过截止时间且不允许迟交';
        }
    }

    /**
     * 检查提交内容
     */
    protected function checkContent(array $data, AssignmentModel $assignment = null)
    {
        if (empty($data['content'])) {
            $this->errors[] = '提交内容不能为空';
            return;
        }

        if (!$assignment) {
            return;
        }

        $assignmentContent = $assignment->getContentData();
        if (empty($assignmentContent)) {
            return; // 如果作业没有结构化内容，跳过验证
        }

        $submissionContent = $data['content'];

        // 验证每个题目的答案
        foreach ($assignmentContent as $index => $question) {
            $questionId = $question['id'] ?? $index;
            
            if (!isset($submissionContent[$questionId])) {
                $this->errors[] = "第{$index}题未回答";
                continue;
            }

            $answer = $submissionContent[$questionId];

            // 根据题目类型验证答案
            switch ($question['type']) {
                case 'choice':
                    $this->validateChoiceAnswer($answer, $question, $index);
                    break;
                case 'essay':
                    $this->validateEssayAnswer($answer, $question, $index);
                    break;
                case 'upload':
                    $this->validateUploadAnswer($answer, $question, $index);
                    break;
            }
        }
    }

    /**
     * 验证选择题答案
     */
    protected function validateChoiceAnswer($answer, array $question, int $index)
    {
        if (empty($answer)) {
            $this->errors[] = "第{$index}题必须选择一个答案";
            return;
        }

        // 多选题答案是数组
        if (isset($question['multiple']) && $question['multiple']) {
            if (!is_array($answer)) {
                $this->errors[] = "第{$index}题答案格式错误(多选题)";
                return;
            }
        } else {
            // 单选题答案是字符串
            if (is_array($answer)) {
                $this->errors[] = "第{$index}题答案格式错误(单选题)";
                return;
            }
        }

        // 检查答案是否在选项范围内
        $validOptions = array_keys($question['options'] ?? []);
        $answerArray = is_array($answer) ? $answer : [$answer];
        
        foreach ($answerArray as $ans) {
            if (!in_array($ans, $validOptions)) {
                $this->errors[] = "第{$index}题包含无效选项: {$ans}";
            }
        }
    }

    /**
     * 验证简答题答案
     */
    protected function validateEssayAnswer($answer, array $question, int $index)
    {
        if (empty($answer)) {
            $this->errors[] = "第{$index}题不能为空";
            return;
        }

        if (!is_string($answer)) {
            $this->errors[] = "第{$index}题答案格式错误";
            return;
        }

        // 检查字数限制
        if (isset($question['min_length'])) {
            if (mb_strlen($answer) < $question['min_length']) {
                $this->errors[] = "第{$index}题字数不能少于{$question['min_length']}字";
            }
        }

        if (isset($question['max_length'])) {
            if (mb_strlen($answer) > $question['max_length']) {
                $this->errors[] = "第{$index}题字数不能超过{$question['max_length']}字";
            }
        }
    }

    /**
     * 验证文件上传题答案
     */
    protected function validateUploadAnswer($answer, array $question, int $index)
    {
        if (empty($answer)) {
            $this->errors[] = "第{$index}题必须上传文件";
            return;
        }

        if (!is_array($answer)) {
            $this->errors[] = "第{$index}题文件格式错误";
            return;
        }

        // 检查文件数量限制
        if (isset($question['max_files'])) {
            if (count($answer) > $question['max_files']) {
                $this->errors[] = "第{$index}题文件数量不能超过{$question['max_files']}个";
            }
        }

        // 检查文件类型
        if (isset($question['allowed_types'])) {
            foreach ($answer as $file) {
                if (empty($file['name']) || empty($file['path'])) {
                    $this->errors[] = "第{$index}题文件信息不完整";
                    continue;
                }

                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, $question['allowed_types'])) {
                    $this->errors[] = "第{$index}题文件类型不允许: {$extension}";
                }
            }
        }
    }

    /**
     * 检查附件
     */
    protected function checkAttachments(array $data, AssignmentModel $assignment = null)
    {
        if (empty($data['attachments'])) {
            return;
        }

        if (!is_array($data['attachments'])) {
            $this->errors[] = '附件格式错误';
            return;
        }

        // 检查附件数量限制
        if (count($data['attachments']) > 10) {
            $this->errors[] = '附件数量不能超过10个';
        }

        // 检查单个附件
        foreach ($data['attachments'] as $index => $attachment) {
            if (empty($attachment['name']) || empty($attachment['path'])) {
                $this->errors[] = "第{$index}个附件信息不完整";
                continue;
            }

            // 检查文件大小(如果提供)
            if (isset($attachment['size']) && $attachment['size'] > 100 * 1024 * 1024) {
                $this->errors[] = "第{$index}个附件大小不能超过100MB";
            }
        }
    }

    /**
     * 检查分数
     */
    protected function checkScore(array $data)
    {
        if (isset($data['score'])) {
            $score = floatval($data['score']);
            
            if ($score < 0) {
                $this->errors[] = '分数不能为负数';
            }

            if (isset($data['max_score']) && $score > $data['max_score']) {
                $this->errors[] = '分数不能超过满分';
            }
        }
    }

    /**
     * 验证批改数据
     */
    public function validateGrading(array $data): bool
    {
        $this->errors = [];

        if (!isset($data['score'])) {
            $this->errors[] = '分数不能为空';
        } else {
            $score = floatval($data['score']);
            if ($score < 0) {
                $this->errors[] = '分数不能为负数';
            }
        }

        // 验证分题批改详情
        if (!empty($data['grade_details'])) {
            if (!is_array($data['grade_details'])) {
                $this->errors[] = '批改详情格式错误';
            } else {
                foreach ($data['grade_details'] as $questionId => $detail) {
                    if (isset($detail['score']) && $detail['score'] < 0) {
                        $this->errors[] = "题目{$questionId}分数不能为负数";
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * 验证提交状态变更
     */
    public function validateStatusChange(string $currentStatus, string $newStatus): bool
    {
        $this->errors = [];

        $validStatuses = array_keys(AssignmentSubmissionModel::getStatuses());
        
        if (!in_array($newStatus, $validStatuses)) {
            $this->errors[] = '无效的状态';
            return false;
        }

        // 状态变更规则
        $allowedTransitions = [
            AssignmentSubmissionModel::STATUS_DRAFT => [
                AssignmentSubmissionModel::STATUS_SUBMITTED
            ],
            AssignmentSubmissionModel::STATUS_SUBMITTED => [
                AssignmentSubmissionModel::STATUS_GRADED,
                AssignmentSubmissionModel::STATUS_RETURNED
            ],
            AssignmentSubmissionModel::STATUS_RETURNED => [
                AssignmentSubmissionModel::STATUS_SUBMITTED
            ],
            AssignmentSubmissionModel::STATUS_GRADED => [
                AssignmentSubmissionModel::STATUS_RETURNED
            ]
        ];

        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            $this->errors[] = "不能从状态 {$currentStatus} 变更为 {$newStatus}";
            return false;
        }

        return true;
    }

    /**
     * 获取错误信息
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 是否有错误
     */
    public function hasError(): bool
    {
        return !empty($this->errors);
    }

    /**
     * 获取第一个错误
     */
    public function getFirstError(): string
    {
        return $this->errors[0] ?? '';
    }
}

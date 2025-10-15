<?php
/**
 * 作业提交验证器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Validators\Assignment;

use App\Models\Assignment as AssignmentModel;

class SubmissionValidator
{
    /**
     * 验证作业提交数据
     * 
     * @param array $answers 学生答案
     * @param AssignmentModel $assignment 作业对象
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateSubmit(array $answers, AssignmentModel $assignment): array
    {
        $errors = [];
        $questions = $assignment->getQuestions();

        // 遍历所有题目验证答案
        foreach ($questions as $question) {
            $questionId = $question['id'];
            $required = $question['required'] ?? true;
            $type = $question['type'];

            // 检查必答题是否已回答
            if ($required && (!isset($answers[$questionId]) || $this->isEmpty($answers[$questionId]))) {
                $errors[$questionId] = "题目「{$question['title']}」为必答题";
                continue;
            }

            // 如果没有回答，跳过验证
            if (!isset($answers[$questionId])) {
                continue;
            }

            $answer = $answers[$questionId];

            // 根据题型验证答案格式
            $validation = $this->validateAnswerByType($type, $answer, $question);
            if (!$validation['valid']) {
                $errors[$questionId] = $validation['error'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 根据题型验证答案
     */
    private function validateAnswerByType(string $type, $answer, array $question): array
    {
        switch ($type) {
            case 'single_choice':
                return $this->validateSingleChoiceAnswer($answer, $question);
            
            case 'multiple_choice':
                return $this->validateMultipleChoiceAnswer($answer, $question);
            
            case 'essay':
                return $this->validateEssayAnswer($answer, $question);
            
            case 'code':
                return $this->validateCodeAnswer($answer, $question);
            
            case 'file_upload':
                return $this->validateFileUploadAnswer($answer, $question);
            
            default:
                return ['valid' => true, 'error' => ''];
        }
    }

    /**
     * 验证单选题答案
     */
    private function validateSingleChoiceAnswer($answer, array $question): array
    {
        if (!is_string($answer)) {
            return ['valid' => false, 'error' => '单选题答案必须是字符串'];
        }

        $validKeys = array_column($question['options'] ?? [], 'key');
        if (!in_array($answer, $validKeys)) {
            return ['valid' => false, 'error' => '答案不在选项范围内'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证多选题答案
     */
    private function validateMultipleChoiceAnswer($answer, array $question): array
    {
        if (!is_array($answer)) {
            return ['valid' => false, 'error' => '多选题答案必须是数组'];
        }

        if (empty($answer)) {
            return ['valid' => false, 'error' => '至少选择一个选项'];
        }

        $validKeys = array_column($question['options'] ?? [], 'key');
        foreach ($answer as $key) {
            if (!in_array($key, $validKeys)) {
                return ['valid' => false, 'error' => '答案不在选项范围内'];
            }
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证简答题答案
     */
    private function validateEssayAnswer($answer, array $question): array
    {
        if (!is_string($answer)) {
            return ['valid' => false, 'error' => '简答题答案必须是文本'];
        }

        $length = mb_strlen(trim($answer));

        if (isset($question['min_length']) && $length < $question['min_length']) {
            return ['valid' => false, 'error' => "答案至少需要{$question['min_length']}个字符"];
        }

        if (isset($question['max_length']) && $length > $question['max_length']) {
            return ['valid' => false, 'error' => "答案不能超过{$question['max_length']}个字符"];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证编程题答案
     */
    private function validateCodeAnswer($answer, array $question): array
    {
        if (!is_string($answer)) {
            return ['valid' => false, 'error' => '编程题答案必须是文本'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证文件上传题答案
     */
    private function validateFileUploadAnswer($answer, array $question): array
    {
        if (!is_string($answer)) {
            return ['valid' => false, 'error' => '文件上传题答案必须是文件路径'];
        }

        return ['valid' => true, 'error' => ''];
    }

    /**
     * 检查值是否为空
     */
    private function isEmpty($value): bool
    {
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_array($value)) {
            return empty($value);
        }
        return $value === null;
    }
}


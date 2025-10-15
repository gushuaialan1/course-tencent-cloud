<?php
/**
 * 题目验证器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Validators\Assignment;

class QuestionValidator
{
    /**
     * 验证题目数据
     * 
     * @param array $question
     * @return array ['valid' => bool, 'error' => string]
     */
    public function validate(array $question): array
    {
        // 必填字段检查
        if (!isset($question['type'])) {
            return ['valid' => false, 'error' => '题目类型不能为空'];
        }
        if (!isset($question['title']) || empty(trim($question['title']))) {
            return ['valid' => false, 'error' => '题目标题不能为空'];
        }
        if (!isset($question['score']) || $question['score'] <= 0) {
            return ['valid' => false, 'error' => '题目分值必须大于0'];
        }

        // 根据题型验证
        $type = $question['type'];
        
        switch ($type) {
            case 'single_choice':
                return $this->validateSingleChoice($question);
            
            case 'multiple_choice':
                return $this->validateMultipleChoice($question);
            
            case 'essay':
                return $this->validateEssay($question);
            
            case 'code':
                return $this->validateCode($question);
            
            case 'file_upload':
                return $this->validateFileUpload($question);
            
            default:
                return ['valid' => false, 'error' => "不支持的题型: {$type}"];
        }
    }

    /**
     * 验证单选题
     */
    private function validateSingleChoice(array $question): array
    {
        if (!isset($question['options']) || !is_array($question['options'])) {
            return ['valid' => false, 'error' => '单选题必须包含选项'];
        }
        
        if (count($question['options']) < 2) {
            return ['valid' => false, 'error' => '单选题至少需要2个选项'];
        }
        
        // 验证选项格式
        foreach ($question['options'] as $option) {
            if (!isset($option['key']) || !isset($option['value'])) {
                return ['valid' => false, 'error' => '选项格式错误，必须包含key和value'];
            }
        }
        
        if (!isset($question['correct_answer']) || !is_string($question['correct_answer'])) {
            return ['valid' => false, 'error' => '单选题必须设置正确答案（字符串）'];
        }
        
        // 验证正确答案在选项中
        $keys = array_column($question['options'], 'key');
        if (!in_array($question['correct_answer'], $keys)) {
            return ['valid' => false, 'error' => '正确答案不在选项范围内'];
        }
        
        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证多选题
     */
    private function validateMultipleChoice(array $question): array
    {
        if (!isset($question['options']) || !is_array($question['options'])) {
            return ['valid' => false, 'error' => '多选题必须包含选项'];
        }
        
        if (count($question['options']) < 2) {
            return ['valid' => false, 'error' => '多选题至少需要2个选项'];
        }
        
        // 验证选项格式
        foreach ($question['options'] as $option) {
            if (!isset($option['key']) || !isset($option['value'])) {
                return ['valid' => false, 'error' => '选项格式错误，必须包含key和value'];
            }
        }
        
        if (!isset($question['correct_answer']) || !is_array($question['correct_answer'])) {
            return ['valid' => false, 'error' => '多选题必须设置正确答案（数组）'];
        }
        
        if (count($question['correct_answer']) < 1) {
            return ['valid' => false, 'error' => '多选题至少需要1个正确答案'];
        }
        
        // 验证正确答案在选项中
        $keys = array_column($question['options'], 'key');
        foreach ($question['correct_answer'] as $answer) {
            if (!in_array($answer, $keys)) {
                return ['valid' => false, 'error' => '正确答案不在选项范围内'];
            }
        }
        
        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证简答题
     */
    private function validateEssay(array $question): array
    {
        if (isset($question['min_length']) && isset($question['max_length'])) {
            if ($question['min_length'] > $question['max_length']) {
                return ['valid' => false, 'error' => '最小字数不能大于最大字数'];
            }
        }
        
        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证编程题
     */
    private function validateCode(array $question): array
    {
        // 编程题基本验证通过
        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证文件上传题
     */
    private function validateFileUpload(array $question): array
    {
        // 文件上传题基本验证通过
        return ['valid' => true, 'error' => ''];
    }
}


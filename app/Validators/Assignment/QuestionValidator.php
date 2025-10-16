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

        // 根据题型验证（统一标准）
        $type = $question['type'];
        
        switch ($type) {
            case 'choice':
                return $this->validateChoice($question);
            
            case 'text':
            case 'essay':
                return $this->validateTextEssay($question);
            
            case 'code':
                return $this->validateCode($question);
            
            case 'file':
                return $this->validateFile($question);
            
            default:
                return ['valid' => false, 'error' => "不支持的题型: {$type}"];
        }
    }

    /**
     * 验证选择题（统一处理单选和多选）
     */
    private function validateChoice(array $question): array
    {
        // 必须有 multiple 字段
        if (!isset($question['multiple'])) {
            return ['valid' => false, 'error' => '选择题必须指定 multiple 字段（单选/多选）'];
        }
        
        $isMultiple = $question['multiple'];
        
        // 验证选项
        if (!isset($question['options']) || !is_array($question['options'])) {
            return ['valid' => false, 'error' => '选择题必须包含选项'];
        }
        
        if (count($question['options']) < 2) {
            return ['valid' => false, 'error' => '选择题至少需要2个选项'];
        }
        
        // 验证选项格式（对象格式：{"A": "选项A", "B": "选项B"}）
        $optionKeys = array_keys($question['options']);
        if (empty($optionKeys)) {
            return ['valid' => false, 'error' => '选项不能为空'];
        }
        
        foreach ($question['options'] as $key => $value) {
            if (!is_string($key) || empty($value)) {
                return ['valid' => false, 'error' => '选项格式错误，必须是 {A: "选项内容", B: "选项内容"}'];
            }
        }
        
        // 验证正确答案
        if (!isset($question['correct_answer'])) {
            return ['valid' => false, 'error' => '必须设置正确答案'];
        }
        
        if ($isMultiple) {
            // 多选题：答案必须是数组
            if (!is_array($question['correct_answer'])) {
                return ['valid' => false, 'error' => '多选题的正确答案必须是数组'];
            }
            if (count($question['correct_answer']) < 1) {
                return ['valid' => false, 'error' => '多选题至少需要1个正确答案'];
            }
            // 验证答案在选项中
            foreach ($question['correct_answer'] as $answer) {
                if (!isset($question['options'][$answer])) {
                    return ['valid' => false, 'error' => "正确答案 {$answer} 不在选项范围内"];
                }
            }
        } else {
            // 单选题：答案必须是字符串
            if (!is_string($question['correct_answer'])) {
                return ['valid' => false, 'error' => '单选题的正确答案必须是字符串'];
            }
            // 验证答案在选项中
            if (!isset($question['options'][$question['correct_answer']])) {
                return ['valid' => false, 'error' => '正确答案不在选项范围内'];
            }
        }
        
        return ['valid' => true, 'error' => ''];
    }

    /**
     * 验证简答题/论述题
     */
    private function validateTextEssay(array $question): array
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
     * 验证文件题
     */
    private function validateFile(array $question): array
    {
        // 文件题基本验证通过
        return ['valid' => true, 'error' => ''];
    }
}


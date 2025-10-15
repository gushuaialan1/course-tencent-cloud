<?php
/**
 * 作业验证器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Validators\Assignment;

class AssignmentValidator
{
    /**
     * 验证作业创建数据
     * 
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCreate(array $data): array
    {
        $errors = [];

        // 验证基本字段
        if (empty($data['title']) || strlen(trim($data['title'])) === 0) {
            $errors['title'] = '作业标题不能为空';
        } elseif (strlen($data['title']) > 200) {
            $errors['title'] = '作业标题不能超过200个字符';
        }

        if (empty($data['course_id']) || !is_numeric($data['course_id'])) {
            $errors['course_id'] = '必须选择课程';
        }

        // 验证题目内容
        if (empty($data['content']) || !isset($data['content']['questions'])) {
            $errors['content'] = '作业必须包含题目';
        } else {
            $questions = $data['content']['questions'];
            
            if (!is_array($questions) || count($questions) === 0) {
                $errors['content'] = '作业至少需要包含一个题目';
            } else {
                // 验证每个题目
                $questionValidator = new QuestionValidator();
                $totalScore = 0;
                
                foreach ($questions as $index => $question) {
                    $result = $questionValidator->validate($question);
                    if (!$result['valid']) {
                        $errors["question_{$index}"] = "题目" . ($index + 1) . ": " . $result['error'];
                    } else {
                        $totalScore += $question['score'] ?? 0;
                    }
                }
                
                // 验证总分
                if ($totalScore <= 0) {
                    $errors['total_score'] = '作业总分必须大于0';
                }
            }
        }

        // 验证截止时间
        if (isset($data['due_date']) && $data['due_date'] > 0) {
            if ($data['due_date'] < time()) {
                $errors['due_date'] = '截止时间不能早于当前时间';
            }
        }

        // 验证评分模式
        if (isset($data['grade_mode'])) {
            $validModes = ['auto', 'manual', 'mixed'];
            if (!in_array($data['grade_mode'], $validModes)) {
                $errors['grade_mode'] = '无效的评分模式';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 验证作业更新数据
     * 
     * @param array $data
     * @param bool $hasSubmissions 是否已有提交记录
     * @return array
     */
    public function validateUpdate(array $data, bool $hasSubmissions = false): array
    {
        $errors = [];

        // 如果已有提交记录，某些字段不允许修改
        if ($hasSubmissions) {
            if (isset($data['max_score'])) {
                $errors['max_score'] = '作业已有提交记录，不允许修改总分';
            }
        }

        // 其他验证同创建
        $createValidation = $this->validateCreate($data);
        if (!$createValidation['valid']) {
            $errors = array_merge($errors, $createValidation['errors']);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}


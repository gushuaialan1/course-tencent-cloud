<?php
/**
 * 选择题评分器（统一处理单选和多选）
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment\Graders;

class ChoiceGrader implements QuestionGraderInterface
{
    /**
     * 判断是否支持该题型
     *
     * @param string $questionType
     * @return bool
     */
    public function supports(string $questionType): bool
    {
        return $questionType === 'choice';
    }

    /**
     * 选择题评分逻辑
     * 
     * 根据 question['multiple'] 字段判断单选还是多选
     * 
     * 单选规则：
     * - 学生答案必须是字符串
     * - 完全匹配correct_answer得满分，否则得0分
     * 
     * 多选规则：
     * - 学生答案必须是数组
     * - 数组元素完全匹配（顺序无关）得满分，否则得0分
     *
     * @param array $question
     * @param mixed $studentAnswer
     * @return array
     */
    public function grade(array $question, $studentAnswer): array
    {
        $maxScore = $question['score'] ?? 0;
        $correctAnswer = $question['correct_answer'] ?? '';
        $isMultiple = $question['multiple'] ?? false;
        
        if ($isMultiple) {
            // 多选题
            return $this->gradeMultipleChoice($maxScore, $correctAnswer, $studentAnswer);
        } else {
            // 单选题
            return $this->gradeSingleChoice($maxScore, $correctAnswer, $studentAnswer);
        }
    }
    
    /**
     * 单选题评分
     */
    private function gradeSingleChoice($maxScore, $correctAnswer, $studentAnswer): array
    {
        // 验证答案类型必须是字符串
        if (!is_string($studentAnswer)) {
            return [
                'earned_score' => 0,
                'max_score' => $maxScore,
                'is_correct' => false,
                'auto_graded' => true,
                'error' => '答案格式错误：单选题答案必须是字符串'
            ];
        }
        
        // 判断是否正确
        $isCorrect = $studentAnswer === $correctAnswer;
        $earnedScore = $isCorrect ? $maxScore : 0;
        
        return [
            'earned_score' => $earnedScore,
            'max_score' => $maxScore,
            'is_correct' => $isCorrect,
            'auto_graded' => true
        ];
    }
    
    /**
     * 多选题评分
     */
    private function gradeMultipleChoice($maxScore, $correctAnswer, $studentAnswer): array
    {
        // 验证答案类型必须是数组
        if (!is_array($studentAnswer)) {
            return [
                'earned_score' => 0,
                'max_score' => $maxScore,
                'is_correct' => false,
                'auto_graded' => true,
                'error' => '答案格式错误：多选题答案必须是数组'
            ];
        }
        
        // 确保正确答案也是数组
        if (!is_array($correctAnswer)) {
            $correctAnswer = [$correctAnswer];
        }
        
        // 判断是否正确：数量相同且元素完全匹配（顺序无关）
        $isCorrect = count($studentAnswer) === count($correctAnswer) &&
                     empty(array_diff($studentAnswer, $correctAnswer)) &&
                     empty(array_diff($correctAnswer, $studentAnswer));
        
        $earnedScore = $isCorrect ? $maxScore : 0;
        
        return [
            'earned_score' => $earnedScore,
            'max_score' => $maxScore,
            'is_correct' => $isCorrect,
            'auto_graded' => true
        ];
    }
}


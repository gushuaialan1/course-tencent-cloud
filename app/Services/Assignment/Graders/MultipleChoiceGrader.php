<?php
/**
 * 多选题评分器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment\Graders;

class MultipleChoiceGrader implements QuestionGraderInterface
{
    /**
     * 判断是否支持该题型
     *
     * @param string $questionType
     * @return bool
     */
    public function supports(string $questionType): bool
    {
        return $questionType === 'multiple_choice';
    }

    /**
     * 多选题评分逻辑
     * 
     * 规则：
     * - 学生答案必须是数组
     * - 数组元素完全匹配（顺序无关）得满分
     * - 否则得0分（不支持部分得分）
     *
     * @param array $question
     * @param mixed $studentAnswer
     * @return array
     */
    public function grade(array $question, $studentAnswer): array
    {
        $maxScore = $question['score'] ?? 0;
        $correctAnswer = $question['correct_answer'] ?? [];
        
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


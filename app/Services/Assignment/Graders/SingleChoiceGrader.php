<?php
/**
 * 单选题评分器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment\Graders;

class SingleChoiceGrader implements QuestionGraderInterface
{
    /**
     * 判断是否支持该题型
     *
     * @param string $questionType
     * @return bool
     */
    public function supports(string $questionType): bool
    {
        return $questionType === 'single_choice';
    }

    /**
     * 单选题评分逻辑
     * 
     * 规则：
     * - 学生答案必须是字符串
     * - 完全匹配correct_answer得满分
     * - 否则得0分
     *
     * @param array $question
     * @param mixed $studentAnswer
     * @return array
     */
    public function grade(array $question, $studentAnswer): array
    {
        $maxScore = $question['score'] ?? 0;
        $correctAnswer = $question['correct_answer'] ?? '';
        
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
}


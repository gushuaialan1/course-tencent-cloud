<?php
/**
 * 编程题评分器（占位）
 * 
 * 编程题暂不支持自动评分，等待教师手动批改
 * 未来可扩展自动判题功能
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment\Graders;

class CodeGrader implements QuestionGraderInterface
{
    /**
     * 判断是否支持该题型
     *
     * @param string $questionType
     * @return bool
     */
    public function supports(string $questionType): bool
    {
        return $questionType === 'code';
    }

    /**
     * 编程题不自动评分
     * 
     * 返回0分，标记为需要人工批改
     *
     * @param array $question
     * @param mixed $studentAnswer
     * @return array
     */
    public function grade(array $question, $studentAnswer): array
    {
        $maxScore = $question['score'] ?? 0;
        
        return [
            'earned_score' => 0,
            'max_score' => $maxScore,
            'is_correct' => false,
            'auto_graded' => false,
            'requires_manual_grading' => true
        ];
    }
}


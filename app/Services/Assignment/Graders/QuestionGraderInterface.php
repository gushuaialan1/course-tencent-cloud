<?php
/**
 * 题目评分器接口
 * 
 * 使用策略模式，每种题型实现独立的评分器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment\Graders;

interface QuestionGraderInterface
{
    /**
     * 判断是否支持该题型
     *
     * @param string $questionType 题目类型
     * @return bool
     */
    public function supports(string $questionType): bool;

    /**
     * 评分
     *
     * @param array $question 题目数据
     * @param mixed $studentAnswer 学生答案
     * @return array 返回格式：
     * [
     *     'earned_score' => 实际得分,
     *     'max_score' => 满分,
     *     'is_correct' => 是否正确,
     *     'auto_graded' => 是否自动评分
     * ]
     */
    public function grade(array $question, $studentAnswer): array;
}


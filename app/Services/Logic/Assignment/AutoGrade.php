<?php
/**
 * 作业自动评分服务
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Logic\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Services\Logic\Service as LogicService;

class AutoGrade extends LogicService
{
    /**
     * 自动评分
     * 
     * @param SubmissionModel $submission
     * @return array
     */
    public function handle(SubmissionModel $submission)
    {
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($submission->assignment_id);
        
        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        // 只有自动评分模式才执行
        if ($assignment->grade_mode !== AssignmentModel::GRADE_MODE_AUTO) {
            return ['success' => false, 'reason' => '非自动评分模式'];
        }

        // 解析题目和用户答案
        $questions = json_decode($assignment->content, true);
        $userAnswers = json_decode($submission->content, true);
        
        if (!is_array($questions) || !is_array($userAnswers)) {
            return ['success' => false, 'reason' => '数据格式错误'];
        }

        // 兼容两种数据结构
        if (isset($questions['questions']) && is_array($questions['questions'])) {
            $questions = $questions['questions'];
        }

        $totalScore = 0;
        $earnedScore = 0;
        $details = [];

        foreach ($questions as $question) {
            $questionId = $question['id'] ?? null;
            $questionType = $question['type'] ?? '';
            $questionScore = floatval($question['score'] ?? 0);
            
            $totalScore += $questionScore;

            // 只对选择题自动评分
            if ($questionType === 'choice') {
                $correctAnswer = $question['correct_answer'] ?? [];
                $userAnswer = $userAnswers[$questionId] ?? null;
                
                $isCorrect = false;
                
                // 判断单选/多选
                if ($question['multiple'] ?? false) {
                    // 多选题：完全匹配（排序后比较）
                    if (is_array($userAnswer) && is_array($correctAnswer)) {
                        sort($correctAnswer);
                        sort($userAnswer);
                        $isCorrect = ($correctAnswer === $userAnswer);
                    }
                } else {
                    // 单选题：直接比较
                    if (is_array($correctAnswer) && count($correctAnswer) > 0) {
                        $isCorrect = ($correctAnswer[0] === $userAnswer);
                    }
                }

                $score = $isCorrect ? $questionScore : 0;
                $earnedScore += $score;

                $details[$questionId] = [
                    'question_id' => $questionId,
                    'type' => $questionType,
                    'max_score' => $questionScore,
                    'earned_score' => $score,
                    'is_correct' => $isCorrect,
                ];
            }
        }

        // 更新提交记录的分数和状态
        $submission->score = $earnedScore;
        $submission->status = 'graded';
        $submission->grade_time = time();
        $submission->update_time = time();
        
        if (!$submission->update()) {
            throw new \Exception('更新评分失败');
        }

        return [
            'success' => true,
            'total_score' => $totalScore,
            'earned_score' => $earnedScore,
            'details' => $details
        ];
    }
}


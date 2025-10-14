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

        // 只有自动评分或混合评分模式才执行
        if ($assignment->grade_mode === AssignmentModel::GRADE_MODE_MANUAL) {
            return ['success' => false, 'reason' => '手动评分模式，不进行自动评分'];
        }

        // 使用模型的getContentData()方法解析题目和用户答案
        $content = $assignment->getContentData();
        $userAnswers = $submission->getContentData();
        
        if (!is_array($content) || !is_array($userAnswers)) {
            return ['success' => false, 'reason' => '数据格式错误'];
        }

        // 兼容两种数据结构
        if (isset($content['questions']) && is_array($content['questions'])) {
            $questions = $content['questions'];
        } else {
            $questions = $content;
        }
        
        if (!is_array($questions)) {
            return ['success' => false, 'reason' => '题目数据格式错误'];
        }

        // 标准化题目数据，确保options格式正确
        foreach ($questions as &$question) {
            if (isset($question['options']) && is_array($question['options'])) {
                $normalizedOptions = [];
                foreach ($question['options'] as $key => $option) {
                    if (is_array($option) && isset($option['content'])) {
                        $normalizedOptions[$key] = $option['content'];
                    } elseif (is_object($option) && isset($option->content)) {
                        $normalizedOptions[$key] = $option->content;
                    } else {
                        $normalizedOptions[$key] = (string)$option;
                    }
                }
                $question['options'] = $normalizedOptions;
            }
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
        
        // 根据批改模式设置批改人和批改状态
        if ($assignment->grade_mode === AssignmentModel::GRADE_MODE_AUTO) {
            // 纯自动批改：无批改人，批改完成
            $submission->grader_id = null;
            $submission->grade_status = 'completed';
        } else {
            // 混合模式：选择题已自动批改，但主观题还需要老师批改
            // 设置批改人为作业创建者，状态为待批改
            $submission->grader_id = $assignment->owner_id;
            $submission->grade_status = 'pending';
        }
        
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


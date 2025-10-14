<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Assignment;

use App\Models\User as UserModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as SubmissionRepo;
use App\Repos\Course as CourseRepo;
use App\Services\Logic\Service as LogicService;

class SubmissionResult extends LogicService
{

    public function handle($id)
    {
        $user = $this->getCurrentUser(true);

        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($id);

        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        $submissionRepo = new SubmissionRepo();

        $submission = $submissionRepo->findByAssignmentAndUser($assignment->id, $user->id);

        $assignmentInfo = $this->handleAssignment($assignment);
        $submissionInfo = $this->handleSubmission($submission);
        $questions = $this->handleQuestionsWithAnswers($assignment, $submission);

        return [
            'assignment' => $assignmentInfo,
            'submission' => $submissionInfo,
            'questions' => $questions,
        ];
    }

    protected function handleAssignment($assignment)
    {
        $courseRepo = new CourseRepo();

        $course = $courseRepo->findById($assignment->course_id);
        
        // 使用模型的getContentData()方法解析题目数量
        $content = $assignment->getContentData();
        
        if (isset($content['questions']) && is_array($content['questions'])) {
            $questions = $content['questions'];
        } else {
            $questions = is_array($content) ? $content : [];
        }
        $questionCount = count($questions);

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'max_score' => $assignment->max_score,
            'question_count' => $questionCount,
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
        ];
    }

    protected function handleSubmission($submission)
    {
        if (!$submission) {
            return null;
        }

        // 使用模型的getContentData()方法解析学生答案
        $answers = $submission->getContentData();

        return [
            'id' => $submission->id,
            'score' => $submission->score,
            'status' => $submission->status,
            'grade_status' => $submission->grade_status,
            'content' => $answers,
            'answers' => $answers, // 兼容旧前端字段
            'feedback' => $submission->feedback,
            'is_late' => $submission->is_late,
            'submit_time' => $submission->submit_time,
            'graded_at' => $submission->grade_time, // 兼容旧前端字段
            'grade_time' => $submission->grade_time,
        ];
    }


    protected function handleQuestionsWithAnswers($assignment, $submission)
    {
        // 使用模型的getContentData()方法解析题目
        $content = $assignment->getContentData();
        
        if (!is_array($content)) {
            return [];
        }
        
        // 兼容两种数据结构
        if (isset($content['questions']) && is_array($content['questions'])) {
            $questions = $content['questions'];
        } else {
            $questions = $content;
        }
        
        if (!is_array($questions)) {
            return [];
        }

        $result = [];
        $userAnswers = [];

        if ($submission) {
            $userAnswers = $submission->getContentData();
        }

        foreach ($questions as $question) {
            $questionId = $question['id'] ?? null;
            
            if (!$questionId) {
                continue;
            }

            $userAnswer = isset($userAnswers[$questionId]) ? $userAnswers[$questionId] : null;
            
            // 标准化选项格式，确保是字符串
            $normalizedOptions = [];
            if (isset($question['options']) && is_array($question['options'])) {
                foreach ($question['options'] as $key => $option) {
                    if (is_array($option) && isset($option['content'])) {
                        $normalizedOptions[$key] = $option['content'];
                    } elseif (is_object($option) && isset($option->content)) {
                        $normalizedOptions[$key] = $option->content;
                    } else {
                        $normalizedOptions[$key] = (string)$option;
                    }
                }
            }
            
            // 计算得分（从自动评分结果中获取，如果有的话）
            $earnedScore = 0;
            if ($question['type'] === 'choice') {
                $correctAnswer = $question['correct_answer'] ?? [];
                $isCorrect = false;
                
                if ($question['multiple'] ?? false) {
                    // 多选题
                    if (is_array($userAnswer) && is_array($correctAnswer)) {
                        sort($correctAnswer);
                        sort($userAnswer);
                        $isCorrect = ($correctAnswer === $userAnswer);
                    }
                } else {
                    // 单选题
                    if (is_array($correctAnswer) && count($correctAnswer) > 0) {
                        $isCorrect = ($correctAnswer[0] === $userAnswer);
                    }
                }
                
                $earnedScore = $isCorrect ? floatval($question['score'] ?? 0) : 0;
            }

            $result[] = [
                'id' => $questionId,
                'type' => $question['type'],
                'title' => $question['title'] ?? '',
                'content' => $question['content'] ?? '',
                'options' => $normalizedOptions, // 使用标准化的选项
                'correct_answer' => $question['correct_answer'] ?? [],
                'score' => $question['score'] ?? 0,
                'user_answer' => $userAnswer,
                'earned_score' => $earnedScore,
                'feedback' => '',
            ];
        }

        return $result;
    }

}


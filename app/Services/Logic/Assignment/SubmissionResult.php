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
        error_log("SubmissionResult: Starting handle for assignment ID {$id}");
        
        try {
            // 查看成绩需要用户登录
            $user = $this->getLoginUser(true);
            error_log("SubmissionResult: User {$user->id} logged in");

            $assignmentRepo = new AssignmentRepo();
            $assignment = $assignmentRepo->findById($id);

            if (!$assignment) {
                error_log("SubmissionResult: Assignment {$id} not found");
                throw new \Exception('作业不存在');
            }
            error_log("SubmissionResult: Assignment {$id} found - {$assignment->title}");

            $submissionRepo = new SubmissionRepo();
            $submission = $submissionRepo->findByAssignmentAndUser($assignment->id, $user->id);

            if (!$submission) {
                error_log("SubmissionResult: No submission found for user {$user->id} assignment {$id}");
                return [
                    'assignment' => $this->handleAssignment($assignment),
                    'submission' => null,
                    'questions' => [],
                ];
            }
            error_log("SubmissionResult: Found submission ID {$submission->id} status {$submission->status}");

            error_log("SubmissionResult: Processing assignment info...");
            $assignmentInfo = $this->handleAssignment($assignment);
            
            error_log("SubmissionResult: Processing submission info...");
            $submissionInfo = $this->handleSubmission($submission);
            
            error_log("SubmissionResult: Processing questions with answers...");
            $questions = $this->handleQuestionsWithAnswers($assignment, $submission);
            error_log("SubmissionResult: Found " . count($questions) . " questions");

            error_log("SubmissionResult: Successfully completed handle");
            return [
                'assignment' => $assignmentInfo,
                'submission' => $submissionInfo,
                'questions' => $questions,
            ];
            
        } catch (\Exception $e) {
            error_log("SubmissionResult: ERROR - " . $e->getMessage());
            error_log("SubmissionResult: ERROR - " . $e->getTraceAsString());
            throw $e;
        }
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
            'score' => $submission->score ?: 0,
            'status' => $submission->status,
            'grade_status' => $submission->grade_status,
            'content' => $answers,
            'feedback' => $submission->feedback ?: '',
            'is_late' => $submission->is_late,
            'submit_time' => $submission->submit_time ?: 0,
            'grade_time' => $submission->grade_time ?: 0,
        ];
    }


    protected function handleQuestionsWithAnswers($assignment, $submission)
    {
        error_log("SubmissionResult: handleQuestionsWithAnswers - assignment ID {$assignment->id}");
        
        // 使用模型的getContentData()方法解析题目
        $content = $assignment->getContentData();
        error_log("SubmissionResult: Content data type: " . gettype($content));
        
        if (!is_array($content)) {
            error_log("SubmissionResult: Content is not array, returning empty");
            return [];
        }
        
        // 兼容两种数据结构
        if (isset($content['questions']) && is_array($content['questions'])) {
            $questions = $content['questions'];
            error_log("SubmissionResult: Using content['questions'], count: " . count($questions));
        } else {
            $questions = $content;
            error_log("SubmissionResult: Using content directly, count: " . count($questions));
        }
        
        if (!is_array($questions)) {
            error_log("SubmissionResult: Questions is not array, returning empty");
            return [];
        }

        $result = [];
        $userAnswers = [];

        if ($submission) {
            $userAnswers = $submission->getContentData();
            error_log("SubmissionResult: User answers type: " . gettype($userAnswers) . ", count: " . (is_array($userAnswers) ? count($userAnswers) : 0));
        } else {
            error_log("SubmissionResult: No submission provided");
        }

        foreach ($questions as $index => $question) {
            error_log("SubmissionResult: Processing question index {$index}");
            
            $questionId = $question['id'] ?? null;
            
            if (!$questionId) {
                error_log("SubmissionResult: Question has no ID, skipping");
                continue;
            }
            
            error_log("SubmissionResult: Processing question ID {$questionId}, type: " . ($question['type'] ?? 'unknown'));

            $userAnswer = isset($userAnswers[$questionId]) ? $userAnswers[$questionId] : null;
            error_log("SubmissionResult: User answer for Q{$questionId}: " . (is_null($userAnswer) ? 'null' : json_encode($userAnswer)));
            
            // 标准化选项格式，转换为Volt模板期望的格式
            $normalizedOptions = [];
            if (isset($question['options']) && is_array($question['options'])) {
                // 检查是否是新格式（对象数组）
                $firstOption = reset($question['options']);
                if (is_array($firstOption) && isset($firstOption['label']) && isset($firstOption['content'])) {
                    // 新格式：[{"label":"A","content":"xxx"}] -> {"A":"xxx"}
                    foreach ($question['options'] as $option) {
                        if (isset($option['label']) && isset($option['content'])) {
                            $normalizedOptions[$option['label']] = $option['content'];
                        }
                    }
                } else {
                    // 旧格式或其他格式
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
            }
            
            // 计算得分（从自动评分结果中获取，如果有的话）
            $earnedScore = 0;
            
            // 判断是否为选择题（兼容多种类型字段）
            $isChoiceQuestion = false;
            $isMultipleChoice = false;
            
            if ($question['type'] === 'choice') {
                $isChoiceQuestion = true;
                $isMultipleChoice = ($question['choice_type'] ?? '') === 'multiple' || 
                                   ($question['multiple'] ?? false);
            } elseif (in_array($question['type'], ['choice_single', 'choice_multiple'])) {
                $isChoiceQuestion = true;
                $isMultipleChoice = ($question['type'] === 'choice_multiple');
            }
            
            if ($isChoiceQuestion) {
                $correctAnswer = $question['correct_answer'] ?? [];
                $isCorrect = false;
                
                if ($isMultipleChoice) {
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

            // 使用前面已经计算好的多选题标识
            $questionType = $question['type'];
            if ($isChoiceQuestion) {
                $questionType = $isMultipleChoice ? 'choice_multiple' : 'choice_single';
            }
            
            // 设置参考答案，统一为answer字段（result.volt使用的字段名）
            $answer = '';
            if (isset($question['correct_answer'])) {
                if (is_array($question['correct_answer'])) {
                    $answer = implode(', ', $question['correct_answer']);
                } else {
                    $answer = $question['correct_answer'];
                }
            } elseif (isset($question['answer'])) {
                $answer = $question['answer'];
            }

            $result[] = [
                'id' => $questionId,
                'type' => $questionType,  // 统一类型字段
                'title' => $question['title'] ?? '',
                'content' => $question['content'] ?? '',
                'options' => $normalizedOptions,
                'correct_answer' => $question['correct_answer'] ?? [],
                'answer' => $answer,  // result.volt模板使用的字段
                'score' => $question['score'] ?? 0,
                'user_answer' => $userAnswer,
                'earned_score' => $earnedScore,
                'feedback' => '',
            ];
        }

        return $result;
    }

}


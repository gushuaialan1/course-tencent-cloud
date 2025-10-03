<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Assignment;

use App\Models\User as UserModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentQuestion as QuestionRepo;
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

        $submission = $submissionRepo->findSubmission($assignment->id, $user->id);

        $assignmentInfo = $this->handleAssignment($assignment);
        $submissionInfo = $this->handleSubmission($submission);
        $questions = $this->handleQuestionsWithAnswers($assignment->id, $submission);

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

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'total_score' => $assignment->total_score,
            'question_count' => $assignment->question_count,
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

        $answers = $submission->answers ? json_decode($submission->answers, true) : [];

        return [
            'id' => $submission->id,
            'score' => $submission->score,
            'status' => $submission->status,
            'answers' => $answers,
            'feedback' => $submission->feedback,
            'is_late' => $submission->is_late,
            'submitted_at' => $submission->submitted_at,
            'graded_at' => $submission->graded_at,
        ];
    }

    protected function handleQuestionsWithAnswers($assignmentId, $submission)
    {
        $questionRepo = new QuestionRepo();

        $questions = $questionRepo->findAll([
            'assignment_id' => $assignmentId,
            'deleted' => 0,
        ], ['priority' => 1], 100);

        $result = [];

        $userAnswers = [];
        $questionScores = [];

        if ($submission && $submission->answers) {
            $userAnswers = json_decode($submission->answers, true);
        }

        if ($submission && $submission->question_scores) {
            $questionScores = json_decode($submission->question_scores, true);
        }

        if ($questions->count() > 0) {
            foreach ($questions as $question) {
                $questionId = $question->id;

                $userAnswer = isset($userAnswers[$questionId]) ? $userAnswers[$questionId] : null;
                $earnedScore = isset($questionScores[$questionId]) ? $questionScores[$questionId] : 0;

                $result[] = [
                    'id' => $question->id,
                    'type' => $question->type,
                    'title' => $question->title,
                    'content' => $question->content,
                    'options' => $question->options ? json_decode($question->options, true) : [],
                    'answer' => $question->answer,
                    'score' => $question->score,
                    'user_answer' => $userAnswer,
                    'earned_score' => $earnedScore,
                    'feedback' => isset($questionScores[$questionId . '_feedback']) ? $questionScores[$questionId . '_feedback'] : '',
                ];
            }
        }

        return $result;
    }

}


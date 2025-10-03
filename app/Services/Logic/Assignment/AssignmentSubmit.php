<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Assignment;

use App\Library\Validators\Common as CommonValidator;
use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentQuestion as QuestionRepo;
use App\Repos\AssignmentSubmission as SubmissionRepo;
use App\Services\Logic\Service as LogicService;

class AssignmentSubmit extends LogicService
{

    public function handle($id)
    {
        $user = $this->getCurrentUser(true);

        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($id);

        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        if ($assignment->deleted == 1) {
            throw new \Exception('作业已删除');
        }

        if ($assignment->status != 'published') {
            throw new \Exception('作业未发布');
        }

        // 检查是否已截止
        if ($assignment->deadline > 0 && $assignment->deadline < time()) {
            if ($assignment->allow_resubmit == 0) {
                throw new \Exception('作业已截止，不允许提交');
            }
        }

        $post = $this->request->getPost();

        $validator = new CommonValidator();

        $answers = $validator->checkJsonField($post, 'answers');
        $answers = json_decode($answers, true);

        if (!$answers || !is_array($answers)) {
            throw new \Exception('请先完成作业');
        }

        // 验证必答题
        $this->checkRequiredQuestions($assignment->id, $answers);

        $submissionRepo = new SubmissionRepo();

        // 检查是否已有提交
        $submission = $submissionRepo->findSubmission($assignment->id, $user->id);

        $isLate = $assignment->deadline > 0 && time() > $assignment->deadline;

        if ($submission) {
            // 重新提交
            if ($assignment->allow_resubmit == 0) {
                throw new \Exception('不允许重新提交');
            }

            $submission->answers = json_encode($answers);
            $submission->status = 'pending';
            $submission->is_late = $isLate ? 1 : 0;
            $submission->submitted_at = time();
            $submission->update_time = time();

            $submission->update();
        } else {
            // 首次提交
            $submission = new SubmissionModel();

            $submission->assignment_id = $assignment->id;
            $submission->user_id = $user->id;
            $submission->course_id = $assignment->course_id;
            $submission->answers = json_encode($answers);
            $submission->status = 'pending';
            $submission->is_late = $isLate ? 1 : 0;
            $submission->submitted_at = time();
            $submission->create_time = time();
            $submission->update_time = time();

            $submission->create();
        }

        // 触发事件（用于发送通知）
        $this->eventsManager->fire('Assignment:afterSubmit', $this, $submission);

        return $submission;
    }

    protected function checkRequiredQuestions($assignmentId, $answers)
    {
        $questionRepo = new QuestionRepo();

        $questions = $questionRepo->findAll([
            'assignment_id' => $assignmentId,
            'required' => 1,
            'deleted' => 0,
        ]);

        if ($questions->count() == 0) {
            return;
        }

        $answeredIds = array_keys($answers);

        foreach ($questions as $question) {
            if (!in_array($question->id, $answeredIds)) {
                throw new \Exception("请完成必答题：{$question->title}");
            }

            $answer = $answers[$question->id];

            // 检查答案是否为空
            if ($question->type == 'choice_single' || $question->type == 'choice_multiple') {
                if (empty($answer)) {
                    throw new \Exception("请完成必答题：{$question->title}");
                }
            } elseif ($question->type == 'text' || $question->type == 'essay') {
                if (trim($answer) === '') {
                    throw new \Exception("请完成必答题：{$question->title}");
                }
            }
        }
    }

}


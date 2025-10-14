<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as SubmissionRepo;
use App\Services\Logic\Service as LogicService;

class AssignmentSubmit extends LogicService
{

    public function handle($id)
    {
        // 提交作业需要用户登录
        $user = $this->getLoginUser(true);

        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($id);

        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        if ($assignment->delete_time > 0) {
            throw new \Exception('作业已删除');
        }

        if ($assignment->status != 'published') {
            throw new \Exception('作业未发布');
        }

        // 检查是否已截止
        if ($assignment->due_date > 0 && $assignment->due_date < time()) {
            if ($assignment->allow_late == 0) {
                throw new \Exception('作业已截止，不允许迟交');
            }
        }

        $post = $this->request->getPost();

        // 获取并解析答案数据
        $answersJson = $post['answers'] ?? '';
        $answers = $answersJson ? json_decode($answersJson, true) : [];

        if (!$answers || !is_array($answers)) {
            throw new \Exception('请先完成作业');
        }

        // 验证必答题
        $this->checkRequiredQuestions($assignment->id, $answers);

        $submissionRepo = new SubmissionRepo();

        // 检查是否已有提交
        $submission = $submissionRepo->findByAssignmentAndUser($assignment->id, $user->id);

        $isLate = $assignment->due_date > 0 && time() > $assignment->due_date;

        if ($submission) {
            // 重新提交
            if ($assignment->allow_late == 0 && $isLate) {
                throw new \Exception('作业已截止，不允许迟交');
            }

            $submission->content = json_encode($answers);
            $submission->status = 'submitted'; // 已提交状态
            $submission->is_late = $isLate ? 1 : 0;
            $submission->submit_time = time();
            $submission->update_time = time();

            $submission->update();
        } else {
            // 首次提交
            $submission = new SubmissionModel();

            $submission->assignment_id = $assignment->id;
            $submission->user_id = $user->id;
            $submission->content = json_encode($answers);
            $submission->attachments = json_encode([]); // 初始化为空JSON数组
            $submission->grade_details = json_encode([]); // 初始化为空JSON数组
            $submission->status = 'submitted'; // 已提交状态
            $submission->is_late = $isLate ? 1 : 0;
            $submission->submit_time = time();
            $submission->grader_id = null; // 提交时未批改，设置为null
            $submission->create_time = time();
            $submission->update_time = time();

            $result = $submission->create();
            
            if (!$result) {
                $messages = $submission->getMessages();
                $errorMsg = !empty($messages) ? $messages[0]->getMessage() : '提交失败';
                throw new \Exception('作业提交失败: ' . $errorMsg);
            }
        }

        // 触发事件（用于发送通知）
        $this->eventsManager->fire('Assignment:afterSubmit', $this, $submission);

        return $submission;
    }

    protected function checkRequiredQuestions($assignmentId, $answers)
    {
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($assignmentId);
        
        if (!$assignment) {
            return;
        }

        // 使用模型的getContentData()方法解析题目
        $content = $assignment->getContentData();
        
        if (!is_array($content)) {
            return;
        }
        
        // 兼容两种数据结构
        if (isset($content['questions']) && is_array($content['questions'])) {
            $questions = $content['questions'];
        } else {
            $questions = $content;
        }
        
        if (!is_array($questions)) {
            return;
        }

        if (empty($questions)) {
            return;
        }

        $answeredIds = array_keys($answers);

        foreach ($questions as $question) {
            // 检查是否为必答题
            if (empty($question['required']) || $question['required'] != 1) {
                continue;
            }
            
            $questionId = $question['id'] ?? null;
            if (!$questionId) {
                continue;
            }

            if (!in_array($questionId, $answeredIds)) {
                throw new \Exception("请完成必答题：{$question['title']}");
            }

            $answer = $answers[$questionId];

            // 检查答案是否为空
            if ($question['type'] == 'choice' || $question['type'] == 'choice_single' || $question['type'] == 'choice_multiple') {
                if (empty($answer)) {
                    throw new \Exception("请完成必答题：{$question['title']}");
                }
            } elseif ($question['type'] == 'text' || $question['type'] == 'essay') {
                if (trim($answer) === '') {
                    throw new \Exception("请完成必答题：{$question['title']}");
                }
            }
        }
    }

}


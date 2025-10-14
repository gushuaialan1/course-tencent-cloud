<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Assignment;

use App\Models\AssignmentSubmission as SubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as SubmissionRepo;
use App\Services\Logic\Service as LogicService;

class SubmissionDraft extends LogicService
{

    public function handle($id)
    {
        $user = $this->getCurrentUser(true);

        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($id);

        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        $post = $this->request->getPost();

        // 获取答案数据（可能是JSON字符串）
        $answers = $post['answers'] ?? '';

        $submissionRepo = new SubmissionRepo();

        // 查找或创建草稿
        $submission = $submissionRepo->findByAssignmentAndUser($assignment->id, $user->id);

        if ($submission) {
            // 如果已提交，不允许保存草稿
            if ($submission->status != 'draft') {
                throw new \Exception('作业已提交，无法保存草稿');
            }

            $submission->content = $answers;
            $submission->update_time = time();

            $submission->update();
        } else {
            // 创建草稿
            $submission = new SubmissionModel();

            $submission->assignment_id = $assignment->id;
            $submission->user_id = $user->id;
            $submission->content = $answers;
            $submission->status = 'draft';
            $submission->grader_id = null; // 草稿状态，未批改
            $submission->create_time = time();
            $submission->update_time = time();

            $result = $submission->create();
            
            if (!$result) {
                $messages = $submission->getMessages();
                $errorMsg = !empty($messages) ? $messages[0]->getMessage() : '草稿创建失败';
                throw new \Exception('保存草稿失败: ' . $errorMsg);
            }
        }

        return $submission;
    }

}


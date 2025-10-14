<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\User\Console;

use App\Builders\AssignmentList as AssignmentListBuilder;
use App\Library\Paginator\Query as PagerQuery;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as SubmissionRepo;
use App\Services\Logic\Service as LogicService;

class AssignmentList extends LogicService
{

    public function handle()
    {
        $user = $this->getCurrentUser(true);

        $pagerQuery = new PagerQuery();

        $submissionRepo = new SubmissionRepo();

        $params = [
            'user_id' => $user->id,
        ];

        $sort = $pagerQuery->getSort();
        $page = $pagerQuery->getPage();
        $limit = $pagerQuery->getLimit();

        $status = $this->request->getQuery('status', 'trim');

        if ($status) {
            $params['status'] = $status;
        }

        $pager = $submissionRepo->paginate($params, $sort, $page, $limit);

        return $this->handleSubmissions($pager);
    }

    protected function handleSubmissions($pager)
    {
        if ($pager->total_items == 0) {
            return $pager;
        }

        $assignmentRepo = new AssignmentRepo();
        $submissionRepo = new SubmissionRepo();

        $items = [];

        foreach ($pager->items as $submission) {
            $assignment = $assignmentRepo->findById($submission->assignment_id);

            if (!$assignment || $assignment->delete_time > 0) {
                continue;
            }

            // 解析content获取题目数量
            $content = $assignment->content ? json_decode($assignment->content, true) : [];
            if (is_array($content)) {
                $questions = isset($content['questions']) ? $content['questions'] : $content;
            } else {
                $questions = [];
            }
            $questionCount = count($questions);

            $isOverdue = $assignment->due_date > 0 && $assignment->due_date < time();

            $items[] = [
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'course_id' => $assignment->course_id,
                    'due_date' => $assignment->due_date,
                    'max_score' => $assignment->max_score,
                    'question_count' => $questionCount,
                    'status' => $assignment->status,
                    'allow_late' => $assignment->allow_late,
                    'is_overdue' => $isOverdue,
                ],
                'submission' => [
                    'id' => $submission->id,
                    'score' => $submission->score,
                    'status' => $submission->status,
                    'grade_status' => $submission->grade_status,
                    'is_late' => $submission->is_late,
                    'submit_time' => $submission->submit_time,
                    'grade_time' => $submission->grade_time,
                ],
            ];
        }

        $pager->items = $items;

        return $pager;
    }

}


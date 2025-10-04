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

            if (!$assignment || $assignment->deleted == 1) {
                continue;
            }

            $answers = $submission->answers ? json_decode($submission->answers, true) : [];

            $isOverdue = $assignment->deadline > 0 && $assignment->deadline < time();

            $items[] = [
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'course_id' => $assignment->course_id,
                    'deadline' => $assignment->deadline,
                    'total_score' => $assignment->total_score,
                    'question_count' => $assignment->question_count,
                    'status' => $assignment->status,
                    'allow_resubmit' => $assignment->allow_resubmit,
                    'is_overdue' => $isOverdue,
                ],
                'submission' => [
                    'id' => $submission->id,
                    'score' => $submission->score,
                    'status' => $submission->status,
                    'is_late' => $submission->is_late,
                    'submitted_at' => $submission->submitted_at,
                    'graded_at' => $submission->graded_at,
                ],
            ];
        }

        $pager->items = $items;

        return $pager;
    }

}


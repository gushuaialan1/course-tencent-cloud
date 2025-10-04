<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Notice\Internal;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Models\Notification as NotificationModel;
use App\Models\User as UserModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Services\Logic\Service as LogicService;

class AssignmentGraded extends LogicService
{

    public function handle(SubmissionModel $submission, UserModel $sender)
    {
        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($submission->assignment_id);

        if (!$assignment) {
            return;
        }

        $notification = new NotificationModel();

        $notification->sender_id = $sender->id;
        $notification->receiver_id = $submission->user_id;
        $notification->event_id = $submission->id;
        $notification->event_type = NotificationModel::TYPE_ASSIGNMENT_GRADED;
        $notification->event_info = [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
            ],
            'submission' => [
                'id' => $submission->id,
                'score' => $submission->score,
                'total_score' => $assignment->total_score,
            ],
        ];

        $notification->create();
    }

}


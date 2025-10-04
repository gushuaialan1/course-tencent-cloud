<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Notice\Internal;

use App\Models\Assignment as AssignmentModel;
use App\Models\Notification as NotificationModel;
use App\Models\User as UserModel;
use App\Repos\Course as CourseRepo;
use App\Repos\CourseUser as CourseUserRepo;
use App\Services\Logic\Service as LogicService;

class AssignmentPublished extends LogicService
{

    public function handle(AssignmentModel $assignment, UserModel $sender)
    {
        $courseUserRepo = new CourseUserRepo();

        // 获取该课程的所有学生
        $courseUsers = $courseUserRepo->findAll([
            'course_id' => $assignment->course_id,
            'deleted' => 0,
        ]);

        if ($courseUsers->count() == 0) {
            return;
        }

        $courseRepo = new CourseRepo();
        $course = $courseRepo->findById($assignment->course_id);

        foreach ($courseUsers as $courseUser) {
            // 不给自己发通知
            if ($courseUser->user_id == $sender->id) {
                continue;
            }

            $notification = new NotificationModel();

            $notification->sender_id = $sender->id;
            $notification->receiver_id = $courseUser->user_id;
            $notification->event_id = $assignment->id;
            $notification->event_type = NotificationModel::TYPE_ASSIGNMENT_PUBLISHED;
            $notification->event_info = [
                'course' => ['id' => $course->id, 'title' => $course->title],
                'assignment' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'deadline' => $assignment->deadline,
                ],
            ];

            $notification->create();
        }
    }

}


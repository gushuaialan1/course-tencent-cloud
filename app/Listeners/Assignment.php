<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Listeners;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Services\Logic\Notice\Internal\AssignmentGraded as AssignmentGradedNotice;
use App\Services\Logic\Notice\Internal\AssignmentPublished as AssignmentPublishedNotice;
use Phalcon\Events\Event as PhEvent;

class Assignment extends Listener
{

    /**
     * 作业发布后
     */
    public function afterPublish(PhEvent $event, $source, AssignmentModel $assignment)
    {
        $user = $this->getLoginUser();

        $notice = new AssignmentPublishedNotice();

        $notice->handle($assignment, $user);
    }

    /**
     * 作业提交后
     */
    public function afterSubmit(PhEvent $event, $source, SubmissionModel $submission)
    {
        // 提交作业后可以发送通知给教师，这里先不实现
    }

    /**
     * 作业批改后
     */
    public function afterGrade(PhEvent $event, $source, SubmissionModel $submission)
    {
        $user = $this->getLoginUser();

        $notice = new AssignmentGradedNotice();

        $notice->handle($submission, $user);
    }

}


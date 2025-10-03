<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Home\Controllers;

use App\Services\Logic\Assignment\AssignmentInfo as AssignmentInfoService;
use App\Services\Logic\Assignment\AssignmentSubmit as AssignmentSubmitService;
use App\Services\Logic\Assignment\SubmissionDraft as SubmissionDraftService;
use App\Services\Logic\Assignment\SubmissionResult as SubmissionResultService;
use Phalcon\Mvc\View;

/**
 * @RoutePrefix("/assignment")
 */
class AssignmentController extends Controller
{

    /**
     * @Get("/{id:[0-9]+}", name="home.assignment.show")
     */
    public function showAction($id)
    {
        $service = new AssignmentInfoService();

        $assignment = $service->handle($id);

        if ($assignment['deleted'] == 1) {
            $this->notFound();
        }

        // 检查是否有提交记录（已批改的）
        if ($assignment['submission'] && $assignment['submission']['status'] == 'graded') {
            // 跳转到成绩查看页
            $location = $this->url->get(['for' => 'home.assignment.result', 'id' => $id]);
            return $this->response->redirect($location);
        }

        $this->seo->prependTitle(['作业', $assignment['title']]);
        $this->seo->setDescription($assignment['description']);

        $this->view->setVar('assignment', $assignment);
    }

    /**
     * @Get("/{id:[0-9]+}/result", name="home.assignment.result")
     */
    public function resultAction($id)
    {
        $service = new SubmissionResultService();

        $result = $service->handle($id);

        if (!$result['submission']) {
            $this->flashSession->error('您还未提交此作业');
            $location = $this->url->get(['for' => 'home.assignment.show', 'id' => $id]);
            return $this->response->redirect($location);
        }

        if ($result['submission']['status'] != 'graded') {
            $this->flashSession->info('作业批改中，请耐心等待');
            $location = $this->url->get(['for' => 'home.assignment.show', 'id' => $id]);
            return $this->response->redirect($location);
        }

        $this->seo->prependTitle(['作业成绩', $result['assignment']['title']]);

        $this->view->setVar('assignment', $result['assignment']);
        $this->view->setVar('submission', $result['submission']);
        $this->view->setVar('questions', $result['questions']);
    }

    /**
     * @Post("/{id:[0-9]+}/submit", name="home.assignment.submit")
     */
    public function submitAction($id)
    {
        $service = new AssignmentSubmitService();

        $submission = $service->handle($id);

        $location = $this->url->get(['for' => 'home.assignment.result', 'id' => $id]);

        $content = [
            'location' => $location,
            'msg' => '提交作业成功，请等待老师批改',
        ];

        return $this->jsonSuccess($content);
    }

    /**
     * @Post("/{id:[0-9]+}/draft", name="home.assignment.draft")
     */
    public function draftAction($id)
    {
        $service = new SubmissionDraftService();

        $service->handle($id);

        return $this->jsonSuccess(['msg' => '草稿保存成功']);
    }

}


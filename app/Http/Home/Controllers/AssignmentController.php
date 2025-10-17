<?php
/**
 * 作业前台控制器 - 完全重写版本
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Home\Controllers;

use App\Models\Assignment as AssignmentModel;
use App\Services\Assignment\AssignmentService;
use App\Services\Assignment\SubmissionService;
use App\Services\Assignment\StatisticsService;

/**
 * @RoutePrefix("/home/assignment")
 */
class AssignmentController extends Controller
{
    protected $assignmentService;
    protected $submissionService;
    protected $statisticsService;

    public function initialize()
    {
        parent::initialize();
        
        $this->assignmentService = new AssignmentService();
        $this->submissionService = new SubmissionService();
        $this->statisticsService = new StatisticsService();
    }

    /**
     * @Get("/list", name="home.assignment.list")
     */
    public function listAction()
    {
        try {
            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15)));
            $courseId = $this->request->getQuery('course_id', 'int');

            $params = [];
            if ($courseId) {
                $params['course_id'] = $courseId;
            }

            // 获取作业列表
            $result = $this->assignmentService->getList(array_merge($params, [
                'status' => 'published',  // 只显示已发布的
                'page' => $page,
                'limit' => $limit
            ]));

            // 为每个作业添加用户的提交状态
            $userId = $this->authUser->id ?? 0;
            if ($userId) {
                foreach ($result['assignments'] as &$assignment) {
                    $submission = $this->submissionService->getSubmission($assignment['id'], $userId);
                    $assignment['user_submission'] = $submission;
                }
            }

            if ($this->request->isAjax()) {
                return $this->jsonSuccess($result);
            }

            $this->view->setVars([
                'assignments' => $result['assignments'],
                'pager' => $result['pager']
            ]);
            
            return $this->view->pick('assignment/list');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取作业列表失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取作业列表失败: ' . $e->getMessage());
            return $this->response->redirect('/home/index/index');
        }
    }

    /**
     * @Get("/show/{id:[0-9]+}", name="home.assignment.show")
     */
    public function showAction($id)
    {
        try {
            // 获取作业详情
            $assignment = $this->assignmentService->getDetail($id);
            
            if (!$assignment) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '作业不存在']);
                }
                $this->flashSession->error('作业不存在');
                return $this->response->redirect('/home/course/index');
            }

            // 检查作业是否已发布
            if ($assignment['status'] !== 'published') {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '作业未发布']);
                }
                $this->flashSession->error('作业未发布');
                return $this->response->redirect('/home/course/index');
            }

            // 获取用户的提交记录
            $userId = $this->authUser->id ?? 0;
            $submissionData = null;
            $canSubmit = ['allowed' => false, 'reason' => ''];
            
            if ($userId) {
                $submission = $this->submissionService->getSubmission($id, $userId);
                if ($submission) {
                    $submissionData = $submission->toArray();
                    // 确保所有JSON字段都是数组格式
                    if (isset($submissionData['content']) && !is_array($submissionData['content'])) {
                        $submissionData['content'] = json_decode(json_encode($submissionData['content']), true);
                    }
                    if (isset($submissionData['grade_details']) && !is_array($submissionData['grade_details'])) {
                        $submissionData['grade_details'] = json_decode(json_encode($submissionData['grade_details']), true);
                    }
                    if (isset($submissionData['attachments']) && !is_array($submissionData['attachments'])) {
                        $submissionData['attachments'] = json_decode(json_encode($submissionData['attachments']), true);
                    }
                }
                $canSubmit = $this->submissionService->canSubmit($id, $userId);
            }
            
            // 将 submission 数据附加到 assignment 中，方便视图使用
            $assignment['submission'] = $submissionData;

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'assignment' => $assignment,
                    'submission' => $submissionData,
                    'can_submit' => $canSubmit
                ]);
            }

            $this->view->setVars([
                'assignment' => $assignment,
                'can_submit' => $canSubmit
            ]);
            
            return $this->view->pick('assignment/show');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取作业详情失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取作业详情失败: ' . $e->getMessage());
            return $this->response->redirect('/home/course/index');
        }
    }

    /**
     * @Post("/save-draft/{id:[0-9]+}", name="home.assignment.save_draft")
     * @Post("/draft/{id:[0-9]+}", name="home.assignment.draft")
     */
    public function saveDraftAction($id)
    {
        try {
            $userId = $this->authUser->id ?? 0;
            
            if (!$userId) {
                return $this->jsonError(['msg' => '请先登录']);
            }

            // 获取答案数据
            $answers = $this->request->getPost('answers');
            
            // 如果是JSON字符串，解析它
            if (is_string($answers)) {
                $answers = json_decode($answers, true);
            }

            // 保存草稿
            $submission = $this->submissionService->saveAsDraft($id, $userId, $answers, [
                'ip' => $this->request->getClientAddress(),
                'user_agent' => $this->request->getUserAgent()
            ]);

            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'msg' => '草稿保存成功'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '保存草稿失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/submit/{id:[0-9]+}", name="home.assignment.submit")
     */
    public function submitAction($id)
    {
        try {
            $userId = $this->authUser->id ?? 0;
            
            if (!$userId) {
                return $this->jsonError(['msg' => '请先登录']);
            }

            // 获取答案数据
            $answers = $this->request->getPost('answers');
            
            // 如果是JSON字符串，解析它
            if (is_string($answers)) {
                $answers = json_decode($answers, true);
            }

            // 提交作业
            $result = $this->submissionService->submit($id, $userId, $answers, [
                'submit_ip' => $this->request->getClientAddress(),
                'user_agent' => $this->request->getUserAgent()
            ]);

            $message = '作业提交成功';
            if ($result['auto_graded']) {
                if ($result['all_graded']) {
                    $message = '作业提交成功，自动评分已完成';
                } else {
                    $message = '作业提交成功，部分题目已自动评分，其余等待教师批改';
                }
            } else {
                $message = '作业提交成功，等待教师批改';
            }

            return $this->jsonSuccess([
                'submission' => $result['submission']->toArray(),
                'auto_graded' => $result['auto_graded'],
                'all_graded' => $result['all_graded'],
                'score' => $result['submission']->score,
                'msg' => $message
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '提交失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/result/{id:[0-9]+}", name="home.assignment.result")
     */
    public function resultAction($id)
    {
        try {
            $userId = $this->authUser->id ?? 0;
            
            if (!$userId) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '请先登录']);
                }
                $this->flashSession->error('请先登录');
                return $this->response->redirect('/home/auth/login');
            }

            // 获取提交记录
            $submission = $this->submissionService->getSubmission($id, $userId);
            
            if (!$submission) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '未找到提交记录']);
                }
                $this->flashSession->error('未找到提交记录');
                return $this->response->redirect('/home/assignment/detail/' . $id);
            }

            // 获取作业信息
            $assignment = $this->assignmentService->getDetail($submission['assignment_id']);

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'assignment' => $assignment,
                    'submission' => $submission
                ]);
            }

            $this->view->setVars([
                'assignment' => $assignment,
                'submission' => $submission
            ]);
            
            return $this->view->pick('assignment/result');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取结果失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取结果失败: ' . $e->getMessage());
            return $this->response->redirect('/home/course/index');
        }
    }

    /**
     * @Get("/my-submissions", name="home.assignment.my_submissions")
     */
    public function mySubmissionsAction()
    {
        try {
            $userId = $this->authUser->id ?? 0;
            
            if (!$userId) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '请先登录']);
                }
                $this->flashSession->error('请先登录');
                return $this->response->redirect('/home/auth/login');
            }

            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15)));
            $courseId = $this->request->getQuery('course_id', 'int');

            $submissionRepo = new \App\Repos\AssignmentSubmission();
            
            $options = [
                'user_id' => $userId,
                'limit' => $limit,
                'offset' => ($page - 1) * $limit
            ];

            if ($courseId) {
                $options['course_id'] = $courseId;
            }

            $submissions = $submissionRepo->findAll($options);
            $total = $submissionRepo->countAll(array_diff_key($options, ['limit' => '', 'offset' => '']));

            // 为每个提交添加作业信息
            foreach ($submissions as &$submission) {
                $assignment = $this->assignmentService->getDetail($submission['assignment_id']);
                $submission['assignment'] = $assignment;
            }

            $pager = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'submissions' => $submissions,
                    'pager' => $pager
                ]);
            }

            $this->view->setVars([
                'submissions' => $submissions,
                'pager' => $pager
            ]);
            
            return $this->view->pick('assignment/my_submissions');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取我的提交失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取我的提交失败: ' . $e->getMessage());
            return $this->response->redirect('/home/index/index');
        }
    }
}

<?php
/**
 * 作业提交管理后台控制器 - 完全重写版本
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Admin\Controllers;

use App\Models\AssignmentSubmission as SubmissionModel;
use App\Services\Assignment\GradingService;
use App\Services\Assignment\AssignmentService;
use App\Validators\Assignment\SubmissionValidator;

/**
 * @RoutePrefix("/admin/assignment/submission")
 */
class AssignmentSubmissionController extends Controller
{
    protected $gradingService;
    protected $assignmentService;
    protected $validator;

    public function initialize()
    {
        parent::initialize();
        
        $this->gradingService = new GradingService();
        $this->assignmentService = new AssignmentService();
        $this->validator = new SubmissionValidator();
    }

    /**
     * @Get("/", name="admin.assignment.submission.index")
     */
    public function indexAction()
    {
        return $this->response->redirect('/admin/assignment/submission/list');
    }

    /**
     * @Get("/list", name="admin.assignment.submission.list")
     */
    public function listAction()
    {
        try {
            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15)));
            
            $assignmentId = $this->request->getQuery('assignment_id', 'int');
            $userId = $this->request->getQuery('user_id', 'int');
            $status = $this->request->getQuery('status', 'string'); // 使用新的status字段
            $isLate = $this->request->getQuery('is_late', 'int');

            $submissionRepo = new \App\Repos\AssignmentSubmission();
            
            $options = [
                'limit' => $limit,
                'offset' => ($page - 1) * $limit
            ];

            if ($assignmentId) {
                $options['assignment_id'] = $assignmentId;
            }
            if ($userId) {
                $options['user_id'] = $userId;
            }
            if ($status) {
                $options['status'] = $status;  // 只使用status，不再用grade_status
            }
            if (isset($isLate)) {
                $options['is_late'] = $isLate;
            }

            $submissions = $submissionRepo->findAll($options);
            $total = $submissionRepo->countAll(array_diff_key($options, ['limit' => '', 'offset' => '']));

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

            // 获取作业信息
            $assignment = null;
            if ($assignmentId) {
                $assignment = $this->assignmentService->getDetail($assignmentId);
            }

            $this->view->setVars([
                'submissions' => $submissions,
                'pager' => $pager,
                'assignment' => $assignment,
                'statuses' => SubmissionModel::getStatuses()
            ]);
            
            return $this->view->pick('assignment/submission/list');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取提交列表失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取提交列表失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Get("/grading-queue", name="admin.assignment.submission.grading_queue")
     * @Get("/grading-list", name="admin.assignment.grading.list")
     */
    public function gradingQueueAction()
    {
        try {
            // 筛选条件
            $assignmentId = $this->request->getQuery('assignment_id', 'int');
            $courseId = $this->request->getQuery('course_id', 'int');
            $status = $this->request->getQuery('status', 'string');
            $isLate = $this->request->getQuery('is_late', 'int');
            
            // 构建查询条件
            $conditions = ['delete_time = 0'];
            $bind = [];
            
            if ($assignmentId) {
                $conditions[] = 'assignment_id = :assignment_id:';
                $bind['assignment_id'] = $assignmentId;
            }
            
            if ($status) {
                $conditions[] = 'status = :status:';
                $bind['status'] = $status;
            }
            
            // 获取提交列表
            $submissions = \App\Models\AssignmentSubmission::find([
                'conditions' => implode(' AND ', $conditions),
                'bind' => $bind,
                'order' => 'id DESC',
                'limit' => 100
            ]);
            
            // 转换为数组并附加相关信息
            $submissionsData = [];
            foreach ($submissions as $submission) {
                $data = $submission->toArray();
                
                // 获取作业信息
                $assignment = \App\Models\Assignment::findFirst($submission->assignment_id);
                if ($assignment) {
                    $data['assignment_title'] = $assignment->title;
                    $data['course_id'] = $assignment->course_id;
                }
                
                // 获取用户信息
                $user = \App\Models\User::findFirst($submission->user_id);
                if ($user) {
                    $data['user_name'] = $user->name;
                }
                
                $submissionsData[] = $data;
            }
            
            // 如果指定了课程筛选，过滤结果
            if ($courseId) {
                $submissionsData = array_filter($submissionsData, function($item) use ($courseId) {
                    return isset($item['course_id']) && $item['course_id'] == $courseId;
                });
            }
            
            // 获取课程列表用于筛选
            $courses = \App\Models\Course::find([
                'conditions' => 'deleted = 0 AND published = 1',
                'order' => 'id DESC'
            ]);
            
            // 获取作业列表用于筛选
            $assignments = \App\Models\Assignment::find([
                'conditions' => 'delete_time = 0',
                'order' => 'id DESC',
                'limit' => 100
            ]);

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'submissions' => $submissionsData
                ]);
            }

            $this->view->setVars([
                'submissions' => $submissionsData,
                'pager' => null, // 简化版本不分页
                'courses' => $courses,
                'assignments' => $assignments,
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'status' => $status,
                'is_late' => $isLate
            ]);
            
            return $this->view->pick('assignment/grading-list');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取批改列表失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取批改列表失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Get("/detail/{id:[0-9]+}", name="admin.assignment.submission.detail")
     */
    public function detailAction($id)
    {
        try {
            $submissionRepo = new \App\Repos\AssignmentSubmission();
            $submission = $submissionRepo->findById($id);
            
            if (!$submission) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '提交记录不存在']);
                }
                $this->flashSession->error('提交记录不存在');
                return $this->response->redirect('/admin/assignment/list');
            }

            // 获取作业信息
            $assignment = $this->assignmentService->getDetail($submission['assignment_id']);

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'submission' => $submission,
                    'assignment' => $assignment
                ]);
            }

            $this->view->setVars([
                'submission' => $submission,
                'assignment' => $assignment
            ]);
            
            return $this->view->pick('assignment/submission/detail');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取提交详情失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取提交详情失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Get("/grade/{id:[0-9]+}", name="admin.assignment.submission.grade")
     */
    public function gradeAction($id)
    {
        try {
            $submissionRepo = new \App\Repos\AssignmentSubmission();
            $submission = $submissionRepo->findById($id);
            
            if (!$submission) {
                $this->flashSession->error('提交记录不存在');
                return $this->response->redirect('/admin/assignment/list');
            }

            // 获取作业信息
            $assignment = $this->assignmentService->getDetail($submission['assignment_id']);

            $this->view->setVars([
                'submission' => $submission,
                'assignment' => $assignment
            ]);
            
            return $this->view->pick('assignment/submission/grade');
            
        } catch (\Exception $e) {
            $this->flashSession->error('加载批改页面失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Post("/grade", name="admin.assignment.submission.do_grade")
     */
    public function doGradeAction()
    {
        try {
            $submissionId = (int)$this->request->getPost('submission_id', 'int');
            $grading = $this->request->getPost('grading');  // 各题评分
            $feedback = $this->request->getPost('feedback', 'string', '');  // 总体反馈

            // grading可能是JSON字符串
            if (is_string($grading)) {
                $grading = json_decode($grading, true);
            }

            // 验证数据
            $validation = $this->validator->validateGrading([
                'submission_id' => $submissionId,
                'grading' => $grading
            ]);

            if (!$validation['valid']) {
                return $this->jsonError([
                    'msg' => '数据验证失败',
                    'errors' => $validation['errors']
                ]);
            }

            // 执行批改
            $submission = $this->gradingService->manualGrade($submissionId, $grading, $feedback);

            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'msg' => '批改完成'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '批改失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/return/{id:[0-9]+}", name="admin.assignment.submission.return")
     */
    public function returnAction($id)
    {
        try {
            $reason = $this->request->getPost('reason', 'string', '需要重新提交');

            // 退回作业
            $submission = $this->gradingService->returnSubmission($id, $reason);

            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'msg' => '作业已退回'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '退回失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/auto-grade/{id:[0-9]+}", name="admin.assignment.submission.auto_grade")
     */
    public function autoGradeAction($id)
    {
        try {
            // 执行自动评分
            $result = $this->gradingService->autoGrade($id);

            return $this->jsonSuccess([
                'result' => $result,
                'msg' => '自动评分完成'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '自动评分失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/export", name="admin.assignment.submission.export")
     */
    public function exportAction()
    {
        try {
            $assignmentId = $this->request->getQuery('assignment_id', 'int');
            
            if (!$assignmentId) {
                return $this->jsonError(['msg' => '缺少作业ID']);
            }

            // TODO: 实现导出功能
            return $this->jsonError(['msg' => '导出功能开发中']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '导出失败: ' . $e->getMessage()]);
        }
    }
}

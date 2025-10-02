<?php
/**
 * 作业管理后台控制器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Admin\Controllers;

use App\Models\Assignment as AssignmentModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as AssignmentSubmissionRepo;
use App\Repos\Course as CourseRepo;
use App\Validators\Assignment as AssignmentValidator;

/**
 * @RoutePrefix("/admin/assignment")
 */
class AssignmentController extends Controller
{
    /**
     * @Get("/", name="admin.assignment.index")
     */
    public function indexAction()
    {
        // 作业列表页面
    }

    /**
     * @Get("/list", name="admin.assignment.list")
     */
    public function listAction()
    {
        try {
            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15)));
            $courseId = $this->request->getQuery('course_id', 'int');
            $status = $this->request->getQuery('status', 'string');
            $type = $this->request->getQuery('type', 'string');
            $title = $this->request->getQuery('title', 'string');

            $assignmentRepo = new AssignmentRepo();
            
            $options = [
                'limit' => $limit,
                'offset' => ($page - 1) * $limit
            ];

            if ($courseId) {
                $options['course_id'] = $courseId;
            }
            if ($status) {
                $options['status'] = $status;
            }
            if ($type) {
                $options['type'] = $type;
            }
            if ($title) {
                $options['title'] = $title;
            }

            $assignments = $assignmentRepo->findAll($options);
            
            // 获取总数用于分页
            $totalOptions = array_diff_key($options, ['limit' => '', 'offset' => '']);
            $total = $assignmentRepo->countAll($totalOptions);

            // 获取提交统计
            $submissionRepo = new AssignmentSubmissionRepo();
            foreach ($assignments as &$assignment) {
                try {
                    $stats = $submissionRepo->getStatistics(['assignment_id' => $assignment['id']]);
                    $assignment['submission_stats'] = $stats;
                } catch (\Exception $e) {
                    $assignment['submission_stats'] = [
                        'total' => 0,
                        'graded' => 0,
                        'pending' => 0
                    ];
                }
            }

            // 获取课程列表用于筛选
            $courseRepo = new CourseRepo();
            $courses = $courseRepo->findAll(['published' => 1, 'deleted' => 0]);

            $pager = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ];

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'assignments' => $assignments,
                    'pager' => $pager
                ]);
            }

            $this->view->setVars([
                'assignments' => $assignments,
                'pager' => $pager,
                'courses' => $courses,
                'course_id' => $courseId,
                'status' => $status,
                'type' => $type,
                'title' => $title,
                'assignment_types' => AssignmentModel::getTypes(),
                'assignment_statuses' => AssignmentModel::getStatuses()
            ]);
            
            return $this->view->pick('assignment/list');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取作业列表失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取作业列表失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/index/index');
        }
    }

    /**
     * @Get("/search", name="admin.assignment.search")
     */
    public function searchAction()
    {
        $title = $this->request->getQuery('title', 'string');
        $courseId = $this->request->getQuery('course_id', 'int');
        
        if (empty($title)) {
            return $this->jsonSuccess(['assignments' => []]);
        }

        $assignmentRepo = new AssignmentRepo();
        
        // 这里需要实现根据标题搜索的方法
        $options = [
            'limit' => 20,
            'title' => $title
        ];
        
        if ($courseId) {
            $options['course_id'] = $courseId;
        }

        $assignments = $assignmentRepo->findByCourseId($courseId ?: 0, $options);

        return $this->jsonSuccess(['assignments' => $assignments]);
    }

    /**
     * @Get("/create", name="admin.assignment.create")
     */
    public function createAction()
    {
        try {
            $courseRepo = new CourseRepo();
            $courses = $courseRepo->findAll(['published' => 1, 'deleted' => 0]);

            $this->view->setVars([
                'courses' => $courses,
                'assignment_types' => AssignmentModel::getTypes(),
                'grade_modes' => AssignmentModel::getGradeModes(),
                'assignment_statuses' => AssignmentModel::getStatuses()
            ]);
            
            return $this->view->pick('assignment/create');
            
        } catch (\Exception $e) {
            $this->flashSession->error('加载创建页面失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Post("/create", name="admin.assignment.do_create")
     */
    public function doCreateAction()
    {
        $postData = $this->request->getPost();
        
        $validator = new AssignmentValidator();
        $validator->validate($postData);
        
        if ($validator->hasError()) {
            return $this->jsonError(['message' => $validator->getFirstError()]);
        }

        $assignmentRepo = new AssignmentRepo();
        
        $data = [
            'title' => $postData['title'],
            'description' => $postData['description'] ?? '',
            'course_id' => $postData['course_id'],
            'chapter_id' => $postData['chapter_id'] ?? 0,
            'assignment_type' => $postData['assignment_type'] ?? AssignmentModel::TYPE_MIXED,
            'max_score' => $postData['max_score'] ?? 100.00,
            'due_date' => $postData['due_date'] ?? 0,
            'allow_late' => $postData['allow_late'] ?? 0,
            'late_penalty' => $postData['late_penalty'] ?? 0.00,
            'grade_mode' => $postData['grade_mode'] ?? AssignmentModel::GRADE_MODE_MANUAL,
            'instructions' => $postData['instructions'] ?? '',
            'max_attempts' => $postData['max_attempts'] ?? 1,
            'time_limit' => $postData['time_limit'] ?? 0,
            'status' => $postData['status'] ?? AssignmentModel::STATUS_DRAFT,
            'owner_id' => $this->authUser->id
        ];

        // 处理JSON字段
        if (!empty($postData['attachments'])) {
            $data['attachments'] = json_decode($postData['attachments'], true);
        }
        if (!empty($postData['rubric'])) {
            $data['rubric'] = json_decode($postData['rubric'], true);
        }
        if (!empty($postData['content'])) {
            $data['content'] = json_decode($postData['content'], true);
        }
        if (!empty($postData['reference_answer'])) {
            $data['reference_answer'] = json_decode($postData['reference_answer'], true);
        }
        if (!empty($postData['visibility'])) {
            $data['visibility'] = json_decode($postData['visibility'], true);
        }

        try {
            $assignment = $assignmentRepo->create($data);
            
            return $this->jsonSuccess([
                'assignment' => $assignment->toArray(),
                'message' => '作业创建成功'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业创建失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/edit/{id:[0-9]+}", name="admin.assignment.edit")
     */
    public function editAction()
    {
        try {
            $id = $this->dispatcher->getParam('id');
            
            $assignmentRepo = new AssignmentRepo();
            $assignment = $assignmentRepo->findById($id);
            
            if (!$assignment) {
                $this->flashSession->error('作业不存在');
                return $this->response->redirect('/admin/assignment/list');
            }

            $courseRepo = new CourseRepo();
            $courses = $courseRepo->findAll(['published' => 1, 'deleted' => 0]);

            $this->view->setVars([
                'assignment' => $assignment,
                'courses' => $courses,
                'assignment_types' => AssignmentModel::getTypes(),
                'grade_modes' => AssignmentModel::getGradeModes(),
                'assignment_statuses' => AssignmentModel::getStatuses()
            ]);
            
            return $this->view->pick('assignment/edit');
            
        } catch (\Exception $e) {
            $this->flashSession->error('加载编辑页面失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Post("/update", name="admin.assignment.update")
     */
    public function updateAction()
    {
        $id = $this->request->getPost('id', 'int');
        $postData = $this->request->getPost();
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        try {
            // 处理JSON字段
            if (!empty($postData['attachments'])) {
                $postData['attachments'] = json_decode($postData['attachments'], true);
            }
            if (!empty($postData['rubric'])) {
                $postData['rubric'] = json_decode($postData['rubric'], true);
            }
            if (!empty($postData['content'])) {
                $postData['content'] = json_decode($postData['content'], true);
            }
            if (!empty($postData['reference_answer'])) {
                $postData['reference_answer'] = json_decode($postData['reference_answer'], true);
            }
            if (!empty($postData['visibility'])) {
                $postData['visibility'] = json_decode($postData['visibility'], true);
            }

            $assignmentRepo->update($assignment, $postData);
            
            return $this->jsonSuccess([
                'assignment' => $assignment->toArray(),
                'message' => '作业更新成功'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业更新失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/delete", name="admin.assignment.delete")
     */
    public function deleteAction()
    {
        $id = $this->request->getPost('id', 'int');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        try {
            $assignmentRepo->delete($assignment);
            return $this->jsonSuccess(['message' => '作业删除成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/publish", name="admin.assignment.publish")
     */
    public function publishAction()
    {
        $id = $this->request->getPost('id', 'int');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        try {
            $assignmentRepo->publish($assignment);
            return $this->jsonSuccess(['message' => '作业发布成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业发布失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/close", name="admin.assignment.close")
     */
    public function closeAction()
    {
        $id = $this->request->getPost('id', 'int');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        try {
            $assignmentRepo->close($assignment);
            return $this->jsonSuccess(['message' => '作业关闭成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业关闭失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/duplicate", name="admin.assignment.duplicate")
     */
    public function duplicateAction()
    {
        $id = $this->request->getPost('id', 'int');
        $title = $this->request->getPost('title', 'string');
        $courseId = $this->request->getPost('course_id', 'int');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        try {
            $overrideData = [
                'owner_id' => $this->authUser->id
            ];
            
            if ($title) {
                $overrideData['title'] = $title;
            }
            if ($courseId) {
                $overrideData['course_id'] = $courseId;
            }

            $newAssignment = $assignmentRepo->duplicate($assignment, $overrideData);
            
            return $this->jsonSuccess([
                'assignment' => $newAssignment->toArray(),
                'message' => '作业复制成功'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业复制失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/batch", name="admin.assignment.batch")
     */
    public function batchAction()
    {
        $action = $this->request->getPost('action', 'string');
        $ids = $this->request->getPost('ids', 'string');
        
        if (empty($action) || empty($ids)) {
            return $this->jsonError(['message' => '参数错误']);
        }

        $assignmentIds = explode(',', $ids);
        $assignmentIds = array_map('intval', $assignmentIds);
        $assignmentIds = array_filter($assignmentIds);

        if (empty($assignmentIds)) {
            return $this->jsonError(['message' => '请选择要操作的作业']);
        }

        $assignmentRepo = new AssignmentRepo();

        try {
            switch ($action) {
                case 'publish':
                    $affectedRows = $assignmentRepo->batchUpdateStatus($assignmentIds, AssignmentModel::STATUS_PUBLISHED);
                    $message = "成功发布{$affectedRows}个作业";
                    break;
                    
                case 'close':
                    $affectedRows = $assignmentRepo->batchUpdateStatus($assignmentIds, AssignmentModel::STATUS_CLOSED);
                    $message = "成功关闭{$affectedRows}个作业";
                    break;
                    
                case 'archive':
                    $affectedRows = $assignmentRepo->batchUpdateStatus($assignmentIds, AssignmentModel::STATUS_ARCHIVED);
                    $message = "成功归档{$affectedRows}个作业";
                    break;
                    
                default:
                    return $this->jsonError(['message' => '不支持的操作']);
            }

            return $this->jsonSuccess([
                'affected_rows' => $affectedRows,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '批量操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/stats", name="admin.assignment.stats")
     */
    public function statsAction()
    {
        $courseId = $this->request->getQuery('course_id', 'int');
        $startTime = $this->request->getQuery('start_time', 'int');
        $endTime = $this->request->getQuery('end_time', 'int');

        $assignmentRepo = new AssignmentRepo();
        
        $options = [];
        if ($courseId) {
            $options['course_id'] = $courseId;
        }
        if ($startTime) {
            $options['start_time'] = $startTime;
        }
        if ($endTime) {
            $options['end_time'] = $endTime;
        }

        $stats = $assignmentRepo->getStatistics($options);

        // 获取即将到期的作业
        $upcomingDue = $assignmentRepo->getUpcomingDue(24);

        // 获取已过期的作业
        $overdue = $assignmentRepo->getOverdue(['limit' => 10]);

        return $this->jsonSuccess([
            'statistics' => $stats,
            'upcoming_due' => $upcomingDue,
            'overdue' => $overdue
        ]);
    }

    /**
     * @Get("/show/{id:[0-9]+}", name="admin.assignment.show")
     */
    public function showAction()
    {
        $id = $this->dispatcher->getParam('id');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            $this->notFound();
            return;
        }

        // 获取提交统计
        $submissionRepo = new AssignmentSubmissionRepo();
        $submissionStats = $submissionRepo->getStatistics(['assignment_id' => $id]);

        $this->view->setVar('assignment', $assignment);
        $this->view->setVar('submission_stats', $submissionStats);
    }

    /**
     * 批改列表页面
     * @Get("/grading/list", name="admin.assignment.grading.list")
     */
    public function gradingListAction()
    {
        try {
            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15)));
            $courseId = $this->request->getQuery('course_id', 'int');
            $assignmentId = $this->request->getQuery('assignment_id', 'int');
            $gradeStatus = $this->request->getQuery('grade_status', 'string');
            $isLate = $this->request->getQuery('is_late', 'int');

            $submissionRepo = new AssignmentSubmissionRepo();
            
            $options = [
                'limit' => $limit,
                'offset' => ($page - 1) * $limit,
                'status' => \App\Models\AssignmentSubmission::STATUS_SUBMITTED
            ];

            if ($courseId) {
                $options['course_id'] = $courseId;
            }
            if ($assignmentId) {
                $options['assignment_id'] = $assignmentId;
            }
            if ($gradeStatus) {
                $options['grade_status'] = $gradeStatus;
            }
            if ($isLate !== null && $isLate !== '') {
                $options['is_late'] = $isLate;
            }

            // 获取待批改列表（包含作业和用户信息）
            $submissions = $submissionRepo->findAllWithDetails($options);
            
            // 获取总数
            $totalOptions = array_diff_key($options, ['limit' => '', 'offset' => '']);
            $total = $submissionRepo->countAllWithDetails($totalOptions);

            // 获取课程列表用于筛选
            $courseRepo = new CourseRepo();
            $courses = $courseRepo->findAll(['published' => 1, 'deleted' => 0]);

            // 获取作业列表用于筛选
            $assignmentRepo = new AssignmentRepo();
            $assignmentOptions = ['status' => \App\Models\Assignment::STATUS_PUBLISHED];
            if ($courseId) {
                $assignmentOptions['course_id'] = $courseId;
            }
            $assignments = $assignmentRepo->findAll($assignmentOptions);

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
                'pager' => $pager,
                'courses' => $courses,
                'assignments' => $assignments,
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'grade_status' => $gradeStatus,
                'is_late' => $isLate,
                'grade_statuses' => \App\Models\AssignmentSubmission::getGradeStatuses()
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
     * 获取提交详情（用于批改）
     * @Get("/submission/{id:[0-9]+}", name="admin.assignment.submission.detail")
     */
    public function submissionDetailAction()
    {
        try {
            $id = $this->dispatcher->getParam('id');
            
            $submissionRepo = new AssignmentSubmissionRepo();
            $submission = $submissionRepo->findByIdWithDetails($id);
            
            if (!$submission) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '提交记录不存在']);
                }
                $this->flashSession->error('提交记录不存在');
                return $this->response->redirect('/admin/assignment/grading/list');
            }

            // 获取作业信息
            $assignmentRepo = new AssignmentRepo();
            $assignment = $assignmentRepo->findById($submission['assignment_id']);

            if (!$assignment) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '作业不存在']);
                }
                $this->flashSession->error('作业不存在');
                return $this->response->redirect('/admin/assignment/grading/list');
            }

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'submission' => $submission,
                    'assignment' => $assignment->toArray()
                ]);
            }

            $this->view->setVars([
                'submission' => $submission,
                'assignment' => $assignment
            ]);
            
            return $this->view->pick('assignment/grading-detail');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取提交详情失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取提交详情失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/grading/list');
        }
    }

    /**
     * 批改作业（评分）
     * @Post("/submission/{id:[0-9]+}/grade", name="admin.assignment.submission.grade")
     */
    public function gradeSubmissionAction()
    {
        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getPost();
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        // 验证提交状态
        if ($submission->status !== \App\Models\AssignmentSubmission::STATUS_SUBMITTED) {
            return $this->jsonError(['message' => '该提交不可批改']);
        }

        try {
            // 获取作业信息用于评分
            $assignmentRepo = new AssignmentRepo();
            $assignment = $assignmentRepo->findById($submission->assignment_id);
            
            if (!$assignment) {
                return $this->jsonError(['message' => '作业不存在']);
            }

            // 开始批改
            if ($submission->grade_status === \App\Models\AssignmentSubmission::GRADE_STATUS_PENDING) {
                $submissionRepo->startGrading($submission, $this->authUser->id);
            }

            // 处理评分数据
            $score = floatval($postData['score'] ?? 0);
            $feedback = $postData['feedback'] ?? '';
            $gradeDetails = [];

            // 解析分题评分
            if (!empty($postData['grade_details'])) {
                $gradeDetails = json_decode($postData['grade_details'], true);
            }

            // 自动评分逻辑（选择题）
            if ($assignment->grade_mode === \App\Models\Assignment::GRADE_MODE_AUTO || 
                $assignment->grade_mode === \App\Models\Assignment::GRADE_MODE_MIXED) {
                $autoScore = $this->calculateAutoScore($assignment, $submission);
                
                // 如果是自动评分模式，使用计算得分
                if ($assignment->grade_mode === \App\Models\Assignment::GRADE_MODE_AUTO) {
                    $score = $autoScore['total_score'];
                    $gradeDetails = $autoScore['details'];
                } else {
                    // 混合模式：自动评分 + 手动评分
                    $score = $autoScore['auto_score'] + floatval($postData['manual_score'] ?? 0);
                    $gradeDetails = array_merge($autoScore['details'], $gradeDetails);
                }
            }

            // 验证分数不超过满分
            if ($score > $submission->max_score) {
                return $this->jsonError(['message' => "分数不能超过满分{$submission->max_score}"]);
            }

            // 完成批改
            $submissionRepo->completeGrading($submission, $score, $feedback, $gradeDetails);
            
            // 发送通知（可选）
            // TODO: 集成消息通知系统
            
            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '批改成功'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '批改失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 批量批改
     * @Post("/grading/batch", name="admin.assignment.grading.batch")
     */
    public function batchGradeAction()
    {
        $action = $this->request->getPost('action', 'string');
        $ids = $this->request->getPost('ids', 'string');
        
        if (empty($action) || empty($ids)) {
            return $this->jsonError(['message' => '参数错误']);
        }

        $submissionIds = explode(',', $ids);
        $submissionIds = array_map('intval', $submissionIds);
        $submissionIds = array_filter($submissionIds);

        if (empty($submissionIds)) {
            return $this->jsonError(['message' => '请选择要操作的提交']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();

        try {
            switch ($action) {
                case 'assign':
                    // 批量分配批改老师
                    $graderId = $this->authUser->id;
                    $affectedRows = $submissionRepo->batchAssignGrader($submissionIds, $graderId);
                    $message = "成功分配{$affectedRows}个提交";
                    break;
                    
                case 'auto_grade':
                    // 批量自动评分（仅选择题）
                    $affectedRows = $this->batchAutoGrade($submissionIds);
                    $message = "成功评分{$affectedRows}个提交";
                    break;
                    
                default:
                    return $this->jsonError(['message' => '不支持的操作']);
            }

            return $this->jsonSuccess([
                'affected_rows' => $affectedRows,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '批量操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 计算自动评分（选择题）
     * 
     * @param \App\Models\Assignment $assignment
     * @param \App\Models\AssignmentSubmission $submission
     * @return array
     */
    private function calculateAutoScore($assignment, $submission)
    {
        $content = $assignment->getContentData();
        $referenceAnswer = $assignment->getReferenceAnswerData();
        $submittedContent = $submission->getContentData();
        
        $totalScore = 0;
        $autoScore = 0;
        $details = [];

        if (empty($content) || empty($referenceAnswer) || empty($submittedContent)) {
            return [
                'total_score' => 0,
                'auto_score' => 0,
                'details' => []
            ];
        }

        foreach ($content as $index => $question) {
            $questionType = $question['type'] ?? '';
            $questionScore = floatval($question['score'] ?? 0);
            $totalScore += $questionScore;

            // 只对选择题自动评分
            if ($questionType === 'choice' || $questionType === 'multiple_choice') {
                $correctAnswer = $referenceAnswer[$index] ?? null;
                $userAnswer = $submittedContent[$index] ?? null;

                $isCorrect = false;
                if ($questionType === 'multiple_choice') {
                    // 多选题：完全匹配
                    sort($correctAnswer);
                    sort($userAnswer);
                    $isCorrect = ($correctAnswer === $userAnswer);
                } else {
                    // 单选题
                    $isCorrect = ($correctAnswer === $userAnswer);
                }

                $earnedScore = $isCorrect ? $questionScore : 0;
                $autoScore += $earnedScore;

                $details[$index] = [
                    'question_id' => $question['id'] ?? $index,
                    'type' => $questionType,
                    'max_score' => $questionScore,
                    'earned_score' => $earnedScore,
                    'is_correct' => $isCorrect,
                    'user_answer' => $userAnswer,
                    'correct_answer' => $correctAnswer
                ];
            } else {
                // 非选择题，需要手动评分
                $details[$index] = [
                    'question_id' => $question['id'] ?? $index,
                    'type' => $questionType,
                    'max_score' => $questionScore,
                    'earned_score' => 0,
                    'requires_manual_grading' => true,
                    'user_answer' => $submittedContent[$index] ?? null
                ];
            }
        }

        return [
            'total_score' => $autoScore, // 全自动评分模式使用
            'auto_score' => $autoScore,  // 混合评分模式使用
            'details' => $details
        ];
    }

    /**
     * 批量自动评分
     * 
     * @param array $submissionIds
     * @return int
     */
    private function batchAutoGrade($submissionIds)
    {
        $submissionRepo = new AssignmentSubmissionRepo();
        $assignmentRepo = new AssignmentRepo();
        $affectedCount = 0;

        foreach ($submissionIds as $submissionId) {
            try {
                $submission = $submissionRepo->findById($submissionId);
                
                if (!$submission || $submission->status !== \App\Models\AssignmentSubmission::STATUS_SUBMITTED) {
                    continue;
                }

                $assignment = $assignmentRepo->findById($submission->assignment_id);
                
                if (!$assignment || $assignment->grade_mode === \App\Models\Assignment::GRADE_MODE_MANUAL) {
                    continue;
                }

                // 开始批改
                if ($submission->grade_status === \App\Models\AssignmentSubmission::GRADE_STATUS_PENDING) {
                    $submissionRepo->startGrading($submission, $this->authUser->id);
                }

                // 计算自动评分
                $autoScore = $this->calculateAutoScore($assignment, $submission);
                
                // 完成批改
                $submissionRepo->completeGrading(
                    $submission, 
                    $autoScore['total_score'], 
                    '自动评分', 
                    $autoScore['details']
                );
                
                $affectedCount++;
            } catch (\Exception $e) {
                // 记录错误但继续处理
                $this->logger->error("批量自动评分失败 submission_id={$submissionId}: " . $e->getMessage());
            }
        }

        return $affectedCount;
    }
}

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
        $page = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 15);
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

        $assignments = $assignmentRepo->findByCourseId($courseId ?: 0, $options);
        
        // 获取总数用于分页
        $totalOptions = array_diff_key($options, ['limit' => '', 'offset' => '']);
        $total = count($assignmentRepo->findByCourseId($courseId ?: 0, $totalOptions));

        // 获取提交统计
        $submissionRepo = new AssignmentSubmissionRepo();
        foreach ($assignments as &$assignment) {
            $stats = $submissionRepo->getStatistics(['assignment_id' => $assignment['id']]);
            $assignment['submission_stats'] = $stats;
        }

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

        $this->view->setVar('assignments', $assignments);
        $this->view->setVar('pager', $pager);
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
        $courseRepo = new CourseRepo();
        $courses = $courseRepo->findAll(['status' => 'published']);

        $this->view->setVar('courses', $courses);
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
        $id = $this->dispatcher->getParam('id');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            $this->notFound();
            return;
        }

        $courseRepo = new CourseRepo();
        $courses = $courseRepo->findAll(['status' => 'published']);

        $this->view->setVar('assignment', $assignment);
        $this->view->setVar('courses', $courses);
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
}

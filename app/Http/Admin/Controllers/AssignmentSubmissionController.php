<?php
/**
 * 作业提交管理后台控制器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Admin\Controllers;

use App\Models\AssignmentSubmission as AssignmentSubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as AssignmentSubmissionRepo;
use App\Validators\AssignmentSubmission as AssignmentSubmissionValidator;

/**
 * @RoutePrefix("/admin/assignment/submission")
 */
class AssignmentSubmissionController extends Controller
{
    /**
     * @Get("/", name="admin.assignment.submission.index")
     */
    public function indexAction()
    {
        // 提交列表页面
    }

    /**
     * @Get("/list", name="admin.assignment.submission.list")
     */
    public function listAction()
    {
        $page = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 15);
        $assignmentId = $this->request->getQuery('assignment_id', 'int');
        $userId = $this->request->getQuery('user_id', 'int');
        $status = $this->request->getQuery('status', 'string');
        $gradeStatus = $this->request->getQuery('grade_status', 'string');
        $isLate = $this->request->getQuery('is_late', 'int');

        $submissionRepo = new AssignmentSubmissionRepo();
        
        $options = [
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ];

        if ($status) {
            $options['status'] = $status;
        }
        if ($gradeStatus) {
            $options['grade_status'] = $gradeStatus;
        }
        if (isset($isLate)) {
            $options['is_late'] = $isLate;
        }

        $submissions = [];
        $total = 0;

        if ($assignmentId) {
            $submissions = $submissionRepo->findByAssignmentId($assignmentId, $options);
            $totalOptions = array_diff_key($options, ['limit' => '', 'offset' => '']);
            $total = count($submissionRepo->findByAssignmentId($assignmentId, $totalOptions));
        } elseif ($userId) {
            $submissions = $submissionRepo->findByUserId($userId, $options);
            $totalOptions = array_diff_key($options, ['limit' => '', 'offset' => '']);
            $total = count($submissionRepo->findByUserId($userId, $totalOptions));
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

        $this->view->setVar('submissions', $submissions);
        $this->view->setVar('pager', $pager);
    }

    /**
     * @Get("/show/{id:[0-9]+}", name="admin.assignment.submission.show")
     */
    public function showAction()
    {
        $id = $this->dispatcher->getParam('id');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            $this->notFound();
            return;
        }

        $this->view->setVar('submission', $submission);
    }

    /**
     * @Get("/grade/{id:[0-9]+}", name="admin.assignment.submission.grade")
     */
    public function gradeAction()
    {
        $id = $this->dispatcher->getParam('id');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            $this->notFound();
            return;
        }

        if (!$submission->canGrade()) {
            $this->flash->error('该提交无法批改');
            return $this->response->redirect('/admin/assignment/submission');
        }

        $this->view->setVar('submission', $submission);
    }

    /**
     * @Post("/start_grading", name="admin.assignment.submission.start_grading")
     */
    public function startGradingAction()
    {
        $id = $this->request->getPost('id', 'int');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        if (!$submission->canGrade()) {
            return $this->jsonError(['message' => '该提交无法批改']);
        }

        try {
            $submissionRepo->startGrading($submission, $this->authUser->id);
            
            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '开始批改'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/complete_grading", name="admin.assignment.submission.complete_grading")
     */
    public function completeGradingAction()
    {
        try {
            $id = $this->request->getPost('id', 'int');
            $grading = $this->request->getPost('grading', null); // 题目评分数据
            $feedback = $this->request->getPost('feedback', 'string', '');
            
            // 如果grading是JSON字符串，解析它
            if (is_string($grading)) {
                $grading = json_decode($grading, true);
            }
            if (!is_array($grading)) {
                $grading = [];
            }

            // 使用新的GradingService
            $gradingService = new \App\Services\Assignment\GradingService();
            $submission = $gradingService->manualGrade($id, $grading, $feedback);

            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '批改完成'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '批改失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * @Post("/complete_grading_old", name="admin.assignment.submission.complete_grading_old")
     * @deprecated 保留用于兼容
     */
    public function completeGradingOldAction()
    {
        $id = $this->request->getPost('id', 'int');
        $score = $this->request->getPost('score', 'float');
        $feedback = $this->request->getPost('feedback', 'string', '');
        $gradeDetails = $this->request->getPost('grade_details', 'string', '');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        if ($submission->grader_id !== $this->authUser->id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        // 验证分数范围
        if ($score < 0 || $score > $submission->max_score) {
            return $this->jsonError(['message' => '分数超出范围']);
        }

        try {
            $submissionRepo->completeGrading(
                $submission, 
                $score, 
                $feedback, 
                $gradeData['grade_details'] ?? []
            );
            
            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '批改完成'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '批改失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/return", name="admin.assignment.submission.return")
     */
    public function returnSubmissionAction()
    {
        $id = $this->request->getPost('id', 'int');
        $reason = $this->request->getPost('reason', 'string', '');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        try {
            $submissionRepo->returnSubmission($submission, $reason);
            
            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '作业已退回'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '操作失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/pending", name="admin.assignment.submission.pending")
     */
    public function pendingAction()
    {
        $page = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 15);
        $courseId = $this->request->getQuery('course_id', 'int');
        $assignmentId = $this->request->getQuery('assignment_id', 'int');

        $submissionRepo = new AssignmentSubmissionRepo();
        
        $options = [
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ];

        if ($courseId) {
            $options['course_id'] = $courseId;
        }
        if ($assignmentId) {
            $options['assignment_id'] = $assignmentId;
        }

        $submissions = $submissionRepo->getPendingGrading($options);
        $totalOptions = array_diff_key($options, ['limit' => '', 'offset' => '']);
        $total = count($submissionRepo->getPendingGrading($totalOptions));

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

        $this->view->setVar('submissions', $submissions);
        $this->view->setVar('pager', $pager);
    }

    /**
     * @Post("/batch_assign", name="admin.assignment.submission.batch_assign")
     */
    public function batchAssignAction()
    {
        $submissionIds = $this->request->getPost('submission_ids', 'string');
        $graderId = $this->request->getPost('grader_id', 'int', $this->authUser->id);
        
        if (empty($submissionIds)) {
            return $this->jsonError(['message' => '请选择要分配的提交记录']);
        }

        $ids = explode(',', $submissionIds);
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);

        if (empty($ids)) {
            return $this->jsonError(['message' => '请选择要分配的提交记录']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();

        try {
            $affectedRows = $submissionRepo->batchAssignGrader($ids, $graderId);
            
            return $this->jsonSuccess([
                'affected_rows' => $affectedRows,
                'message' => "成功分配{$affectedRows}个提交记录"
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '分配失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/stats", name="admin.assignment.submission.stats")
     */
    public function statsAction()
    {
        $assignmentId = $this->request->getQuery('assignment_id', 'int');
        $courseId = $this->request->getQuery('course_id', 'int');
        $startTime = $this->request->getQuery('start_time', 'int');
        $endTime = $this->request->getQuery('end_time', 'int');

        $submissionRepo = new AssignmentSubmissionRepo();
        
        $options = [];
        if ($assignmentId) {
            $options['assignment_id'] = $assignmentId;
        }
        if ($courseId) {
            $options['course_id'] = $courseId;
        }
        if ($startTime) {
            $options['start_time'] = $startTime;
        }
        if ($endTime) {
            $options['end_time'] = $endTime;
        }

        $stats = $submissionRepo->getStatistics($options);

        return $this->jsonSuccess(['statistics' => $stats]);
    }

    /**
     * @Get("/export", name="admin.assignment.submission.export")
     */
    public function exportAction()
    {
        $assignmentId = $this->request->getQuery('assignment_id', 'int');
        $format = $this->request->getQuery('format', 'string', 'csv');
        
        if (!$assignmentId) {
            return $this->jsonError(['message' => '作业ID不能为空']);
        }

        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($assignmentId);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();
        $submissions = $submissionRepo->findByAssignmentId($assignmentId, [
            'status' => AssignmentSubmissionModel::STATUS_GRADED
        ]);

        // 导出数据
        $data = [];
        foreach ($submissions as $submission) {
            $data[] = [
                '学生ID' => $submission['user_id'],
                '姓名' => $submission['user']['name'] ?? '',
                '得分' => $submission['score'],
                '满分' => $submission['max_score'],
                '得分率' => $submission['score_percentage'] . '%',
                '等级' => $submission['grade'],
                '是否迟交' => $submission['is_late'] ? '是' : '否',
                '提交时间' => date('Y-m-d H:i:s', $submission['submit_time']),
                '批改时间' => $submission['grade_time'] ? date('Y-m-d H:i:s', $submission['grade_time']) : '',
                '反馈' => $submission['feedback']
            ];
        }

        $filename = "assignment_{$assignmentId}_submissions_" . date('YmdHis');

        if ($format === 'excel') {
            return $this->exportExcel($data, $filename);
        } else {
            return $this->exportCsv($data, $filename);
        }
    }

    /**
     * 导出CSV
     */
    protected function exportCsv(array $data, string $filename)
    {
        $this->response->setContentType('text/csv; charset=utf-8');
        $this->response->setHeader('Content-Disposition', "attachment; filename={$filename}.csv");
        
        $output = fopen('php://output', 'w');
        
        // 写入BOM以支持中文
        fwrite($output, "\xEF\xBB\xBF");
        
        if (!empty($data)) {
            // 写入表头
            fputcsv($output, array_keys($data[0]));
            
            // 写入数据
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        
        return $this->response;
    }

    /**
     * 导出Excel (需要PhpSpreadsheet库)
     */
    protected function exportExcel(array $data, string $filename)
    {
        // 这里需要集成PhpSpreadsheet库来生成Excel文件
        // 暂时返回错误提示
        return $this->jsonError(['message' => 'Excel导出功能暂未实现']);
    }

    /**
     * @Post("/delete", name="admin.assignment.submission.delete")
     */
    public function deleteAction()
    {
        $id = $this->request->getPost('id', 'int');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        try {
            $submissionRepo->delete($submission);
            return $this->jsonSuccess(['message' => '提交记录删除成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '删除失败: ' . $e->getMessage()]);
        }
    }
}

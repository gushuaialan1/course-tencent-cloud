<?php
/**
 * 作业提交API控制器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Api\Controllers;

use App\Models\AssignmentSubmission as AssignmentSubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as AssignmentSubmissionRepo;

class AssignmentSubmissionController extends Controller
{
    /**
     * 获取提交列表
     */
    public function listAction()
    {
        $assignmentId = $this->request->getQuery('assignment_id', 'int');
        $userId = $this->request->getQuery('user_id', 'int');
        $status = $this->request->getQuery('status', 'string');
        $gradeStatus = $this->request->getQuery('grade_status', 'string');
        $page = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 20);

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

        $submissions = [];

        if ($assignmentId) {
            // 按作业查询提交列表
            $submissions = $submissionRepo->findByAssignmentId($assignmentId, $options);
        } elseif ($userId || $this->authUser) {
            // 按用户查询提交列表
            $targetUserId = $userId ?: $this->authUser->id;
            $submissions = $submissionRepo->findByUserId($targetUserId, $options);
        } else {
            return $this->jsonError(['message' => '缺少查询参数']);
        }

        return $this->jsonSuccess([
            'submissions' => $submissions,
            'pager' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($submissions)
            ]
        ]);
    }

    /**
     * 获取提交详情
     */
    public function showAction()
    {
        $id = $this->dispatcher->getParam('id');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        // 权限检查
        if (!$this->authUser || 
            ($this->authUser->id !== $submission->user_id && 
             $this->authUser->id !== $submission->assignment->owner_id)) {
            return $this->jsonError(['message' => '无权限查看']);
        }

        $submissionData = $submission->toArray();
        $submissionData['assignment'] = $submission->assignment->toArray();

        return $this->jsonSuccess(['submission' => $submissionData]);
    }

    /**
     * 开始批改
     */
    public function startGradingAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        
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
     * 完成批改
     */
    public function completeGradingAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getJsonRawBody(true);
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        if ($submission->grader_id !== $this->authUser->id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        $score = $postData['score'] ?? 0;
        $feedback = $postData['feedback'] ?? '';
        $gradeDetails = $postData['grade_details'] ?? [];

        // 验证分数范围
        if ($score < 0 || $score > $submission->max_score) {
            return $this->jsonError(['message' => '分数超出范围']);
        }

        try {
            $submissionRepo->completeGrading($submission, $score, $feedback, $gradeDetails);
            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '批改完成'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '批改失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 退回作业
     */
    public function returnSubmissionAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getJsonRawBody(true);
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        // 权限检查
        if ($this->authUser->id !== $submission->assignment->owner_id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        $reason = $postData['reason'] ?? '';

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
     * 获取待批改队列
     */
    public function pendingQueueAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $courseId = $this->request->getQuery('course_id', 'int');
        $assignmentId = $this->request->getQuery('assignment_id', 'int');
        $page = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 20);

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

        return $this->jsonSuccess([
            'submissions' => $submissions,
            'pager' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($submissions)
            ]
        ]);
    }

    /**
     * 批量分配批改老师
     */
    public function batchAssignGraderAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $postData = $this->request->getJsonRawBody(true);
        $submissionIds = $postData['submission_ids'] ?? [];
        $graderId = $postData['grader_id'] ?? $this->authUser->id;

        if (empty($submissionIds)) {
            return $this->jsonError(['message' => '请选择要分配的提交记录']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();

        try {
            $affectedRows = $submissionRepo->batchAssignGrader($submissionIds, $graderId);
            return $this->jsonSuccess([
                'affected_rows' => $affectedRows,
                'message' => "成功分配{$affectedRows}个提交记录"
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '分配失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取提交统计
     */
    public function statisticsAction()
    {
        $assignmentId = $this->request->getQuery('assignment_id', 'int');
        $courseId = $this->request->getQuery('course_id', 'int');
        $userId = $this->request->getQuery('user_id', 'int');
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
        if ($userId) {
            $options['user_id'] = $userId;
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
     * 获取用户学习进度
     */
    public function userProgressAction()
    {
        $userId = $this->request->getQuery('user_id', 'int');
        $courseId = $this->request->getQuery('course_id', 'int');

        if (!$userId) {
            $userId = $this->authUser ? $this->authUser->id : 0;
        }

        if (!$userId) {
            return $this->jsonError(['message' => '请先登录']);
        }

        if (!$courseId) {
            return $this->jsonError(['message' => '课程ID不能为空']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();
        $progress = $submissionRepo->getUserProgress($userId, $courseId);

        return $this->jsonSuccess(['progress' => $progress]);
    }

    /**
     * 删除提交记录
     */
    public function deleteAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submission = $submissionRepo->findById($id);
        
        if (!$submission) {
            return $this->jsonError(['message' => '提交记录不存在']);
        }

        // 权限检查 - 只有学生本人或作业创建者可删除
        if ($this->authUser->id !== $submission->user_id && 
            $this->authUser->id !== $submission->assignment->owner_id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        // 已提交的作业不能删除
        if ($submission->status !== AssignmentSubmissionModel::STATUS_DRAFT) {
            return $this->jsonError(['message' => '已提交的作业不能删除']);
        }

        try {
            $submissionRepo->delete($submission);
            return $this->jsonSuccess(['message' => '提交记录删除成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '删除失败: ' . $e->getMessage()]);
        }
    }
}

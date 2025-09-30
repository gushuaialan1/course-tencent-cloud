<?php
/**
 * 作业API控制器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Api\Controllers;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as AssignmentSubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as AssignmentSubmissionRepo;
use App\Repos\Course as CourseRepo;
use App\Validators\Assignment as AssignmentValidator;
use App\Validators\AssignmentSubmission as AssignmentSubmissionValidator;

class AssignmentController extends Controller
{
    /**
     * 获取作业列表
     */
    public function listAction()
    {
        $courseId = $this->request->getQuery('course_id', 'int');
        $chapterId = $this->request->getQuery('chapter_id', 'int');
        $status = $this->request->getQuery('status', 'string');
        $type = $this->request->getQuery('type', 'string');
        $page = $this->request->getQuery('page', 'int', 1);
        $limit = $this->request->getQuery('limit', 'int', 20);

        if (!$courseId) {
            return $this->jsonError(['message' => '课程ID不能为空']);
        }

        // 检查课程权限
        $courseRepo = new CourseRepo();
        $course = $courseRepo->findById($courseId);
        if (!$course) {
            return $this->jsonError(['message' => '课程不存在']);
        }

        $assignmentRepo = new AssignmentRepo();
        
        $options = [
            'course_id' => $courseId,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ];

        if ($chapterId) {
            $options['chapter_id'] = $chapterId;
        }
        if ($status) {
            $options['status'] = $status;
        }
        if ($type) {
            $options['type'] = $type;
        }

        $assignments = $assignmentRepo->findByCourseId($courseId, $options);

        // 如果是学生，获取提交状态
        $submissionRepo = new AssignmentSubmissionRepo();
        if ($this->authUser) {
            foreach ($assignments as &$assignment) {
                $submission = $submissionRepo->findByAssignmentAndUser($assignment['id'], $this->authUser->id);
                $assignment['submission'] = $submission ? $submission->toArray() : null;
            }
        }

        return $this->jsonSuccess([
            'assignments' => $assignments,
            'pager' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($assignments)
            ]
        ]);
    }

    /**
     * 获取作业详情
     */
    public function showAction()
    {
        $id = $this->dispatcher->getParam('id');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        $assignmentData = $assignment->toArray();

        // 获取用户提交状态
        if ($this->authUser) {
            $submissionRepo = new AssignmentSubmissionRepo();
            $submission = $submissionRepo->findByAssignmentAndUser($id, $this->authUser->id);
            $assignmentData['submission'] = $submission ? $submission->toArray() : null;
        }

        // 获取统计信息
        $assignmentData['stats'] = $assignment->getSubmissionStats();

        return $this->jsonSuccess(['assignment' => $assignmentData]);
    }

    /**
     * 创建作业
     */
    public function createAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $postData = $this->request->getJsonRawBody(true);
        
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

        // JSON字段
        if (!empty($postData['attachments'])) {
            $data['attachments'] = $postData['attachments'];
        }
        if (!empty($postData['rubric'])) {
            $data['rubric'] = $postData['rubric'];
        }
        if (!empty($postData['content'])) {
            $data['content'] = $postData['content'];
        }
        if (!empty($postData['reference_answer'])) {
            $data['reference_answer'] = $postData['reference_answer'];
        }
        if (!empty($postData['visibility'])) {
            $data['visibility'] = $postData['visibility'];
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
     * 更新作业
     */
    public function updateAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getJsonRawBody(true);
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        // 权限检查
        if ($assignment->owner_id !== $this->authUser->id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        try {
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
     * 删除作业
     */
    public function deleteAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        // 权限检查
        if ($assignment->owner_id !== $this->authUser->id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        try {
            $assignmentRepo->delete($assignment);
            return $this->jsonSuccess(['message' => '作业删除成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 发布作业
     */
    public function publishAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        // 权限检查
        if ($assignment->owner_id !== $this->authUser->id) {
            return $this->jsonError(['message' => '无权限操作']);
        }

        try {
            $assignmentRepo->publish($assignment);
            return $this->jsonSuccess(['message' => '作业发布成功']);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业发布失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 复制作业
     */
    public function duplicateAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getJsonRawBody(true);
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        try {
            $overrideData = array_merge($postData, ['owner_id' => $this->authUser->id]);
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
     * 提交作业
     */
    public function submitAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getJsonRawBody(true);
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        if (!$assignment->canSubmit()) {
            return $this->jsonError(['message' => '作业无法提交']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();
        
        // 查找或创建提交记录
        $submission = $submissionRepo->findByAssignmentAndUser($id, $this->authUser->id);
        
        if (!$submission) {
            // 创建新提交
            $submissionData = [
                'assignment_id' => $id,
                'user_id' => $this->authUser->id,
                'max_score' => $assignment->max_score,
                'content' => $postData['content'] ?? [],
                'attachments' => $postData['attachments'] ?? []
            ];
            $submission = $submissionRepo->create($submissionData);
        } else {
            // 更新现有提交
            if (!$submission->canEdit()) {
                return $this->jsonError(['message' => '提交记录无法编辑']);
            }
            
            $updateData = [
                'content' => $postData['content'] ?? [],
                'attachments' => $postData['attachments'] ?? []
            ];
            $submissionRepo->update($submission, $updateData);
        }

        // 提交作业
        $submitOptions = [
            'ip' => $this->request->getClientAddress(),
            'user_agent' => $this->request->getUserAgent(),
            'start_time' => $postData['start_time'] ?? time()
        ];

        try {
            $submissionRepo->submit($submission, $submitOptions);
            return $this->jsonSuccess([
                'submission' => $submission->toArray(),
                'message' => '作业提交成功'
            ]);
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '作业提交失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 保存草稿
     */
    public function saveDraftAction()
    {
        if (!$this->authUser) {
            return $this->jsonError(['message' => '请先登录']);
        }

        $id = $this->dispatcher->getParam('id');
        $postData = $this->request->getJsonRawBody(true);
        
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($id);
        
        if (!$assignment) {
            return $this->jsonError(['message' => '作业不存在']);
        }

        $submissionRepo = new AssignmentSubmissionRepo();
        
        // 查找或创建提交记录
        $submission = $submissionRepo->findByAssignmentAndUser($id, $this->authUser->id);
        
        if (!$submission) {
            // 创建新草稿
            $submissionData = [
                'assignment_id' => $id,
                'user_id' => $this->authUser->id,
                'max_score' => $assignment->max_score,
                'status' => AssignmentSubmissionModel::STATUS_DRAFT,
                'content' => $postData['content'] ?? [],
                'attachments' => $postData['attachments'] ?? []
            ];
            $submission = $submissionRepo->create($submissionData);
        } else {
            // 更新草稿
            if (!$submission->canEdit()) {
                return $this->jsonError(['message' => '提交记录无法编辑']);
            }
            
            $updateData = [
                'content' => $postData['content'] ?? [],
                'attachments' => $postData['attachments'] ?? []
            ];
            $submissionRepo->update($submission, $updateData);
        }

        return $this->jsonSuccess([
            'submission' => $submission->toArray(),
            'message' => '草稿保存成功'
        ]);
    }

    /**
     * 获取作业统计
     */
    public function statisticsAction()
    {
        $courseId = $this->request->getQuery('course_id', 'int');
        $startTime = $this->request->getQuery('start_time', 'int');
        $endTime = $this->request->getQuery('end_time', 'int');

        $assignmentRepo = new AssignmentRepo();
        
        $options = [];
        if ($courseId) {
            $options['course_id'] = $courseId;
        }
        if ($this->authUser) {
            $options['owner_id'] = $this->authUser->id;
        }
        if ($startTime) {
            $options['start_time'] = $startTime;
        }
        if ($endTime) {
            $options['end_time'] = $endTime;
        }

        $stats = $assignmentRepo->getStatistics($options);

        return $this->jsonSuccess(['statistics' => $stats]);
    }
}

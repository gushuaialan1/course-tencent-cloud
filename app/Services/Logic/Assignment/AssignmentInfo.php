<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Services\Logic\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Models\User as UserModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as SubmissionRepo;
use App\Repos\Course as CourseRepo;
use App\Services\Logic\Service as LogicService;

class AssignmentInfo extends LogicService
{

    public function handle($id)
    {
        $user = $this->getCurrentUser();

        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($id);

        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        $result = $this->handleAssignment($assignment, $user);

        return $result;
    }

    protected function handleAssignment(AssignmentModel $assignment, UserModel $user)
    {
        $course = $this->handleCourseInfo($assignment->course_id);
        $questions = $this->handleQuestions($assignment->id);
        $submission = $this->handleSubmission($assignment->id, $user->id);
        $me = $this->handleMeInfo($assignment, $user);

        $isOverdue = $assignment->due_date > 0 && $assignment->due_date < time();

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'course_id' => $assignment->course_id,
            'due_date' => $assignment->due_date,
            'max_score' => $assignment->max_score,
            'question_count' => count($questions),
            'status' => $assignment->status,
            'allow_late' => $assignment->allow_late,
            'delete_time' => $assignment->delete_time,
            'create_time' => $assignment->create_time,
            'update_time' => $assignment->update_time,
            'course' => $course,
            'questions' => $questions,
            'submission' => $submission,
            'is_overdue' => $isOverdue,
            'me' => $me,
        ];
    }

    protected function handleCourseInfo($courseId)
    {
        $courseRepo = new CourseRepo();

        $course = $courseRepo->findById($courseId);

        if (!$course) {
            return null;
        }

        return [
            'id' => $course->id,
            'title' => $course->title,
            'cover' => $course->cover,
        ];
    }

    protected function handleQuestions($assignmentId)
    {
        $assignmentRepo = new AssignmentRepo();
        $assignment = $assignmentRepo->findById($assignmentId);
        
        if (!$assignment || !$assignment->content) {
            return [];
        }

        // content字段存储的是题目数组的JSON
        $questions = json_decode($assignment->content, true);
        
        if (!is_array($questions)) {
            return [];
        }

        // content可能直接是题目数组，也可能是包含questions键的对象
        if (isset($questions['questions']) && is_array($questions['questions'])) {
            $questions = $questions['questions'];
        }

        return $questions;
    }

    protected function handleSubmission($assignmentId, $userId)
    {
        $submissionRepo = new SubmissionRepo();

        $submission = $submissionRepo->findByAssignmentAndUser($assignmentId, $userId);

        if (!$submission) {
            return null;
        }

        $content = $submission->content ? json_decode($submission->content, true) : [];

        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'user_id' => $submission->user_id,
            'score' => $submission->score,
            'status' => $submission->status,
            'grade_status' => $submission->grade_status,
            'content' => $content,  // 使用标准字段名
            'answers' => $content,  // 兼容旧前端代码，保留answers字段映射
            'is_late' => $submission->is_late,
            'submit_time' => $submission->submit_time,  // 使用标准字段名
            'submitted_at' => $submission->submit_time,  // 兼容旧前端代码
            'grade_time' => $submission->grade_time,     // 使用标准字段名
            'graded_at' => $submission->grade_time,      // 兼容旧前端代码
            'create_time' => $submission->create_time,
            'update_time' => $submission->update_time,
        ];
    }

    protected function handleMeInfo(AssignmentModel $assignment, UserModel $user)
    {
        $me = [
            'owned' => 0,
            'allow_submit' => 1,  // 暂时全部放开权限
        ];

        if ($user->id == 0) {
            return $me;
        }

        // 暂时简化处理：所有登录用户都可以提交作业
        // 后续可以添加课程购买权限检查
        
        return $me;
    }

}


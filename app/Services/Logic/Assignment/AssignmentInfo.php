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
        // 作业功能需要用户登录，使用getLoginUser确保用户已登录
        $user = $this->getLoginUser(true);

        $assignmentRepo = new AssignmentRepo();

        $assignment = $assignmentRepo->findById($id);

        if (!$assignment) {
            throw new \Exception('作业不存在');
        }

        // 验证用户是否有访问该课程的权限
        $this->validateUserAccess($assignment, $user);

        $result = $this->handleAssignment($assignment, $user);

        return $result;
    }

    protected function handleAssignment(AssignmentModel $assignment, UserModel $user)
    {
        $course = $this->handleCourseInfo($assignment->course_id);
        $questions = $this->handleQuestions($assignment); // 传递assignment对象而不是ID
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

    protected function handleQuestions(AssignmentModel $assignment)
    {
        // 使用模型的getContentData()方法，确保正确解析JSON
        $content = $assignment->getContentData();
        
        if (!is_array($content)) {
            return [];
        }

        // content可能直接是题目数组，也可能是包含questions键的对象
        if (isset($content['questions']) && is_array($content['questions'])) {
            $questions = $content['questions'];
        } else {
            // 直接是题目数组
            $questions = $content;
        }

        // 添加调试信息（临时）
        if (empty($questions)) {
            error_log("AssignmentInfo: No questions found for assignment ID " . $assignment->id);
        } else {
            error_log("AssignmentInfo: Found " . count($questions) . " questions for assignment ID " . $assignment->id);
        }

        // 标准化题目数据，转换options为Volt模板期望的格式
        if (is_array($questions)) {
            foreach ($questions as $index => $question) {
                if (isset($question['options']) && is_array($question['options'])) {
                    $normalizedOptions = [];
                    
                    // 检查是否是新格式（对象数组）
                    $firstOption = reset($question['options']);
                    if (is_array($firstOption) && isset($firstOption['label']) && isset($firstOption['content'])) {
                        // 新格式：[{"label":"A","content":"xxx"}] -> {"A":"xxx"}
                        foreach ($question['options'] as $option) {
                            if (isset($option['label']) && isset($option['content'])) {
                                $normalizedOptions[$option['label']] = $option['content'];
                            }
                        }
                    } else {
                        // 旧格式或其他格式
                        foreach ($question['options'] as $key => $option) {
                            if (is_array($option) && isset($option['content'])) {
                                $normalizedOptions[$key] = $option['content'];
                            } elseif (is_object($option) && isset($option->content)) {
                                $normalizedOptions[$key] = $option->content;
                            } else {
                                $normalizedOptions[$key] = (string)$option;
                            }
                        }
                    }
                    
                    $questions[$index]['options'] = $normalizedOptions;
                    error_log("Normalized options for Q{$question['id']}: " . json_encode(array_keys($normalizedOptions)));
                }
                
                // 处理多选题标识，统一为Volt模板期望的格式
                if (isset($question['choice_type'])) {
                    $questions[$index]['multiple'] = ($question['choice_type'] === 'multiple');
                } elseif (isset($question['type']) && $question['type'] === 'choice_multiple') {
                    $questions[$index]['multiple'] = true;
                } else {
                    $questions[$index]['multiple'] = false;
                }
            }
        }

        // 确保返回的是连续索引的数组，Volt循环需要
        return is_array($questions) ? array_values($questions) : [];
    }

    /**
     * 验证用户是否有访问该作业的权限
     *
     * @param AssignmentModel $assignment
     * @param UserModel $user
     * @throws \Exception
     */
    protected function validateUserAccess(AssignmentModel $assignment, UserModel $user)
    {
        // 检查用户是否有访问该课程的权限
        // 这里可以根据实际业务逻辑进行扩展，比如检查用户是否购买了课程等
        
        if ($assignment->status !== AssignmentModel::STATUS_PUBLISHED) {
            throw new \Exception('作业尚未发布');
        }
        
        // 记录用户访问日志
        error_log("User {$user->id} accessing assignment {$assignment->id}");
    }

    protected function handleSubmission($assignmentId, $userId)
    {
        $submissionRepo = new SubmissionRepo();

        $submission = $submissionRepo->findByAssignmentAndUser($assignmentId, $userId);

        if (!$submission) {
            return null;
        }

        // 使用模型的getContentData()方法解析学生答案
        $content = $submission->getContentData();

        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'user_id' => $submission->user_id,
            'score' => $submission->score,
            'status' => $submission->status,
            'grade_status' => $submission->grade_status,
            'content' => $content,
            'is_late' => $submission->is_late,
            'submit_time' => $submission->submit_time,
            'grade_time' => $submission->grade_time,
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


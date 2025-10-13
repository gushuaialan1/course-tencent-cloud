<?php
/**
 * 课程作业列表服务
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Logic\Course;

use App\Library\Paginator\Query as PagerQuery;
use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as AssignmentSubmissionModel;
use App\Repos\Assignment as AssignmentRepo;
use App\Repos\AssignmentSubmission as AssignmentSubmissionRepo;
use App\Services\Logic\CourseTrait;
use App\Services\Logic\Service as LogicService;

class AssignmentList extends LogicService
{
    use CourseTrait;

    /**
     * 处理课程作业列表
     *
     * @param int $courseId 课程ID
     * @return mixed 返回分页器对象
     */
    public function handle($courseId)
    {
        // 检查课程是否存在
        $course = $this->checkCourse($courseId);

        $user = $this->getCurrentUser();

        // 使用分页器查询
        $pagerQuery = new PagerQuery();
        $page = $pagerQuery->getPage();
        $limit = $pagerQuery->getLimit();
        $sort = $pagerQuery->getSort();

        // 获取课程所有已发布的作业
        $assignmentRepo = new AssignmentRepo();
        
        $params = [
            'course_id' => $course->id,
            'status' => AssignmentModel::STATUS_PUBLISHED,
        ];
        
        // 使用paginate返回分页器
        $pager = $assignmentRepo->paginate($params, $sort, $page, $limit);
        
        // 处理分页数据
        return $this->handleAssignments($pager, $user->id);
    }
    
    /**
     * 处理作业分页数据
     *
     * @param mixed $pager 分页器对象
     * @param int $userId 用户ID
     * @return mixed
     */
    protected function handleAssignments($pager, $userId)
    {
        if ($pager->total_pages == 0) {
            return $pager;
        }
        
        $submissionRepo = new AssignmentSubmissionRepo();
        $submissions = [];
        
        // 如果用户已登录，获取用户的提交状态
        if ($userId > 0) {
            $assignmentIds = array_column($pager->items->toArray(), 'id');
            foreach ($assignmentIds as $assignmentId) {
                $submission = $submissionRepo->findByAssignmentAndUser($assignmentId, $userId);
                if ($submission) {
                    $submissions[$assignmentId] = $submission->toArray();
                }
            }
        }
        
        // 处理每个作业
        $items = [];
        foreach ($pager->items as $assignment) {
            $item = $this->handleAssignment($assignment->toArray(), $submissions[$assignment->id] ?? null);
            $items[] = $item;
        }
        
        $pager->items = $items;
        
        return $pager;
    }

    /**
     * 处理单个作业数据
     *
     * @param array $assignment 作业数据
     * @param array|null $submission 提交数据
     * @return array
     */
    protected function handleAssignment($assignment, $submission = null)
    {
        $currentTime = time();
        $dueDate = $assignment['due_date'];

        // 判断是否逾期
        $isOverdue = $dueDate > 0 && $dueDate < $currentTime;

        // 初始化结果
        $result = [
            'id' => $assignment['id'],
            'title' => $assignment['title'],
            'description' => $assignment['description'],
            'assignment_type' => $assignment['assignment_type'],
            'max_score' => $assignment['max_score'],
            'due_date' => $dueDate,
            'due_date_text' => $dueDate > 0 ? date('Y-m-d H:i', $dueDate) : '无截止时间',
            'time_limit' => $assignment['time_limit'],
            'max_attempts' => $assignment['max_attempts'],
            'is_overdue' => $isOverdue,
            'allow_late' => $assignment['allow_late'],
            'late_penalty' => $assignment['late_penalty'],
            'submitted' => false,
            'submission' => null,
            'status_text' => '未提交',
            'status_badge' => 'layui-bg-orange',
        ];

        // 解析content获取题目数量
        $content = $assignment['content'] ? json_decode($assignment['content'], true) : [];
        $questions = $content['questions'] ?? [];
        $result['question_count'] = count($questions);

        // 如果有提交记录，添加提交信息
        if ($submission) {
            $result['submitted'] = true;
            $result['submission'] = [
                'id' => $submission['id'],
                'score' => $submission['score'],
                'status' => $submission['status'],
                'grade_status' => $submission['grade_status'],
                'submit_time' => $submission['submit_time'],
                'submit_time_text' => date('Y-m-d H:i', $submission['submit_time']),
                'is_late' => $submission['is_late'],
                'attempt_count' => $submission['attempt_count'],
                'graded' => $submission['status'] == AssignmentSubmissionModel::STATUS_GRADED,
            ];

            // 根据提交状态设置状态文字和徽章颜色
            if ($submission['status'] == AssignmentSubmissionModel::STATUS_GRADED) {
                $result['status_text'] = '已批改';
                $result['status_badge'] = 'layui-bg-green';
            } elseif ($submission['status'] == AssignmentSubmissionModel::STATUS_SUBMITTED) {
                $result['status_text'] = '待批改';
                $result['status_badge'] = 'layui-bg-blue';
            } elseif ($submission['status'] == AssignmentSubmissionModel::STATUS_DRAFT) {
                $result['status_text'] = '草稿';
                $result['status_badge'] = 'layui-bg-gray';
            }
        } elseif ($isOverdue) {
            // 未提交且已逾期
            $result['status_text'] = '已截止';
            $result['status_badge'] = 'layui-bg-gray';
        }

        // 计算剩余时间
        if ($dueDate > 0 && !$isOverdue) {
            $remainingTime = $dueDate - $currentTime;
            $days = floor($remainingTime / 86400);
            $hours = floor(($remainingTime % 86400) / 3600);
            
            if ($days > 0) {
                $result['remaining_time'] = "剩余 {$days} 天";
            } elseif ($hours > 0) {
                $result['remaining_time'] = "剩余 {$hours} 小时";
            } else {
                $result['remaining_time'] = "即将截止";
            }
        }

        return $result;
    }
}


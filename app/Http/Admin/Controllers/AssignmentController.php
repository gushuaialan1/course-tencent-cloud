<?php
/**
 * 作业管理后台控制器 - 完全重写版本
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Admin\Controllers;

use App\Models\Assignment as AssignmentModel;
use App\Services\Assignment\AssignmentService;
use App\Services\Assignment\StatisticsService;
use App\Validators\Assignment\AssignmentValidator;

/**
 * @RoutePrefix("/admin/assignment")
 */
class AssignmentController extends Controller
{
    protected $assignmentService;
    protected $statisticsService;
    protected $validator;

    public function initialize()
    {
        parent::initialize();
        
        $this->assignmentService = new AssignmentService();
        $this->statisticsService = new StatisticsService();
        $this->validator = new AssignmentValidator();
    }

    /**
     * @Get("/", name="admin.assignment.index")
     */
    public function indexAction()
    {
        return $this->response->redirect('/admin/assignment/list');
    }

    /**
     * @Get("/list", name="admin.assignment.list")
     */
    public function listAction()
    {
        try {
            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15));
            
            // 筛选条件
            $params = [
                'course_id' => $this->request->getQuery('course_id', 'int'),
                'status' => $this->request->getQuery('status', 'string'),
                'title' => $this->request->getQuery('title', 'string'),
            ];

            // 获取作业列表（使用Service）
            $result = $this->assignmentService->getList(array_merge($params, [
                'page' => $page,
                'limit' => $limit
            ]));

            // 为每个作业添加统计信息
            foreach ($result['assignments'] as &$assignment) {
                $stats = $this->statisticsService->getAssignmentStats($assignment['id']);
                $assignment['stats'] = $stats;
            }

            if ($this->request->isAjax()) {
                return $this->jsonSuccess($result);
            }

            // 获取课程列表用于筛选
            $courseRepo = new \App\Repos\Course();
            $courses = $courseRepo->findAll(['published' => 1]);

            $this->view->setVars([
                'assignments' => $result['assignments'],
                'pager' => $result['pager'],
                'courses' => $courses,
                'params' => $params,
                'statuses' => AssignmentModel::getStatuses()
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
     * @Get("/create", name="admin.assignment.create")
     */
    public function createAction()
    {
        try {
            $courseRepo = new \App\Repos\Course();
            $courses = $courseRepo->findAll(['published' => 1]);

            $this->view->setVars([
                'courses' => $courses,
                'grade_modes' => AssignmentModel::getGradeModes(),
                'statuses' => AssignmentModel::getStatuses()
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
        try {
            $postData = $this->request->getPost();
            
            // 准备数据
            $data = [
                'title' => $postData['title'] ?? '',
                'description' => $postData['description'] ?? '',
                'course_id' => (int)($postData['course_id'] ?? 0),
                'chapter_id' => (int)($postData['chapter_id'] ?? 0),
                'due_date' => (int)($postData['due_date'] ?? 0),
                'allow_late' => (int)($postData['allow_late'] ?? 0),
                'late_penalty' => (float)($postData['late_penalty'] ?? 0.00),
                'grade_mode' => $postData['grade_mode'] ?? 'manual',
                'instructions' => $postData['instructions'] ?? '',
                'max_attempts' => (int)($postData['max_attempts'] ?? 1),
                'time_limit' => (int)($postData['time_limit'] ?? 0),
                'status' => $postData['status'] ?? 'draft',
                'owner_id' => $this->authUser->id
            ];

            // 处理content字段（题目数据）
            if (!empty($postData['content'])) {
                $content = is_string($postData['content']) ? 
                    json_decode($postData['content'], true) : $postData['content'];
                
                // 统一格式：确保是 {questions: [...]}
                if (is_array($content)) {
                    if (!isset($content['questions'])) {
                        // 旧格式：直接是数组 → 包裹
                        $data['content'] = ['questions' => $content];
                    } else {
                        $data['content'] = $content;
                    }
                } else {
                    $data['content'] = ['questions' => []];
                }
            } else {
                $data['content'] = ['questions' => []];
            }
            
            // 处理attachments字段
            if (!empty($postData['attachments'])) {
                $data['attachments'] = is_string($postData['attachments']) ?
                    json_decode($postData['attachments'], true) : $postData['attachments'];
            }

            // 验证数据
            $validation = $this->validator->validateCreate($data);
            
            if (!$validation['valid']) {
                return $this->jsonError([
                    'msg' => '数据验证失败',
                    'errors' => $validation['errors']
                ]);
            }

            // 创建作业
            $assignment = $this->assignmentService->create($data);
            
            return $this->jsonSuccess([
                'assignment' => $assignment->toArray(),
                'msg' => '作业创建成功'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '作业创建失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/edit/{id:[0-9]+}", name="admin.assignment.edit")
     */
    public function editAction($id)
    {
        try {
            // 获取作业详情
            $assignment = $this->assignmentService->getDetail($id);
            
            if (!$assignment) {
                $this->flashSession->error('作业不存在');
                return $this->response->redirect('/admin/assignment/list');
            }

            // 获取课程列表
            $courseRepo = new \App\Repos\Course();
            $courses = $courseRepo->findAll(['published' => 1]);

            $this->view->setVars([
                'assignment' => $assignment,
                'courses' => $courses,
                'grade_modes' => AssignmentModel::getGradeModes(),
                'statuses' => AssignmentModel::getStatuses(),
                'is_edit' => true
            ]);
            
            return $this->view->pick('assignment/create');
            
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
        try {
            $id = (int)$this->request->getPost('id', 'int');
            $postData = $this->request->getPost();
            
            // 准备更新数据
            $data = [
                'title' => $postData['title'] ?? '',
                'description' => $postData['description'] ?? '',
                'course_id' => (int)($postData['course_id'] ?? 0),
                'chapter_id' => (int)($postData['chapter_id'] ?? 0),
                'due_date' => (int)($postData['due_date'] ?? 0),
                'allow_late' => (int)($postData['allow_late'] ?? 0),
                'late_penalty' => (float)($postData['late_penalty'] ?? 0.00),
                'grade_mode' => $postData['grade_mode'] ?? 'manual',
                'instructions' => $postData['instructions'] ?? '',
                'max_attempts' => (int)($postData['max_attempts'] ?? 1),
                'time_limit' => (int)($postData['time_limit'] ?? 0),
                'status' => $postData['status'] ?? 'draft',
            ];

            // 处理content字段
            if (!empty($postData['content'])) {
                $content = is_string($postData['content']) ? 
                    json_decode($postData['content'], true) : $postData['content'];
                
                if (is_array($content)) {
                    if (!isset($content['questions'])) {
                        $data['content'] = ['questions' => $content];
                    } else {
                        $data['content'] = $content;
                    }
                }
            }
            
            // 处理attachments字段
            if (!empty($postData['attachments'])) {
                $data['attachments'] = is_string($postData['attachments']) ?
                    json_decode($postData['attachments'], true) : $postData['attachments'];
            }

            // 更新作业
            $assignment = $this->assignmentService->update($id, $data);
            
            return $this->jsonSuccess([
                'assignment' => $assignment->toArray(),
                'msg' => '作业更新成功'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '作业更新失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Post("/delete/{id:[0-9]+}", name="admin.assignment.delete")
     */
    public function deleteAction($id)
    {
        try {
            $this->assignmentService->delete($id);
            
            return $this->jsonSuccess(['msg' => '作业删除成功']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '作业删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/detail/{id:[0-9]+}", name="admin.assignment.detail")
     */
    public function detailAction($id)
    {
        try {
            $assignment = $this->assignmentService->getDetail($id);
            
            if (!$assignment) {
                if ($this->request->isAjax()) {
                    return $this->jsonError(['msg' => '作业不存在']);
                }
                $this->flashSession->error('作业不存在');
                return $this->response->redirect('/admin/assignment/list');
            }

            // 获取统计信息
            $stats = $this->statisticsService->getAssignmentStats($id);
            $gradeDistribution = $this->statisticsService->getGradeDistribution($id);

            if ($this->request->isAjax()) {
                return $this->jsonSuccess([
                    'assignment' => $assignment,
                    'stats' => $stats,
                    'grade_distribution' => $gradeDistribution
                ]);
            }

            $this->view->setVars([
                'assignment' => $assignment,
                'stats' => $stats,
                'grade_distribution' => $gradeDistribution
            ]);
            
            return $this->view->pick('assignment/detail');
            
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return $this->jsonError(['msg' => '获取作业详情失败: ' . $e->getMessage()]);
            }
            
            $this->flashSession->error('获取作业详情失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/assignment/list');
        }
    }

    /**
     * @Post("/publish/{id:[0-9]+}", name="admin.assignment.publish")
     */
    public function publishAction($id)
    {
        try {
            $assignment = $this->assignmentService->publish($id);
            
            return $this->jsonSuccess([
                'assignment' => $assignment->toArray(),
                'msg' => '作业发布成功'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '作业发布失败: ' . $e->getMessage()]);
        }
    }

    /**
     * @Get("/statistics/{id:[0-9]+}", name="admin.assignment.statistics")
     */
    public function statisticsAction($id)
    {
        try {
            $stats = $this->statisticsService->getAssignmentStats($id);
            $gradeDistribution = $this->statisticsService->getGradeDistribution($id);

            return $this->jsonSuccess([
                'stats' => $stats,
                'grade_distribution' => $gradeDistribution
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '获取统计信息失败: ' . $e->getMessage()]);
        }
    }
}

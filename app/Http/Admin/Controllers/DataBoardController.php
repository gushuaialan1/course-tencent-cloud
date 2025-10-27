<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Controllers;

use App\Http\Admin\Services\DataBoard as DataBoardService;
use App\Http\Admin\Services\DataBoardCourse as DataBoardCourseService;

/**
 * @RoutePrefix("/admin/data_board")
 */
class DataBoardController extends Controller
{
    /**
     * 数据看板展示页
     * 
     * @Get("/show", name="admin.data_board.show")
     */
    public function showAction()
    {
        $service = new DataBoardService();

        $stats = $service->getStats();
        $boardTitle = $service->getBoardTitle();
        $boardSubtitle = $service->getBoardSubtitle();

        $this->view->pick('data_board/show');
        $this->view->setVar('stats', $stats);
        $this->view->setVar('board_title', $boardTitle);
        $this->view->setVar('board_subtitle', $boardSubtitle);
    }

    /**
     * 数据看板编辑页（列表）
     * 
     * @Get("/list", name="admin.data_board.list")
     */
    public function listAction()
    {
        $service = new DataBoardService();

        $stats = $service->getStatsForEdit();
        $boardTitle = $service->getBoardTitle();
        $boardSubtitle = $service->getBoardSubtitle();

        $this->view->pick('data_board/list');
        $this->view->setVar('stats', $stats);
        $this->view->setVar('board_title', $boardTitle);
        $this->view->setVar('board_subtitle', $boardSubtitle);
    }

    /**
     * 编辑统计项
     * 
     * @Get("/edit/{id:[0-9]+}", name="admin.data_board.edit")
     */
    public function editAction($id)
    {
        $service = new DataBoardService();

        $stat = $service->getStat($id);

        if (!$stat) {
            return $this->notFound();
        }

        $this->view->pick('data_board/edit');
        $this->view->setVar('stat', $stat);
    }

    /**
     * 更新统计项
     * 
     * @Post("/update", name="admin.data_board.update")
     */
    public function updateAction()
    {
        $service = new DataBoardService();

        $id = $this->request->getPost('id', 'int');
        $data = [
            'stat_name' => $this->request->getPost('stat_name', 'string'),
            'virtual_value' => $this->request->getPost('virtual_value', 'int', 0),
            'unit' => $this->request->getPost('unit', 'string'),
            'icon' => $this->request->getPost('icon', 'string'),
            'color' => $this->request->getPost('color', 'string'),
            'sort_order' => $this->request->getPost('sort_order', 'int', 0),
            'is_visible' => $this->request->getPost('is_visible', 'int', 1),
            'description' => $this->request->getPost('description', 'string'),
        ];

        if ($service->updateStat($id, $data)) {
            return $this->jsonSuccess(['msg' => '更新成功']);
        }

        return $this->jsonError(['msg' => '更新失败']);
    }

    /**
     * 刷新所有真实统计数据
     * 
     * @Post("/refresh", name="admin.data_board.refresh")
     */
    public function refreshAction()
    {
        $service = new DataBoardService();

        if ($service->refreshRealStats()) {
            return $this->jsonSuccess(['msg' => '刷新成功']);
        }

        return $this->jsonError(['msg' => '刷新失败']);
    }

    /**
     * 刷新单个统计项的真实数据
     * 
     * @Post("/refresh/{id:[0-9]+}", name="admin.data_board.refresh_single")
     */
    public function refreshSingleAction($id)
    {
        $service = new DataBoardService();

        if ($service->refreshSingleRealStat($id)) {
            return $this->jsonSuccess(['msg' => '刷新成功']);
        }

        return $this->jsonError(['msg' => '刷新失败']);
    }

    /**
     * 更新看板标题和副标题
     * 
     * @Post("/update_title", name="admin.data_board.update_title")
     */
    public function updateTitleAction()
    {
        $service = new DataBoardService();

        $boardTitle = $this->request->getPost('board_title', 'string');
        $boardSubtitle = $this->request->getPost('board_subtitle', 'string');

        if (empty($boardTitle)) {
            return $this->jsonError(['msg' => '主标题不能为空']);
        }

        $titleResult = $service->updateBoardTitle($boardTitle);
        $subtitleResult = $service->updateBoardSubtitle($boardSubtitle);

        if ($titleResult && $subtitleResult) {
            return $this->jsonSuccess(['msg' => '保存成功']);
        }

        return $this->jsonError(['msg' => '保存失败']);
    }

    /**
     * 课程统计管理页
     * 
     * @Get("/course", name="admin.data_board.course")
     */
    public function courseAction()
    {
        $courseService = new DataBoardCourseService();

        $currentCourseId = $courseService->getCurrentCourseId();
        $courseInfo = null;
        $stats = [];
        $courseIntro = '';
        $courseTitle = '';
        $courseSubtitle = '';

        if ($currentCourseId) {
            $courseInfo = $courseService->getCourseInfo($currentCourseId);
            $stats = $courseService->getStatsList($currentCourseId);
            $courseIntro = $courseService->getCourseIntro($currentCourseId);
            $courseTitle = $courseService->getCourseTitle($currentCourseId);
            $courseSubtitle = $courseService->getCourseSubtitle();
            
            // 如果标题为空，自动初始化并保存到数据库
            if (empty($courseTitle)) {
                $courseTitle = $courseInfo['title'] . ' - 数据看板';
                $courseService->updateCourseTitle($courseTitle);
            }
            
            // 如果副标题为空，自动初始化并保存到数据库
            if (empty($courseSubtitle)) {
                $courseSubtitle = '课程数据实时展示';
                $courseService->updateCourseSubtitle($courseSubtitle);
            }
        }

        $this->view->pick('data_board/course');
        $this->view->setVar('current_course_id', $currentCourseId);
        $this->view->setVar('course_info', $courseInfo);
        $this->view->setVar('stats', $stats);
        $this->view->setVar('course_intro', $courseIntro);
        $this->view->setVar('course_title', $courseTitle);
        $this->view->setVar('course_subtitle', $courseSubtitle);
    }

    /**
     * 搜索课程
     * 
     * @Get("/search_course", name="admin.data_board.search_course")
     */
    public function searchCourseAction()
    {
        $service = new DataBoardCourseService();
        $keyword = $this->request->get('keyword', 'string', '');
        
        $courses = $service->searchCourses($keyword, 20);
        
        return $this->jsonSuccess(['data' => $courses]);
    }

    /**
     * 设置当前课程
     * 
     * @Post("/set_course", name="admin.data_board.set_course")
     */
    public function setCourseAction()
    {
        $service = new DataBoardCourseService();
        $courseId = $this->request->getPost('course_id', 'int');

        if (!$courseId) {
            return $this->jsonError(['msg' => '课程ID不能为空']);
        }

        // 检查课程是否存在
        $courseInfo = $service->getCourseInfo($courseId);
        if (!$courseInfo) {
            return $this->jsonError(['msg' => '课程不存在']);
        }

        if ($service->setCurrentCourseId($courseId)) {
            // 初始化统计数据（如果还没有）
            $stats = $service->getStatsList($courseId);
            
            return $this->jsonSuccess([
                'msg' => '设置成功',
                'data' => [
                    'course_id' => $courseId,
                    'course_info' => $courseInfo,
                ]
            ]);
        }

        return $this->jsonError(['msg' => '设置失败']);
    }

    /**
     * 刷新课程统计数据
     * 
     * @Post("/refresh_course", name="admin.data_board.refresh_course")
     */
    public function refreshCourseAction()
    {
        $service = new DataBoardCourseService();
        $courseId = $service->getCurrentCourseId();

        if (!$courseId) {
            return $this->jsonError(['msg' => '请先选择课程']);
        }

        if ($service->refreshAllStats($courseId)) {
            return $this->jsonSuccess(['msg' => '刷新成功']);
        }

        return $this->jsonError(['msg' => '刷新失败']);
    }

    /**
     * 刷新单个课程统计项
     * 
     * @Post("/refresh_course_single/{id:[0-9]+}", name="admin.data_board.refresh_course_single")
     */
    public function refreshCourseSingleAction($id)
    {
        $service = new DataBoardCourseService();

        if ($service->refreshSingleStat($id)) {
            return $this->jsonSuccess(['msg' => '刷新成功']);
        }

        return $this->jsonError(['msg' => '刷新失败']);
    }

    /**
     * 更新课程统计项
     * 
     * @Post("/update_course_stat", name="admin.data_board.update_course_stat")
     */
    public function updateCourseStatAction()
    {
        $service = new DataBoardCourseService();

        $id = $this->request->getPost('id', 'int');
        $data = [
            'virtual_value' => $this->request->getPost('virtual_value', 'int', 0),
            'is_visible' => $this->request->getPost('is_visible', 'int', 1),
        ];

        if ($service->updateStat($id, $data)) {
            return $this->jsonSuccess(['msg' => '更新成功']);
        }

        return $this->jsonError(['msg' => '更新失败']);
    }

    /**
     * 更新课程看板设置（标题、副标题、简介）
     * 
     * @Post("/update_course_intro", name="admin.data_board.update_course_intro")
     */
    public function updateCourseIntroAction()
    {
        $service = new DataBoardCourseService();
        
        $title = $this->request->getPost('course_title', 'string');
        $subtitle = $this->request->getPost('course_subtitle', 'string');
        $intro = $this->request->getPost('course_intro', 'string');

        if (empty($title)) {
            return $this->jsonError(['msg' => '主标题不能为空']);
        }

        $titleResult = $service->updateCourseTitle($title);
        $subtitleResult = $service->updateCourseSubtitle($subtitle);
        $introResult = $service->updateCourseIntro($intro);

        if ($titleResult && $subtitleResult && $introResult) {
            return $this->jsonSuccess(['msg' => '保存成功']);
        }

        return $this->jsonError(['msg' => '保存失败']);
    }

    /**
     * 课程统计展示页
     * 
     * @Get("/show_course", name="admin.data_board.show_course")
     */
    public function showCourseAction()
    {
        $courseService = new DataBoardCourseService();

        $courseId = $courseService->getCurrentCourseId();
        
        if (!$courseId) {
            $this->flashSession->error('请先在数据管理中选择要展示的课程');
            return $this->response->redirect('/admin/data_board/course');
        }

        $courseInfo = $courseService->getCourseInfo($courseId);
        if (!$courseInfo) {
            $this->flashSession->error('课程不存在');
            return $this->response->redirect('/admin/data_board/course');
        }

        $stats = $courseService->getStatsList($courseId);
        $stats = array_filter($stats, function($stat) {
            return $stat['is_visible'] == 1;
        });

        $courseIntro = $courseService->getCourseIntro($courseId);
        $courseTitle = $courseService->getCourseTitle($courseId);
        $courseSubtitle = $courseService->getCourseSubtitle();
        
        // 确保标题和副标题有默认值
        if (empty($courseTitle)) {
            $courseTitle = $courseInfo['title'] . ' - 数据看板';
        }
        if (empty($courseSubtitle)) {
            $courseSubtitle = '课程数据实时展示';
        }

        $this->view->pick('data_board/show_course');
        $this->view->setVar('course_info', $courseInfo);
        $this->view->setVar('stats', $stats);
        $this->view->setVar('course_intro', $courseIntro);
        $this->view->setVar('course_title', $courseTitle);
        $this->view->setVar('course_subtitle', $courseSubtitle);
    }
}


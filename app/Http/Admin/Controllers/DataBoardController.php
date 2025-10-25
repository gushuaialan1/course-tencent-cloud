<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Controllers;

use App\Http\Admin\Services\DataBoard as DataBoardService;

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
}


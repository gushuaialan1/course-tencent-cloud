<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Home\Controllers;

use App\Http\Home\Services\DataBoard as DataBoardService;

/**
 * @RoutePrefix("/data_board")
 */
class DataBoardController extends Controller
{
    /**
     * 公开访问的数据看板页面
     * 
     * @Get("/public", name="home.data_board.public")
     */
    public function publicAction()
    {
        $service = new DataBoardService();

        $stats = $service->getPublicStats();
        $boardTitle = $service->getBoardTitle();
        $boardSubtitle = $service->getBoardSubtitle();

        $this->view->pick('data_board/public');
        $this->view->setVar('stats', $stats);
        $this->view->setVar('board_title', $boardTitle);
        $this->view->setVar('board_subtitle', $boardSubtitle);
        $this->view->setVar('site_info', [
            'title' => $this->getDI()->getShared('config')->get('site.title', '在线教育平台')
        ]);
    }
}


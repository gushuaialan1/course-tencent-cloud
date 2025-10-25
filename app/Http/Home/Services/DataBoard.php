<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Home\Services;

use App\Models\DataBoardStat as DataBoardStatModel;
use App\Models\Setting as SettingModel;

class DataBoard extends Service
{
    /**
     * 获取公开展示的统计数据
     *
     * @return array
     */
    public function getPublicStats()
    {
        $stats = DataBoardStatModel::find([
            'conditions' => 'is_visible = 1',
            'order' => 'sort_order ASC',
        ]);

        $result = [];

        foreach ($stats as $stat) {
            $result[] = [
                'id' => $stat->id,
                'stat_key' => $stat->stat_key,
                'stat_name' => $stat->stat_name,
                'display_value' => $stat->display_value,
                'unit' => $stat->unit,
                'icon' => $stat->icon,
                'color' => $stat->color,
                'description' => $stat->description,
            ];
        }

        return $result;
    }

    /**
     * 获取看板标题
     *
     * @return string
     */
    public function getBoardTitle()
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.title'],
        ]);

        return $setting ? $setting->item_value : '数据看板';
    }

    /**
     * 获取看板副标题
     *
     * @return string
     */
    public function getBoardSubtitle()
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.subtitle'],
        ]);

        return $setting ? $setting->item_value : '实时展示平台核心数据指标';
    }
}


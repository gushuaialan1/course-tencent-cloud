<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Models;

class DataBoardStat extends Model
{
    /**
     * 主键
     *
     * @var int
     */
    public $id;

    /**
     * 统计项key
     *
     * @var string
     */
    public $stat_key;

    /**
     * 统计项名称
     *
     * @var string
     */
    public $stat_name;

    /**
     * 真实统计值
     *
     * @var int
     */
    public $real_value;

    /**
     * 虚拟增加值
     *
     * @var int
     */
    public $virtual_value;

    /**
     * 最终显示值
     *
     * @var int
     */
    public $display_value;

    /**
     * 单位
     *
     * @var string
     */
    public $unit;

    /**
     * 图标类名
     *
     * @var string
     */
    public $icon;

    /**
     * 颜色标识
     *
     * @var string
     */
    public $color;

    /**
     * 排序权重
     *
     * @var int
     */
    public $sort_order;

    /**
     * 是否显示
     *
     * @var int
     */
    public $is_visible;

    /**
     * 统计项描述
     *
     * @var string
     */
    public $description;

    /**
     * 创建时间
     *
     * @var int
     */
    public $created_at;

    /**
     * 更新时间
     *
     * @var int
     */
    public $updated_at;

    /**
     * 表名
     *
     * @return string
     */
    public function getSource()
    {
        return 'kg_data_board_stat';
    }

    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();

        $this->setSource('kg_data_board_stat');
    }

    /**
     * 数据转换前处理
     */
    public function beforeSave()
    {
        // 自动计算显示值
        $this->display_value = $this->real_value + $this->virtual_value;
    }
}


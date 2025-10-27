<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Models;

class DataBoardCourseStat extends Model
{
    /**
     * 主键
     *
     * @var int
     */
    public $id;

    /**
     * 课程ID
     *
     * @var int
     */
    public $course_id;

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
    public $real_value = 0;

    /**
     * 虚拟增加值
     *
     * @var int
     */
    public $virtual_value = 0;

    /**
     * 最终显示值
     *
     * @var int
     */
    public $display_value = 0;

    /**
     * 单位
     *
     * @var string
     */
    public $unit = '';

    /**
     * 图标类名
     *
     * @var string
     */
    public $icon = '';

    /**
     * 颜色标识
     *
     * @var string
     */
    public $color = '';

    /**
     * 排序权重
     *
     * @var int
     */
    public $sort_order = 0;

    /**
     * 是否显示
     *
     * @var int
     */
    public $is_visible = 1;

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

    public function getSource(): string
    {
        return 'kg_data_board_course_stat';
    }

    public function initialize()
    {
        parent::initialize();

        $this->belongsTo(
            'course_id',
            Course::class,
            'id',
            [
                'alias' => 'course',
                'foreignKey' => false,
            ]
        );
    }
}


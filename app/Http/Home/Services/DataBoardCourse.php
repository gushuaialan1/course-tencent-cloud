<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Home\Services;

use App\Models\Course as CourseModel;
use App\Models\DataBoardCourseStat as CourseStatModel;
use App\Models\Setting as SettingModel;

class DataBoardCourse extends Service
{
    /**
     * 获取当前设置的课程ID
     */
    public function getCurrentCourseId()
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_id'],
        ]);

        return $setting ? (int)$setting->item_value : 0;
    }

    /**
     * 获取课程信息
     */
    public function getCourseInfo($courseId)
    {
        $course = CourseModel::findFirst($courseId);
        if (!$course) {
            return null;
        }

        return [
            'id' => $course->id,
            'title' => $course->title,
            'summary' => $course->summary,
            'cover' => $course->cover,
        ];
    }

    /**
     * 获取课程看板标题
     */
    public function getCourseTitle($courseId)
    {
        // 先尝试从设置表获取
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_title'],
        ]);

        if ($setting && !empty($setting->item_value)) {
            return $setting->item_value;
        }

        // 否则生成默认标题
        $course = CourseModel::findFirst($courseId);
        return $course ? $course->title . ' - 数据看板' : '课程数据看板';
    }

    /**
     * 获取课程看板副标题
     */
    public function getCourseSubtitle()
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_subtitle'],
        ]);

        return $setting ? $setting->item_value : '课程数据实时展示';
    }

    /**
     * 获取课程简介
     */
    public function getCourseIntro($courseId)
    {
        // 先尝试从设置表获取
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_intro'],
        ]);

        if ($setting && !empty($setting->item_value)) {
            return $setting->item_value;
        }

        // 否则从课程表获取
        $course = CourseModel::findFirst($courseId);
        return $course ? $course->summary : '';
    }

    /**
     * 获取公开展示的课程统计数据
     *
     * @param int $courseId
     * @return array
     */
    public function getPublicStats($courseId)
    {
        $stats = CourseStatModel::find([
            'conditions' => 'course_id = :course_id: AND is_visible = 1',
            'bind' => ['course_id' => $courseId],
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
}


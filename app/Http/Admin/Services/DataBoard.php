<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Services;

use App\Models\Course as CourseModel;
use App\Models\User as UserModel;
use App\Models\CourseLearning as CourseLearningModel;
use App\Models\Review as ReviewModel;
use App\Models\DataBoardStat as DataBoardStatModel;
use App\Repos\Course as CourseRepo;

class DataBoard extends Service
{
    /**
     * 获取所有统计数据
     *
     * @return array
     */
    public function getStats()
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
                'real_value' => $stat->real_value,
                'virtual_value' => $stat->virtual_value,
                'display_value' => $stat->display_value,
                'unit' => $stat->unit,
                'icon' => $stat->icon,
                'color' => $stat->color,
                'sort_order' => $stat->sort_order,
                'is_visible' => $stat->is_visible,
                'description' => $stat->description,
            ];
        }

        return $result;
    }

    /**
     * 获取单个统计项
     *
     * @param int $id
     * @return DataBoardStatModel|null
     */
    public function getStat($id)
    {
        return DataBoardStatModel::findFirst($id);
    }

    /**
     * 更新统计项
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateStat($id, $data)
    {
        $stat = DataBoardStatModel::findFirst($id);

        if (!$stat) {
            return false;
        }

        if (isset($data['stat_name'])) {
            $stat->stat_name = $data['stat_name'];
        }

        if (isset($data['virtual_value'])) {
            $stat->virtual_value = (int)$data['virtual_value'];
        }

        if (isset($data['unit'])) {
            $stat->unit = $data['unit'];
        }

        if (isset($data['icon'])) {
            $stat->icon = $data['icon'];
        }

        if (isset($data['color'])) {
            $stat->color = $data['color'];
        }

        if (isset($data['sort_order'])) {
            $stat->sort_order = (int)$data['sort_order'];
        }

        if (isset($data['is_visible'])) {
            $stat->is_visible = (int)$data['is_visible'];
        }

        if (isset($data['description'])) {
            $stat->description = $data['description'];
        }

        return $stat->save();
    }

    /**
     * 刷新所有真实统计数据
     *
     * @return bool
     */
    public function refreshRealStats()
    {
        $stats = DataBoardStatModel::find();

        foreach ($stats as $stat) {
            $realValue = $this->calculateRealValue($stat->stat_key);
            $stat->real_value = $realValue;
            $stat->save();
        }

        return true;
    }

    /**
     * 刷新单个统计项的真实数据
     *
     * @param int $id
     * @return bool
     */
    public function refreshSingleRealStat($id)
    {
        $stat = DataBoardStatModel::findFirst($id);

        if (!$stat) {
            return false;
        }

        $realValue = $this->calculateRealValue($stat->stat_key);
        $stat->real_value = $realValue;

        return $stat->save();
    }

    /**
     * 计算真实统计值
     *
     * @param string $statKey
     * @return int
     */
    protected function calculateRealValue($statKey)
    {
        switch ($statKey) {
            case 'course_count':
                return $this->getCourseCount();
            
            case 'teacher_count':
                return $this->getTeacherCount();
            
            case 'student_count':
                return $this->getStudentCount();
            
            case 'page_view_count':
                return $this->getPageViewCount();
            
            case 'learning_count':
                return $this->getLearningCount();
            
            case 'review_count':
                return $this->getReviewCount();
            
            default:
                return 0;
        }
    }

    /**
     * 获取课程总数
     *
     * @return int
     */
    protected function getCourseCount()
    {
        return CourseModel::count([
            'conditions' => 'published = 1 AND deleted = 0',
        ]);
    }

    /**
     * 获取教师总数
     *
     * @return int
     */
    protected function getTeacherCount()
    {
        return UserModel::count([
            'conditions' => 'edu_role = 1 AND deleted = 0',
        ]);
    }

    /**
     * 获取学员总数
     *
     * @return int
     */
    protected function getStudentCount()
    {
        return UserModel::count([
            'conditions' => 'deleted = 0',
        ]);
    }

    /**
     * 获取总浏览量（基于课程学员数）
     *
     * @return int
     */
    protected function getPageViewCount()
    {
        // 使用 user_count + fake_user_count 作为浏览量指标
        $courses = CourseModel::find([
            'conditions' => 'published = 1 AND deleted = 0',
            'columns' => 'user_count, fake_user_count',
        ]);

        $total = 0;
        foreach ($courses as $course) {
            $total += ($course->user_count + $course->fake_user_count);
        }

        return $total;
    }

    /**
     * 获取学习人次
     *
     * @return int
     */
    protected function getLearningCount()
    {
        return CourseLearningModel::count([
            'conditions' => 'deleted = 0',
        ]);
    }

    /**
     * 获取评价数量
     *
     * @return int
     */
    protected function getReviewCount()
    {
        return ReviewModel::count([
            'conditions' => 'published = 1 AND deleted = 0',
        ]);
    }

    /**
     * 获取统计数据用于编辑
     *
     * @return array
     */
    public function getStatsForEdit()
    {
        $stats = DataBoardStatModel::find([
            'order' => 'sort_order ASC',
        ]);

        $result = [];

        foreach ($stats as $stat) {
            $result[] = [
                'id' => $stat->id,
                'stat_key' => $stat->stat_key,
                'stat_name' => $stat->stat_name,
                'real_value' => $stat->real_value,
                'virtual_value' => $stat->virtual_value,
                'display_value' => $stat->display_value,
                'unit' => $stat->unit,
                'icon' => $stat->icon,
                'color' => $stat->color,
                'sort_order' => $stat->sort_order,
                'is_visible' => $stat->is_visible,
                'description' => $stat->description,
            ];
        }

        return $result;
    }
}


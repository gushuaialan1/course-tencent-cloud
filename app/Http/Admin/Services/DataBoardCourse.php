<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Services;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Models\Chapter as ChapterModel;
use App\Models\Course as CourseModel;
use App\Models\DataBoardCourseStat as CourseStatModel;
use App\Models\Learning as LearningModel;
use App\Models\Setting as SettingModel;

class DataBoardCourse extends Service
{
    /**
     * 课程统计项定义
     */
    protected $statDefinitions = [
        'learning_count' => ['name' => '课程学习人数', 'unit' => '人', 'icon' => 'layui-icon-group', 'color' => 'blue', 'sort' => 1, 'desc' => '学习过该课程的总人数'],
        'view_count' => ['name' => '课程浏览量', 'unit' => '次', 'icon' => 'layui-icon-chart', 'color' => 'green', 'sort' => 2, 'desc' => '课程的总浏览量'],
        'chapter_count' => ['name' => '课程章节数', 'unit' => '章', 'icon' => 'layui-icon-template-1', 'color' => 'orange', 'sort' => 3, 'desc' => '课程的章节总数'],
        'duration' => ['name' => '课程时长', 'unit' => '小时', 'icon' => 'layui-icon-time', 'color' => 'red', 'sort' => 4, 'desc' => '课程的总时长'],
        'assignment_count' => ['name' => '课程作业数量', 'unit' => '个', 'icon' => 'layui-icon-form', 'color' => 'cyan', 'sort' => 5, 'desc' => '课程的作业总数'],
        'avg_score' => ['name' => '课程作业平均分', 'unit' => '分', 'icon' => 'layui-icon-rate', 'color' => 'purple', 'sort' => 6, 'desc' => '所有作业的平均分数'],
    ];

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
     * 设置当前课程ID
     */
    public function setCurrentCourseId($courseId)
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_id'],
        ]);

        if (!$setting) {
            $setting = new SettingModel();
            $setting->item_key = 'data_board.course_id';
            $setting->item_name = '数据看板-当前课程ID';
        }

        $setting->item_value = (string)$courseId;
        return $setting->save();
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
     * 更新课程看板标题
     */
    public function updateCourseTitle($title)
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_title'],
        ]);

        if (!$setting) {
            $setting = new SettingModel();
            $setting->item_key = 'data_board.course_title';
            $setting->item_name = '数据看板-课程主标题';
        }

        $setting->item_value = $title;
        return $setting->save();
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
     * 更新课程看板副标题
     */
    public function updateCourseSubtitle($subtitle)
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_subtitle'],
        ]);

        if (!$setting) {
            $setting = new SettingModel();
            $setting->item_key = 'data_board.course_subtitle';
            $setting->item_name = '数据看板-课程副标题';
        }

        $setting->item_value = $subtitle;
        return $setting->save();
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
     * 更新课程简介
     */
    public function updateCourseIntro($intro)
    {
        $setting = SettingModel::findFirst([
            'conditions' => 'item_key = :key:',
            'bind' => ['key' => 'data_board.course_intro'],
        ]);

        if (!$setting) {
            $setting = new SettingModel();
            $setting->item_key = 'data_board.course_intro';
            $setting->item_name = '数据看板-课程简介';
        }

        $setting->item_value = $intro;
        return $setting->save();
    }

    /**
     * 获取课程统计数据列表
     */
    public function getStatsList($courseId)
    {
        if (!$courseId) {
            return [];
        }

        $stats = CourseStatModel::find([
            'conditions' => 'course_id = :course_id:',
            'bind' => ['course_id' => $courseId],
            'order' => 'sort_order ASC',
        ]);

        // 如果没有数据，初始化
        if ($stats->count() == 0) {
            $this->initializeStats($courseId);
            $stats = CourseStatModel::find([
                'conditions' => 'course_id = :course_id:',
                'bind' => ['course_id' => $courseId],
                'order' => 'sort_order ASC',
            ]);
        }

        return $stats->toArray();
    }

    /**
     * 初始化课程统计数据
     */
    public function initializeStats($courseId)
    {
        foreach ($this->statDefinitions as $key => $def) {
            $stat = new CourseStatModel();
            $stat->course_id = $courseId;
            $stat->stat_key = $key;
            $stat->stat_name = $def['name'];
            $stat->real_value = 0;
            $stat->virtual_value = 0;
            $stat->display_value = 0;
            $stat->unit = $def['unit'];
            $stat->icon = $def['icon'];
            $stat->color = $def['color'];
            $stat->sort_order = $def['sort'];
            $stat->is_visible = 1;
            $stat->description = $def['desc'];
            $stat->save();
        }
    }

    /**
     * 刷新所有真实数据
     */
    public function refreshAllStats($courseId)
    {
        if (!$courseId) {
            return false;
        }

        $stats = CourseStatModel::find([
            'conditions' => 'course_id = :course_id:',
            'bind' => ['course_id' => $courseId],
        ]);

        foreach ($stats as $stat) {
            $this->refreshSingleStat($stat->id);
        }

        return true;
    }

    /**
     * 刷新单个统计项
     */
    public function refreshSingleStat($statId)
    {
        $stat = CourseStatModel::findFirst($statId);
        if (!$stat) {
            return false;
        }

        $realValue = $this->calculateRealValue($stat->course_id, $stat->stat_key);
        
        $stat->real_value = $realValue;
        $stat->display_value = $realValue + $stat->virtual_value;
        
        return $stat->save();
    }

    /**
     * 计算真实值
     */
    protected function calculateRealValue($courseId, $statKey)
    {
        switch ($statKey) {
            case 'learning_count':
                return $this->getLearningCount($courseId);
            case 'view_count':
                return $this->getViewCount($courseId);
            case 'chapter_count':
                return $this->getChapterCount($courseId);
            case 'duration':
                return $this->getDuration($courseId);
            case 'assignment_count':
                return $this->getAssignmentCount($courseId);
            case 'avg_score':
                return $this->getAvgScore($courseId);
            default:
                return 0;
        }
    }

    /**
     * 获取学习人数
     */
    protected function getLearningCount($courseId)
    {
        return LearningModel::count([
            'conditions' => 'course_id = :course_id:',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    /**
     * 获取浏览量
     */
    protected function getViewCount($courseId)
    {
        $course = CourseModel::findFirst($courseId);
        if (!$course) {
            return 0;
        }
        return $course->user_count + $course->fake_user_count;
    }

    /**
     * 获取章节数
     */
    protected function getChapterCount($courseId)
    {
        return ChapterModel::count([
            'conditions' => 'course_id = :course_id: AND deleted = 0',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    /**
     * 获取课程时长（转换为小时，保留1位小数）
     */
    protected function getDuration($courseId)
    {
        $course = CourseModel::findFirst($courseId);
        if (!$course) {
            return 0;
        }

        // attrs字段中可能包含duration（秒），转换为小时
        $attrs = is_string($course->attrs) ? json_decode($course->attrs, true) : $course->attrs;
        $durationSeconds = $attrs['duration'] ?? 0;
        
        // 转换为小时并四舍五入到1位小数，再转回整数（乘以10存储）
        return round($durationSeconds / 3600, 1) * 10;
    }

    /**
     * 获取作业数量
     */
    protected function getAssignmentCount($courseId)
    {
        return AssignmentModel::count([
            'conditions' => 'course_id = :course_id: AND deleted = 0',
            'bind' => ['course_id' => $courseId],
        ]);
    }

    /**
     * 获取作业平均分
     */
    protected function getAvgScore($courseId)
    {
        // 获取该课程的所有作业
        $assignments = AssignmentModel::find([
            'conditions' => 'course_id = :course_id: AND deleted = 0',
            'bind' => ['course_id' => $courseId],
            'columns' => 'id',
        ]);

        if ($assignments->count() == 0) {
            return 0;
        }

        $assignmentIds = array_column($assignments->toArray(), 'id');
        
        // 获取所有已评分的提交
        $result = SubmissionModel::average([
            'conditions' => 'assignment_id IN ({ids:array}) AND status = 3',
            'bind' => ['ids' => $assignmentIds],
            'column' => 'score',
        ]);

        return $result ? round($result, 1) * 10 : 0; // 乘以10存储为整数
    }

    /**
     * 更新统计项
     */
    public function updateStat($statId, $data)
    {
        $stat = CourseStatModel::findFirst($statId);
        if (!$stat) {
            return false;
        }

        if (isset($data['virtual_value'])) {
            $stat->virtual_value = (int)$data['virtual_value'];
            $stat->display_value = $stat->real_value + $stat->virtual_value;
        }

        if (isset($data['is_visible'])) {
            $stat->is_visible = (int)$data['is_visible'];
        }

        return $stat->save();
    }

    /**
     * 获取统计项详情
     */
    public function getStatInfo($statId)
    {
        $stat = CourseStatModel::findFirst($statId);
        return $stat ? $stat->toArray() : null;
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
     * 搜索课程列表
     */
    public function searchCourses($keyword = '', $limit = 20)
    {
        $conditions = 'published = 1 AND deleted = 0';
        $bind = [];

        if (!empty($keyword)) {
            $conditions .= ' AND title LIKE :keyword:';
            $bind['keyword'] = '%' . $keyword . '%';
        }

        $courses = CourseModel::find([
            'conditions' => $conditions,
            'bind' => $bind,
            'columns' => 'id, title, cover, summary',
            'order' => 'id DESC',
            'limit' => $limit,
        ]);

        return $courses->toArray();
    }
}


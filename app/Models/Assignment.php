<?php
/**
 * 作业模型
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Models;

use Phalcon\Mvc\Model\Behavior\SoftDelete;

class Assignment extends Model
{
    /**
     * 作业类型常量
     */
    const TYPE_CHOICE = 'choice';      // 选择题
    const TYPE_ESSAY = 'essay';        // 简答题
    const TYPE_UPLOAD = 'upload';      // 文件上传题
    const TYPE_MIXED = 'mixed';        // 混合题型

    /**
     * 作业状态常量
     */
    const STATUS_DRAFT = 'draft';           // 草稿
    const STATUS_PUBLISHED = 'published';   // 已发布
    const STATUS_CLOSED = 'closed';         // 已关闭
    const STATUS_ARCHIVED = 'archived';     // 已归档

    /**
     * 评分模式常量
     */
    const GRADE_MODE_AUTO = 'auto';         // 自动评分
    const GRADE_MODE_MANUAL = 'manual';     // 手动评分
    const GRADE_MODE_MIXED = 'mixed';       // 混合评分

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 作业标题
     *
     * @var string
     */
    public $title = '';

    /**
     * 作业描述
     *
     * @var string
     */
    public $description = '';

    /**
     * 关联课程ID
     *
     * @var int
     */
    public $course_id = 0;

    /**
     * 关联章节ID
     *
     * @var int
     */
    public $chapter_id = 0;

    /**
     * 作业类型
     *
     * @var string
     */
    public $assignment_type = self::TYPE_MIXED;

    /**
     * 总分
     *
     * @var float
     */
    public $max_score = 100.00;

    /**
     * 截止时间
     *
     * @var int
     */
    public $due_date = 0;

    /**
     * 是否允许迟交
     *
     * @var int
     */
    public $allow_late = 0;

    /**
     * 迟交扣分比例
     *
     * @var float
     */
    public $late_penalty = 0.00;

    /**
     * 评分模式
     *
     * @var string
     */
    public $grade_mode = self::GRADE_MODE_MANUAL;

    /**
     * 评分标准(JSON)
     *
     * @var string
     */
    public $rubric = '';

    /**
     * 作业说明
     *
     * @var string
     */
    public $instructions = '';

    /**
     * 附件列表(JSON)
     *
     * @var string
     */
    public $attachments = '';

    /**
     * 作业内容(JSON) - 题目详情
     *
     * @var string
     */
    public $content = '';

    /**
     * 参考答案(JSON)
     *
     * @var string
     */
    public $reference_answer = '';

    /**
     * 最大提交次数
     *
     * @var int
     */
    public $max_attempts = 1;

    /**
     * 时间限制(分钟)
     *
     * @var int
     */
    public $time_limit = 0;

    /**
     * 作业状态
     *
     * @var string
     */
    public $status = self::STATUS_DRAFT;

    /**
     * 发布时间
     *
     * @var int
     */
    public $publish_time = 0;

    /**
     * 可见性设置(JSON)
     *
     * @var string
     */
    public $visibility = '';

    /**
     * 创建者ID
     *
     * @var int
     */
    public $owner_id = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $create_time = 0;

    /**
     * 更新时间
     *
     * @var int
     */
    public $update_time = 0;

    /**
     * 删除时间
     *
     * @var int
     */
    public $delete_time = 0;

    public function initialize()
    {
        parent::initialize();

        $this->setSource('kg_assignment');

        $this->addBehavior(new SoftDelete([
            'field' => 'delete_time',
            'value' => time(),
        ]));

        // 关联课程
        $this->belongsTo('course_id', Course::class, 'id', [
            'alias' => 'course',
            'foreignKey' => [
                'allowNulls' => false,
                'message' => '关联课程不存在'
            ]
        ]);

        // 关联章节
        $this->belongsTo('chapter_id', Chapter::class, 'id', [
            'alias' => 'chapter',
            'foreignKey' => [
                'allowNulls' => true
            ]
        ]);

        // 关联创建者
        $this->belongsTo('owner_id', User::class, 'id', [
            'alias' => 'owner',
            'foreignKey' => [
                'allowNulls' => false,
                'message' => '创建者不存在'
            ]
        ]);

        // 关联提交记录
        $this->hasMany('id', AssignmentSubmission::class, 'assignment_id', [
            'alias' => 'submissions'
        ]);
    }

    public function beforeCreate()
    {
        $this->create_time = time();
        $this->update_time = time();
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
    }

    /**
     * 获取作业类型列表
     *
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_CHOICE => '选择题',
            self::TYPE_ESSAY => '简答题',
            self::TYPE_UPLOAD => '文件上传题',
            self::TYPE_MIXED => '混合题型',
        ];
    }

    /**
     * 获取评分模式列表
     *
     * @return array
     */
    public static function getGradeModes()
    {
        return [
            self::GRADE_MODE_AUTO => '自动评分',
            self::GRADE_MODE_MANUAL => '手动评分',
            self::GRADE_MODE_MIXED => '混合评分',
        ];
    }

    /**
     * 获取状态列表
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_CLOSED => '已关闭',
            self::STATUS_ARCHIVED => '已归档',
        ];
    }

    /**
     * 解析附件数据
     *
     * @return array
     */
    public function getAttachmentsData()
    {
        return $this->attachments ? json_decode($this->attachments, true) : [];
    }

    /**
     * 设置附件数据
     *
     * @param array $attachments
     */
    public function setAttachmentsData($attachments)
    {
        $this->attachments = json_encode($attachments, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析评分标准数据
     *
     * @return array
     */
    public function getRubricData()
    {
        return $this->rubric ? json_decode($this->rubric, true) : [];
    }

    /**
     * 设置评分标准数据
     *
     * @param array $rubric
     */
    public function setRubricData($rubric)
    {
        $this->rubric = json_encode($rubric, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析作业内容数据
     *
     * @return array
     */
    public function getContentData()
    {
        return $this->content ? json_decode($this->content, true) : [];
    }

    /**
     * 设置作业内容数据
     *
     * @param array $content
     */
    public function setContentData($content)
    {
        $this->content = json_encode($content, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析参考答案数据
     *
     * @return array
     */
    public function getReferenceAnswerData()
    {
        return $this->reference_answer ? json_decode($this->reference_answer, true) : [];
    }

    /**
     * 设置参考答案数据
     *
     * @param array $answer
     */
    public function setReferenceAnswerData($answer)
    {
        $this->reference_answer = json_encode($answer, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析可见性设置数据
     *
     * @return array
     */
    public function getVisibilityData()
    {
        return $this->visibility ? json_decode($this->visibility, true) : [];
    }

    /**
     * 设置可见性设置数据
     *
     * @param array $visibility
     */
    public function setVisibilityData($visibility)
    {
        $this->visibility = json_encode($visibility, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 检查是否已过截止时间
     *
     * @return bool
     */
    public function isPastDue()
    {
        return $this->due_date > 0 && time() > $this->due_date;
    }

    /**
     * 检查是否允许提交
     *
     * @return bool
     */
    public function canSubmit()
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->isPastDue() && !$this->allow_late) {
            return false;
        }

        return true;
    }

    /**
     * 获取提交统计信息
     *
     * @return array
     */
    public function getSubmissionStats()
    {
        $submissions = $this->submissions;
        
        $stats = [
            'total' => count($submissions),
            'submitted' => 0,
            'graded' => 0,
            'average_score' => 0,
        ];

        $totalScore = 0;
        $gradedCount = 0;

        foreach ($submissions as $submission) {
            if ($submission->status === AssignmentSubmission::STATUS_SUBMITTED ||
                $submission->status === AssignmentSubmission::STATUS_GRADED) {
                $stats['submitted']++;
            }
            
            if ($submission->status === AssignmentSubmission::STATUS_GRADED) {
                $stats['graded']++;
                $totalScore += $submission->score;
                $gradedCount++;
            }
        }

        if ($gradedCount > 0) {
            $stats['average_score'] = round($totalScore / $gradedCount, 2);
        }

        return $stats;
    }

    /**
     * 转换为数组格式
     *
     * @return array
     */
    public function toArray($columns = null)
    {
        $data = parent::toArray($columns);
        
        // 解析JSON字段
        if (isset($data['attachments'])) {
            $data['attachments'] = $this->getAttachmentsData();
        }
        if (isset($data['rubric'])) {
            $data['rubric'] = $this->getRubricData();
        }
        if (isset($data['content'])) {
            $data['content'] = $this->getContentData();
        }
        if (isset($data['reference_answer'])) {
            $data['reference_answer'] = $this->getReferenceAnswerData();
        }
        if (isset($data['visibility'])) {
            $data['visibility'] = $this->getVisibilityData();
        }

        return $data;
    }
}

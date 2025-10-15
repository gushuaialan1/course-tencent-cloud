<?php
/**
 * 作业提交模型
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Models;

use Phalcon\Mvc\Model\Behavior\SoftDelete;

class AssignmentSubmission extends Model
{
    /**
     * 提交状态常量
     */
    const STATUS_DRAFT = 'draft';           // 草稿
    const STATUS_SUBMITTED = 'submitted';   // 已提交
    const STATUS_GRADED = 'graded';         // 已批改
    const STATUS_RETURNED = 'returned';     // 已退回

    /**
     * 评分状态常量
     */
    const GRADE_STATUS_PENDING = 'pending';     // 待批改
    const GRADE_STATUS_GRADING = 'grading';     // 批改中
    const GRADE_STATUS_COMPLETED = 'completed'; // 批改完成

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 作业ID
     *
     * @var int
     */
    public $assignment_id = 0;

    /**
     * 学生ID
     *
     * @var int
     */
    public $user_id = 0;

    /**
     * 提交内容(JSON)
     *
     * @var string
     */
    public $content = '';

    /**
     * 提交附件(JSON)
     *
     * @var string
     */
    public $attachments = '';

    /**
     * 得分
     *
     * @var float
     */
    public $score = null;

    /**
     * 满分
     *
     * @var float
     */
    public $max_score = 100.00;

    /**
     * 批改反馈
     *
     * @var string
     */
    public $feedback = '';

    /**
     * 批改详情(JSON) - 分题批改
     *
     * @var string
     */
    public $grade_details = '';

    /**
     * 批改老师ID
     *
     * @var int|null
     */
    public $grader_id;

    /**
     * 提交状态
     *
     * @var string
     */
    public $status = self::STATUS_DRAFT;

    /**
     * 评分状态
     *
     * @var string
     */
    public $grade_status = self::GRADE_STATUS_PENDING;

    /**
     * 提交时间
     *
     * @var int
     */
    public $submit_time = 0;

    /**
     * 批改时间
     *
     * @var int
     */
    public $grade_time = 0;

    /**
     * 是否迟交
     *
     * @var int
     */
    public $is_late = 0;

    /**
     * 提交次数
     *
     * @var int
     */
    public $attempt_count = 1;

    /**
     * 完成时长(秒)
     *
     * @var int
     */
    public $duration = 0;

    /**
     * IP地址
     *
     * @var string
     */
    public $submit_ip = '';

    /**
     * 用户代理
     *
     * @var string
     */
    public $user_agent = '';

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

        $this->setSource('kg_assignment_submission');

        $this->addBehavior(new SoftDelete([
            'field' => 'delete_time',
            'value' => time(),
        ]));

        // 关联作业
        $this->belongsTo('assignment_id', Assignment::class, 'id', [
            'alias' => 'assignment',
            'foreignKey' => [
                'allowNulls' => false,
                'message' => '关联作业不存在'
            ]
        ]);

        // 关联学生
        $this->belongsTo('user_id', User::class, 'id', [
            'alias' => 'user',
            'foreignKey' => [
                'allowNulls' => false,
                'message' => '学生不存在'
            ]
        ]);

        // 关联批改老师
        $this->belongsTo('grader_id', User::class, 'id', [
            'alias' => 'grader',
            'foreignKey' => [
                'allowNulls' => true
            ]
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
     * 获取提交状态列表
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_SUBMITTED => '已提交',
            self::STATUS_GRADED => '已批改',
            self::STATUS_RETURNED => '已退回',
        ];
    }

    /**
     * 获取评分状态列表
     *
     * @return array
     */
    public static function getGradeStatuses()
    {
        return [
            self::GRADE_STATUS_PENDING => '待批改',
            self::GRADE_STATUS_GRADING => '批改中',
            self::GRADE_STATUS_COMPLETED => '批改完成',
        ];
    }

    /**
     * 解析提交内容数据
     *
     * @return array
     */
    public function getContentData()
    {
        return $this->content ? json_decode($this->content, true) : [];
    }

    /**
     * 设置提交内容数据
     *
     * @param array $content
     */
    public function setContentData($content)
    {
        $this->content = json_encode($content, JSON_UNESCAPED_UNICODE);
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
     * 解析批改详情数据
     *
     * @return array
     */
    public function getGradeDetailsData()
    {
        return $this->grade_details ? json_decode($this->grade_details, true) : [];
    }

    /**
     * 设置批改详情数据
     *
     * @param array $details
     */
    public function setGradeDetailsData($details)
    {
        $this->grade_details = json_encode($details, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 检查是否可以编辑
     *
     * @return bool
     */
    public function canEdit()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * 检查是否可以提交
     *
     * @return bool
     */
    public function canSubmit()
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        // 检查作业是否允许提交
        if (!$this->assignment->canSubmit()) {
            return false;
        }

        return true;
    }

    /**
     * 检查是否可以批改
     *
     * @return bool
     */
    public function canGrade()
    {
        return $this->status === self::STATUS_SUBMITTED && 
               $this->grade_status === self::GRADE_STATUS_PENDING;
    }

    /**
     * 提交作业
     *
     * @param array $options
     * @return bool
     */
    public function submitAssignment($options = [])
    {
        if (!$this->canSubmit()) {
            return false;
        }

        $this->status = self::STATUS_SUBMITTED;
        $this->submit_time = time();
        
        // 检查是否迟交
        if ($this->assignment->due_date > 0 && $this->submit_time > $this->assignment->due_date) {
            $this->is_late = 1;
        }

        // 设置IP和用户代理
        if (isset($options['ip'])) {
            $this->submit_ip = $options['ip'];
        }
        if (isset($options['user_agent'])) {
            $this->user_agent = $options['user_agent'];
        }

        // 计算完成时长
        if (isset($options['start_time'])) {
            $this->duration = $this->submit_time - $options['start_time'];
        }

        return $this->save();
    }

    /**
     * 开始批改
     *
     * @param int $graderId
     * @return bool
     */
    public function startGrading($graderId)
    {
        if (!$this->canGrade()) {
            return false;
        }

        $this->grader_id = $graderId;
        $this->grade_status = self::GRADE_STATUS_GRADING;

        return $this->save();
    }

    /**
     * 完成批改
     *
     * @param float $score
     * @param string $feedback
     * @param array $gradeDetails
     * @return bool
     */
    public function completeGrading($score, $feedback = '', $gradeDetails = [])
    {
        if ($this->grade_status !== self::GRADE_STATUS_GRADING) {
            return false;
        }

        $this->score = $score;
        $this->feedback = $feedback;
        if (!empty($gradeDetails)) {
            $this->setGradeDetailsData($gradeDetails);
        }
        $this->status = self::STATUS_GRADED;
        $this->grade_status = self::GRADE_STATUS_COMPLETED;
        $this->grade_time = time();

        return $this->save();
    }

    /**
     * 退回作业
     *
     * @param string $reason
     * @return bool
     */
    public function returnSubmission($reason = '')
    {
        $this->status = self::STATUS_RETURNED;
        $this->feedback = $reason;
        $this->grade_time = time();

        return $this->save();
    }

    /**
     * 计算得分率
     *
     * @return float
     */
    public function getScorePercentage()
    {
        if ($this->max_score <= 0 || $this->score === null) {
            return 0;
        }

        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * 获取等级评定
     *
     * @return string
     */
    public function getGrade()
    {
        $percentage = $this->getScorePercentage();

        if ($percentage >= 90) {
            return 'A';
        } elseif ($percentage >= 80) {
            return 'B';
        } elseif ($percentage >= 70) {
            return 'C';
        } elseif ($percentage >= 60) {
            return 'D';
        } else {
            return 'F';
        }
    }

    /**
     * 获取迟交扣分
     *
     * @return float
     */
    public function getLatePenalty()
    {
        if (!$this->is_late || !$this->assignment->late_penalty) {
            return 0;
        }

        return $this->score * $this->assignment->late_penalty;
    }

    /**
     * 获取最终得分(扣除迟交扣分)
     *
     * @return float
     */
    public function getFinalScore()
    {
        if ($this->score === null) {
            return null;
        }

        $penalty = $this->getLatePenalty();
        $finalScore = $this->score - $penalty;

        return max(0, $finalScore);
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
        if (isset($data['content'])) {
            $data['content'] = $this->getContentData();
        }
        if (isset($data['attachments'])) {
            $data['attachments'] = $this->getAttachmentsData();
        }
        if (isset($data['grade_details'])) {
            $data['grade_details'] = $this->getGradeDetailsData();
        }

        // 添加计算字段
        $data['score_percentage'] = $this->getScorePercentage();
        $data['grade'] = $this->getGrade();
        $data['late_penalty'] = $this->getLatePenalty();
        $data['final_score'] = $this->getFinalScore();

        return $data;
    }
}

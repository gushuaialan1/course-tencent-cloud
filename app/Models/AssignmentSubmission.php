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
     * 提交状态常量（简化后的单一状态）
     */
    const STATUS_DRAFT = 'draft';               // 草稿
    const STATUS_SUBMITTED = 'submitted';       // 已提交，待批改
    const STATUS_AUTO_GRADED = 'auto_graded';   // 自动批改完成
    const STATUS_GRADING = 'grading';           // 人工批改中
    const STATUS_GRADED = 'graded';             // 批改完成
    const STATUS_RETURNED = 'returned';         // 已退回
    

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
    public $content = null;

    /**
     * 提交附件(JSON)
     *
     * @var string
     */
    public $attachments = null;

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
    public $grader_id = null;

    /**
     * 提交状态
     *
     * @var string
     */
    public $status = self::STATUS_DRAFT;


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
        // 确保JSON列不写入空字符串
        if ($this->content === '') {
            $this->content = null;
        }
        if ($this->attachments === '') {
            $this->attachments = null;
        }
        if ($this->grade_details === '') {
            $this->grade_details = null;
        }
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
        // 确保JSON列不写入空字符串
        if ($this->content === '') {
            $this->content = null;
        }
        if ($this->attachments === '') {
            $this->attachments = null;
        }
        if ($this->grade_details === '') {
            $this->grade_details = null;
        }
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
            self::STATUS_AUTO_GRADED => '自动批改完成',
            self::STATUS_GRADING => '批改中',
            self::STATUS_GRADED => '已批改',
            self::STATUS_RETURNED => '已退回',
        ];
    }
    

    /**
     * 解析提交内容数据
     *
     * @return array
     */
    public function getContentData()
    {
        if (empty($this->content)) {
            return ['answers' => []];
        }
        
        $data = json_decode($this->content, true);
        
        if (!is_array($data)) {
            return ['answers' => []];
        }
        
        // 标准格式：必须有 answers 键
        return [
            'answers' => $data['answers'] ?? []
        ];
    }

    /**
     * 设置提交内容数据
     *
     * @param array $content
     */
    public function setContentData($content)
    {
        // 标准格式：必须包含 answers 键
        if (!isset($content['answers'])) {
            throw new \Exception('提交数据格式错误：缺少 answers 键');
        }
        
        $this->content = json_encode($content, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 获取答案数据
     *
     * @return array
     */
    public function getAnswers()
    {
        $contentData = $this->getContentData();
        return $contentData['answers'] ?? [];
    }
    
    /**
     * 获取指定题目的答案
     *
     * @param string $questionId
     * @return mixed
     */
    public function getAnswer($questionId)
    {
        $answers = $this->getAnswers();
        return $answers[$questionId] ?? null;
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
     * 解析批改详情数据（标准格式：题目ID到批改结果的映射）
     *
     * @return array
     */
    public function getGradeDetailsData()
    {
        if (empty($this->grade_details)) {
            return [];
        }
        
        $data = json_decode($this->grade_details, true);
        
        if (!is_array($data)) {
            return [];
        }
        
        return $data;
    }

    /**
     * 设置批改详情数据（标准格式：题目ID到批改结果的映射）
     *
     * @param array $details
     */
    public function setGradeDetailsData($details)
    {
        if (!is_array($details)) {
            throw new \Exception('批改详情必须是数组');
        }
        
        $this->grade_details = json_encode($details, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * 获取指定题目的批改结果
     *
     * @param string $questionId
     * @return array|null
     */
    public function getGradeForQuestion($questionId)
    {
        $gradeDetails = $this->getGradeDetailsData();
        return $gradeDetails[$questionId] ?? null;
    }
    
    /**
     * 获取批改汇总信息（计算得出）
     *
     * @return array
     */
    public function getGradeSummary()
    {
        $gradeDetails = $this->getGradeDetailsData();
        
        $totalEarned = 0;
        $totalMax = 0;
        $autoGradedScore = 0;
        $manualGradedScore = 0;
        
        foreach ($gradeDetails as $result) {
            $earnedScore = $result['earned_score'] ?? 0;
            $maxScore = $result['max_score'] ?? 0;
            $autoGraded = $result['auto_graded'] ?? false;
            
            $totalEarned += $earnedScore;
            $totalMax += $maxScore;
            
            if ($autoGraded) {
                $autoGradedScore += $earnedScore;
            } else {
                $manualGradedScore += $earnedScore;
            }
        }
        
        $percentage = $totalMax > 0 ? round(($totalEarned / $totalMax) * 100, 2) : 0;
        
        return [
            'total_earned' => $totalEarned,
            'total_max' => $totalMax,
            'auto_graded_score' => $autoGradedScore,
            'manual_graded_score' => $manualGradedScore,
            'percentage' => $percentage
        ];
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
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_AUTO_GRADED,
            self::STATUS_GRADING
        ]);
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
        $this->status = self::STATUS_GRADING;

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
        if (!in_array($this->status, [self::STATUS_AUTO_GRADED, self::STATUS_GRADING])) {
            return false;
        }

        $this->score = $score;
        $this->feedback = $feedback;
        if (!empty($gradeDetails)) {
            $this->setGradeDetailsData($gradeDetails);
        }
        $this->status = self::STATUS_GRADED;
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

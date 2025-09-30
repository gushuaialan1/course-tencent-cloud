<?php
/**
 * 作业验证器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Validators;

use App\Models\Assignment as AssignmentModel;
use App\Models\Course as CourseModel;
use App\Models\Chapter as ChapterModel;

class Assignment extends Validator
{
    protected $errors = [];

    /**
     * 验证作业数据
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        $this->errors = [];

        $this->checkTitle($data);
        $this->checkCourse($data);
        $this->checkChapter($data);
        $this->checkScore($data);
        $this->checkDueDate($data);
        $this->checkType($data);
        $this->checkGradeMode($data);
        $this->checkAttempts($data);
        $this->checkTimeLimit($data);
        $this->checkPenalty($data);

        return empty($this->errors);
    }

    /**
     * 检查标题
     */
    protected function checkTitle(array $data)
    {
        if (empty($data['title'])) {
            $this->errors[] = '作业标题不能为空';
            return;
        }

        if (mb_strlen($data['title']) > 200) {
            $this->errors[] = '作业标题长度不能超过200个字符';
        }

        if (mb_strlen($data['title']) < 2) {
            $this->errors[] = '作业标题长度不能少于2个字符';
        }
    }

    /**
     * 检查课程
     */
    protected function checkCourse(array $data)
    {
        if (empty($data['course_id'])) {
            $this->errors[] = '课程ID不能为空';
            return;
        }

        $course = CourseModel::findFirst([
            'conditions' => 'id = :id:',
            'bind' => ['id' => $data['course_id']]
        ]);

        if (!$course) {
            $this->errors[] = '关联课程不存在';
        }
    }

    /**
     * 检查章节
     */
    protected function checkChapter(array $data)
    {
        if (!empty($data['chapter_id'])) {
            $chapter = ChapterModel::findFirst([
                'conditions' => 'id = :id:',
                'bind' => ['id' => $data['chapter_id']]
            ]);

            if (!$chapter) {
                $this->errors[] = '关联章节不存在';
                return;
            }

            // 检查章节是否属于指定课程
            if (!empty($data['course_id']) && $chapter->course_id != $data['course_id']) {
                $this->errors[] = '章节不属于指定课程';
            }
        }
    }

    /**
     * 检查分数
     */
    protected function checkScore(array $data)
    {
        if (isset($data['max_score'])) {
            $maxScore = floatval($data['max_score']);
            
            if ($maxScore <= 0) {
                $this->errors[] = '总分必须大于0';
            }

            if ($maxScore > 999.99) {
                $this->errors[] = '总分不能超过999.99';
            }
        }
    }

    /**
     * 检查截止时间
     */
    protected function checkDueDate(array $data)
    {
        if (!empty($data['due_date'])) {
            $dueDate = intval($data['due_date']);
            
            if ($dueDate <= time()) {
                $this->errors[] = '截止时间必须晚于当前时间';
            }
        }
    }

    /**
     * 检查作业类型
     */
    protected function checkType(array $data)
    {
        if (!empty($data['assignment_type'])) {
            $validTypes = array_keys(AssignmentModel::getTypes());
            
            if (!in_array($data['assignment_type'], $validTypes)) {
                $this->errors[] = '无效的作业类型';
            }
        }
    }

    /**
     * 检查评分模式
     */
    protected function checkGradeMode(array $data)
    {
        if (!empty($data['grade_mode'])) {
            $validModes = array_keys(AssignmentModel::getGradeModes());
            
            if (!in_array($data['grade_mode'], $validModes)) {
                $this->errors[] = '无效的评分模式';
            }
        }
    }

    /**
     * 检查提交次数
     */
    protected function checkAttempts(array $data)
    {
        if (isset($data['max_attempts'])) {
            $maxAttempts = intval($data['max_attempts']);
            
            if ($maxAttempts < 1) {
                $this->errors[] = '最大提交次数必须大于等于1';
            }

            if ($maxAttempts > 10) {
                $this->errors[] = '最大提交次数不能超过10次';
            }
        }
    }

    /**
     * 检查时间限制
     */
    protected function checkTimeLimit(array $data)
    {
        if (isset($data['time_limit'])) {
            $timeLimit = intval($data['time_limit']);
            
            if ($timeLimit < 0) {
                $this->errors[] = '时间限制不能为负数';
            }

            if ($timeLimit > 1440) { // 24小时
                $this->errors[] = '时间限制不能超过1440分钟(24小时)';
            }
        }
    }

    /**
     * 检查迟交扣分
     */
    protected function checkPenalty(array $data)
    {
        if (isset($data['late_penalty'])) {
            $penalty = floatval($data['late_penalty']);
            
            if ($penalty < 0) {
                $this->errors[] = '迟交扣分比例不能为负数';
            }

            if ($penalty > 1) {
                $this->errors[] = '迟交扣分比例不能超过1(100%)';
            }
        }
    }

    /**
     * 检查作业内容
     */
    public function validateContent(array $content): bool
    {
        if (empty($content)) {
            $this->errors[] = '作业内容不能为空';
            return false;
        }

        foreach ($content as $index => $question) {
            if (empty($question['type'])) {
                $this->errors[] = "第{$index}题缺少题目类型";
                continue;
            }

            if (empty($question['title'])) {
                $this->errors[] = "第{$index}题缺少题目标题";
                continue;
            }

            // 根据题目类型进行具体验证
            switch ($question['type']) {
                case 'choice':
                    $this->validateChoiceQuestion($question, $index);
                    break;
                case 'essay':
                    $this->validateEssayQuestion($question, $index);
                    break;
                case 'upload':
                    $this->validateUploadQuestion($question, $index);
                    break;
            }
        }

        return empty($this->errors);
    }

    /**
     * 验证选择题
     */
    protected function validateChoiceQuestion(array $question, int $index)
    {
        if (empty($question['options'])) {
            $this->errors[] = "第{$index}题缺少选项";
            return;
        }

        if (count($question['options']) < 2) {
            $this->errors[] = "第{$index}题至少需要2个选项";
        }

        if (empty($question['correct_answer'])) {
            $this->errors[] = "第{$index}题缺少正确答案";
        }
    }

    /**
     * 验证简答题
     */
    protected function validateEssayQuestion(array $question, int $index)
    {
        // 简答题只需要标题，可选参考答案
        if (empty($question['title'])) {
            $this->errors[] = "第{$index}题缺少题目标题";
        }
    }

    /**
     * 验证文件上传题
     */
    protected function validateUploadQuestion(array $question, int $index)
    {
        if (empty($question['title'])) {
            $this->errors[] = "第{$index}题缺少题目标题";
        }

        // 检查文件类型限制
        if (!empty($question['allowed_types'])) {
            $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'png', 'zip'];
            foreach ($question['allowed_types'] as $type) {
                if (!in_array($type, $allowedTypes)) {
                    $this->errors[] = "第{$index}题包含不支持的文件类型: {$type}";
                }
            }
        }
    }

    /**
     * 获取错误信息
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 是否有错误
     */
    public function hasError(): bool
    {
        return !empty($this->errors);
    }

    /**
     * 获取第一个错误
     */
    public function getFirstError(): string
    {
        return $this->errors[0] ?? '';
    }
}

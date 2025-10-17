<?php
/**
 * 作业批改服务
 * 
 * 负责自动评分和手动批改相关功能
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment;

use App\Models\Assignment as AssignmentModel;
use App\Models\AssignmentSubmission as SubmissionModel;
use App\Services\Assignment\Graders\GraderFactory;
use App\Services\Service;

class GradingService extends Service
{
    /**
     * 自动评分
     * 
     * 遍历所有题目，对支持自动评分的题目进行评分
     * 
     * @param int $submissionId 提交记录ID
     * @return array 返回评分结果
     * @throws \Exception
     */
    public function autoGrade(int $submissionId): array
    {
        // 加载提交记录
        $submission = SubmissionModel::findFirst($submissionId);
        if (!$submission) {
            throw new \Exception('提交记录不存在');
        }

        // 加载作业
        $assignment = $submission->assignment;
        if (!$assignment) {
            throw new \Exception('关联作业不存在');
        }

        // 获取题目和答案
        $questions = $assignment->getQuestions();
        $answers = $submission->getAnswers();

        // 初始化评分工厂
        $graderFactory = new GraderFactory();

        // 初始化批改详情（按照标准格式：直接是题目ID到批改结果的映射）
        $gradeDetails = [];
        $totalEarned = 0;
        $totalMax = 0;
        $hasManualQuestion = false;

        // 遍历每个题目进行评分
        foreach ($questions as $question) {
            $questionId = $question['id'] ?? '';
            $questionType = $question['type'] ?? '';
            $studentAnswer = $answers[$questionId] ?? null;

            try {
                // 获取对应题型的评分器
                $grader = $graderFactory->getGrader($questionType);

                // 执行评分
                $result = $grader->grade($question, $studentAnswer);

                // 记录评分结果（标准格式）
                $gradeDetails[$questionId] = [
                    'earned_score' => $result['earned_score'] ?? 0,
                    'max_score' => $result['max_score'] ?? 0,
                    'is_correct' => $result['is_correct'] ?? false,
                    'auto_graded' => $result['auto_graded'] ?? false,
                    'feedback' => $result['feedback'] ?? ''
                ];

                // 累计分数
                $maxScore = $result['max_score'] ?? 0;
                $earnedScore = $result['earned_score'] ?? 0;
                $autoGraded = $result['auto_graded'] ?? false;

                $totalMax += $maxScore;
                $totalEarned += $earnedScore;

                if (!$autoGraded) {
                    $hasManualQuestion = true;
                }
            } catch (\Exception $e) {
                // 不支持的题型，标记为需要人工批改
                $gradeDetails[$questionId] = [
                    'earned_score' => 0,
                    'max_score' => $question['score'] ?? 0,
                    'is_correct' => false,
                    'auto_graded' => false,
                    'feedback' => '不支持的题型: ' . $e->getMessage()
                ];
                $totalMax += $question['score'] ?? 0;
                $hasManualQuestion = true;
            }
        }

        // 保存批改详情（标准格式：直接保存题目ID到批改结果的映射）
        $submission->setGradeDetailsData($gradeDetails);
        $submission->score = $totalEarned;
        $submission->max_score = $totalMax;

        // 根据是否有主观题，决定状态
        if ($hasManualQuestion) {
            // 有主观题，需要教师批改，保持submitted状态
            $submission->status = SubmissionModel::STATUS_SUBMITTED;
            // 不设置grader_id，等待教师主动接取批改任务
        } else {
            // 纯客观题，自动评分完成
            $submission->status = SubmissionModel::STATUS_AUTO_GRADED;
            $submission->grader_id = null;
            $submission->grade_time = time();
        }

        // 保存
        if (!$submission->save()) {
            throw new \Exception('保存评分结果失败：' . implode(', ', $submission->getMessages()));
        }

        return [
            'submission' => $submission,
            'has_manual_question' => $hasManualQuestion
        ];
    }

    /**
     * 手动批改
     * 
     * 教师对主观题进行批改，更新分数和评语
     * 
     * @param int $submissionId 提交记录ID
     * @param array $grading 批改数据，格式：['q1' => ['earned_score' => 25, 'grader_comment' => '...'], ...]
     * @param string $feedback 总体反馈
     * @return SubmissionModel
     * @throws \Exception
     */
    public function manualGrade(int $submissionId, array $grading, string $feedback = ''): SubmissionModel
    {
        // 加载提交记录
        $submission = SubmissionModel::findFirst($submissionId);
        if (!$submission) {
            throw new \Exception('提交记录不存在');
        }

        // 检查是否可以批改
        if (!$submission->canGrade()) {
            throw new \Exception('当前状态不允许批改');
        }

        // 获取现有批改详情（标准格式）
        $gradeDetails = json_decode($submission->grade_details, true) ?: [];

        // 合并新的批改数据
        foreach ($grading as $questionId => $gradeData) {
            if (isset($gradeDetails[$questionId])) {
                // 更新已有题目的批改
                $gradeDetails[$questionId]['earned_score'] = $gradeData['earned_score'] ?? 0;
                $gradeDetails[$questionId]['is_correct'] = ($gradeData['earned_score'] ?? 0) >= ($gradeDetails[$questionId]['max_score'] ?? 0);
                if (isset($gradeData['feedback'])) {
                    $gradeDetails[$questionId]['feedback'] = $gradeData['feedback'];
                }
                // 标记为手动批改
                $gradeDetails[$questionId]['auto_graded'] = false;
            }
        }

        // 重新计算总分
        $totalEarned = 0;
        $totalMax = 0;

        foreach ($gradeDetails as $result) {
            $maxScore = $result['max_score'] ?? 0;
            $earnedScore = $result['earned_score'] ?? 0;

            $totalMax += $maxScore;
            $totalEarned += $earnedScore;
        }

        // 保存批改结果（标准格式：直接保存题目ID到批改结果的映射）
        $submission->setGradeDetailsData($gradeDetails);
        $submission->score = $totalEarned;
        $submission->feedback = $feedback;
        $submission->status = SubmissionModel::STATUS_GRADED;
        $submission->grade_time = time();

        // 保存
        if (!$submission->save()) {
            throw new \Exception('保存批改结果失败：' . implode(', ', $submission->getMessages()));
        }

        return $submission;
    }

    /**
     * 退回作业
     * 
     * @param int $submissionId
     * @param string $reason 退回原因
     * @return SubmissionModel
     * @throws \Exception
     */
    public function returnSubmission(int $submissionId, string $reason = ''): SubmissionModel
    {
        $submission = SubmissionModel::findFirst($submissionId);
        if (!$submission) {
            throw new \Exception('提交记录不存在');
        }

        $submission->status = SubmissionModel::STATUS_RETURNED;
        $submission->feedback = $reason;
        $submission->grade_time = time();

        if (!$submission->save()) {
            throw new \Exception('退回失败：' . implode(', ', $submission->getMessages()));
        }

        return $submission;
    }

    /**
     * 获取待批改队列
     * 
     * @param int $teacherId 教师ID
     * @return array
     */
    public function getGradingQueue(int $teacherId): array
    {
        // 查询教师创建的作业
        $assignments = AssignmentModel::find([
            'conditions' => 'owner_id = :teacher_id: AND delete_time = 0',
            'bind' => ['teacher_id' => $teacherId]
        ]);

        if (!$assignments || count($assignments) === 0) {
            return [];
        }

        // 获取作业ID列表
        $assignmentIds = [];
        foreach ($assignments as $assignment) {
            $assignmentIds[] = $assignment->id;
        }

        // 查询待批改的提交记录
        $submissions = SubmissionModel::find([
            'conditions' => 'assignment_id IN ({ids:array}) AND status IN ({statuses:array}) AND delete_time = 0',
            'bind' => [
                'ids' => $assignmentIds,
                'statuses' => [SubmissionModel::STATUS_SUBMITTED, SubmissionModel::STATUS_GRADING]
            ],
            'order' => 'submit_time ASC'
        ]);

        return $submissions ? $submissions->toArray() : [];
    }
}


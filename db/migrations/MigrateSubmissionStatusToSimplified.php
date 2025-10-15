<?php
/**
 * 作业提交状态迁移 - 简化状态管理
 * 
 * 将双状态字段（status + grade_status）合并为单一status字段
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class MigrateSubmissionStatusToSimplified extends AbstractMigration
{
    /**
     * 迁移
     */
    public function up()
    {
        $this->output->writeln('开始迁移提交状态数据...');

        $pdo = $this->getAdapter()->getConnection();

        // 查询所有提交记录
        $stmt = $pdo->query("SELECT id, status, grade_status, grader_id FROM kg_assignment_submission WHERE delete_time = 0");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $migratedCount = 0;
        $statusMapping = [];

        foreach ($submissions as $submission) {
            $id = $submission['id'];
            $oldStatus = $submission['status'];
            $gradeStatus = $submission['grade_status'];
            $graderId = $submission['grader_id'];

            // 根据旧状态组合确定新状态
            $newStatus = $this->mapStatus($oldStatus, $gradeStatus, $graderId);

            // 更新状态
            $updateStmt = $pdo->prepare("UPDATE kg_assignment_submission SET status = ?, grade_status = NULL WHERE id = ?");
            $updateStmt->execute([$newStatus, $id]);

            // 统计
            $key = "{$oldStatus}+{$gradeStatus}";
            if (!isset($statusMapping[$key])) {
                $statusMapping[$key] = ['count' => 0, 'new_status' => $newStatus];
            }
            $statusMapping[$key]['count']++;

            $migratedCount++;
        }

        $this->output->writeln('');
        $this->output->writeln("迁移完成！总数: {$migratedCount}");
        $this->output->writeln('');
        $this->output->writeln("状态映射统计:");
        foreach ($statusMapping as $oldCombination => $info) {
            $this->output->writeln("  {$oldCombination} -> {$info['new_status']}: {$info['count']}条");
        }
    }

    /**
     * 映射旧状态到新状态
     *
     * @param string $status
     * @param string|null $gradeStatus
     * @param int|null $graderId
     * @return string
     */
    private function mapStatus($status, $gradeStatus, $graderId): string
    {
        // 草稿状态
        if ($status === 'draft') {
            return 'draft';
        }

        // 已退回
        if ($status === 'returned') {
            return 'returned';
        }

        // 已提交
        if ($status === 'submitted') {
            if ($gradeStatus === 'pending' || $gradeStatus === null) {
                return 'submitted';
            }
            if ($gradeStatus === 'grading') {
                return 'grading';
            }
            if ($gradeStatus === 'completed') {
                return 'graded';
            }
        }

        // 已批改
        if ($status === 'graded') {
            // 如果没有grader_id，说明是自动批改
            if ($graderId === null || $graderId === 0) {
                return 'auto_graded';
            }
            // 如果有grader_id但grade_status不是completed，说明正在批改
            if ($gradeStatus === 'grading') {
                return 'grading';
            }
            // 否则是批改完成
            return 'graded';
        }

        // 默认返回submitted
        return 'submitted';
    }

    /**
     * 回滚（不支持）
     */
    public function down()
    {
        $this->output->writeln('数据迁移不支持回滚，请从备份恢复数据库');
    }
}


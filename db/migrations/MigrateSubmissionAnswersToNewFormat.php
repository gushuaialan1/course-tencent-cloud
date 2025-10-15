<?php
/**
 * 作业提交答案数据迁移 - 转换为新格式
 * 
 * 将答案数据包裹到answers键中
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class MigrateSubmissionAnswersToNewFormat extends AbstractMigration
{
    /**
     * 迁移
     */
    public function up()
    {
        $this->output->writeln('开始迁移提交答案数据...');

        $pdo = $this->getAdapter()->getConnection();

        // 查询所有提交记录
        $stmt = $pdo->query("SELECT id, content FROM kg_assignment_submission WHERE delete_time = 0");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($submissions as $submission) {
            $id = $submission['id'];
            $content = $submission['content'];

            if (empty($content)) {
                $skippedCount++;
                continue;
            }

            try {
                // 解析JSON
                $data = json_decode($content, true);

                if (!is_array($data)) {
                    $this->output->writeln("  提交 #{$id}: 内容不是有效的JSON，跳过");
                    $skippedCount++;
                    continue;
                }

                // 如果已经有answers键，跳过
                if (isset($data['answers'])) {
                    $skippedCount++;
                    continue;
                }

                // 转换为新格式：包裹到answers键中
                $newData = ['answers' => $data];

                // 保存回数据库
                $newContent = json_encode($newData, JSON_UNESCAPED_UNICODE);
                $updateStmt = $pdo->prepare("UPDATE kg_assignment_submission SET content = ? WHERE id = ?");
                $updateStmt->execute([$newContent, $id]);

                $migratedCount++;
            } catch (Exception $e) {
                $this->output->writeln("  提交 #{$id}: 迁移失败 - {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->output->writeln('');
        $this->output->writeln("迁移完成！");
        $this->output->writeln("  成功: {$migratedCount}");
        $this->output->writeln("  跳过: {$skippedCount}");
        $this->output->writeln("  失败: {$errorCount}");
    }

    /**
     * 回滚（不支持）
     */
    public function down()
    {
        $this->output->writeln('数据迁移不支持回滚，请从备份恢复数据库');
    }
}


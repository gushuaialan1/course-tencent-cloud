<?php
/**
 * 作业题目数据迁移 - 转换为新格式
 * 
 * 将旧格式的题目数据转换为新的标准格式：
 * 1. 确保题目包裹在questions数组中
 * 2. 将type: "choice" + multiple: false/true 转换为 type: "single_choice"/"multiple_choice"
 * 3. 选项格式标准化为对象数组
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phalcon\Db\Column;
use Phinx\Migration\AbstractMigration;

class MigrateAssignmentQuestionsToNewFormat extends AbstractMigration
{
    /**
     * 迁移
     */
    public function up()
    {
        $this->output->writeln('开始迁移作业题目数据...');

        // 使用PDO直接操作，因为Phinx不支持Phalcon ORM
        $pdo = $this->getAdapter()->getConnection();

        // 查询所有作业
        $stmt = $pdo->query("SELECT id, content FROM kg_assignment WHERE delete_time = 0");
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($assignments as $assignment) {
            $id = $assignment['id'];
            $content = $assignment['content'];

            if (empty($content)) {
                $skippedCount++;
                continue;
            }

            try {
                // 解析JSON
                $data = json_decode($content, true);

                if (!is_array($data)) {
                    $this->output->writeln("  作业 #{$id}: 内容不是有效的JSON，跳过");
                    $skippedCount++;
                    continue;
                }

                // 转换为新格式
                $newData = $this->convertToNewFormat($data);

                // 保存回数据库
                $newContent = json_encode($newData, JSON_UNESCAPED_UNICODE);
                $updateStmt = $pdo->prepare("UPDATE kg_assignment SET content = ? WHERE id = ?");
                $updateStmt->execute([$newContent, $id]);

                $migratedCount++;
                $this->output->writeln("  作业 #{$id}: 迁移成功");
            } catch (Exception $e) {
                $this->output->writeln("  作业 #{$id}: 迁移失败 - {$e->getMessage()}");
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
     * 转换题目数据为新格式
     *
     * @param array $data
     * @return array
     */
    private function convertToNewFormat(array $data): array
    {
        // 如果已经有questions键，检查是否需要转换
        if (isset($data['questions'])) {
            $questions = $data['questions'];
        } else {
            // 旧格式：直接是题目数组
            $questions = $data;
        }

        // 转换每个题目
        $convertedQuestions = [];
        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                continue;
            }

            $convertedQuestion = $question;

            // 转换题目类型
            if (isset($question['type'])) {
                $type = $question['type'];

                // 旧格式：type: "choice" + multiple: true/false
                if ($type === 'choice') {
                    $multiple = $question['multiple'] ?? false;
                    $convertedQuestion['type'] = $multiple ? 'multiple_choice' : 'single_choice';
                    // 删除multiple字段
                    unset($convertedQuestion['multiple']);
                }
                // 其他类型保持不变：essay, code, file_upload
            }

            // 转换选项格式
            if (isset($question['options'])) {
                $options = $question['options'];

                // 如果是对象格式：{"A": "选项A", "B": "选项B"}
                if (is_array($options) && !isset($options[0])) {
                    $convertedOptions = [];
                    foreach ($options as $key => $value) {
                        $convertedOptions[] = [
                            'key' => $key,
                            'value' => $value
                        ];
                    }
                    $convertedQuestion['options'] = $convertedOptions;
                }
                // 如果已经是数组格式，保持不变
            }

            // 确保有id字段
            if (!isset($convertedQuestion['id'])) {
                $convertedQuestion['id'] = 'q' . ($index + 1);
            }

            // 确保有required字段
            if (!isset($convertedQuestion['required'])) {
                $convertedQuestion['required'] = true;
            }

            $convertedQuestions[] = $convertedQuestion;
        }

        return ['questions' => $convertedQuestions];
    }

    /**
     * 回滚（不支持）
     */
    public function down()
    {
        $this->output->writeln('数据迁移不支持回滚，请从备份恢复数据库');
    }
}


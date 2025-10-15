#!/usr/bin/env php
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

// 数据库配置（从 config.php 读取）
$configFile = __DIR__ . '/../../config/config.php';
if (!file_exists($configFile)) {
    die("错误：找不到配置文件 config.php\n");
}

$config = require $configFile;

// 连接数据库
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['db']['host'],
        $config['db']['port'],
        $config['db']['dbname'],
        $config['db']['charset']
    );
    
    $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "数据库连接成功\n";
} catch (PDOException $e) {
    die("数据库连接失败: " . $e->getMessage() . "\n");
}

// 开始迁移
echo "\n====================================\n";
echo "开始迁移作业题目数据...\n";
echo "====================================\n\n";

// 查询所有作业（使用正确的字段 delete_time）
$stmt = $pdo->query("SELECT id, title, content FROM kg_assignment WHERE delete_time = 0");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount = count($assignments);
$migratedCount = 0;
$skippedCount = 0;
$errorCount = 0;

echo "发现作业数量: {$totalCount}\n\n";

foreach ($assignments as $assignment) {
    $id = $assignment['id'];
    $title = $assignment['title'];
    $content = $assignment['content'];

    echo "处理作业 #{$id}: {$title}\n";

    if (empty($content)) {
        echo "  ⚠️  内容为空，跳过\n";
        $skippedCount++;
        continue;
    }

    try {
        // 解析JSON
        $data = json_decode($content, true);

        if (!is_array($data)) {
            echo "  ⚠️  内容不是有效的JSON，跳过\n";
            $skippedCount++;
            continue;
        }

        // 检查是否已经是新格式
        if (isset($data['questions']) && is_array($data['questions'])) {
            $firstQuestion = $data['questions'][0] ?? null;
            if ($firstQuestion && isset($firstQuestion['type'])) {
                $type = $firstQuestion['type'];
                // 如果已经是新格式的题型（single_choice, multiple_choice等）
                if (in_array($type, ['single_choice', 'multiple_choice', 'essay', 'code', 'file_upload'])) {
                    echo "  ✓ 已经是新格式，跳过\n";
                    $skippedCount++;
                    continue;
                }
            }
        }

        // 转换为新格式
        $newData = convertToNewFormat($data);

        // 保存回数据库
        $newContent = json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $updateStmt = $pdo->prepare("UPDATE kg_assignment SET content = ? WHERE id = ?");
        $updateStmt->execute([$newContent, $id]);

        $migratedCount++;
        echo "  ✅ 迁移成功\n";
    } catch (Exception $e) {
        echo "  ❌ 迁移失败: {$e->getMessage()}\n";
        $errorCount++;
    }
}

echo "\n====================================\n";
echo "迁移完成！\n";
echo "====================================\n";
echo "总计: {$totalCount}\n";
echo "成功: {$migratedCount}\n";
echo "跳过: {$skippedCount}\n";
echo "失败: {$errorCount}\n";
echo "====================================\n\n";

/**
 * 转换题目数据为新格式
 */
function convertToNewFormat(array $data): array
{
    // 如果已经有questions键，使用它
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

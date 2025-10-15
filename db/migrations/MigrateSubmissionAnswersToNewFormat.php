#!/usr/bin/env php
<?php
/**
 * 学生答案数据迁移 - 转换为新格式
 * 
 * 将旧格式的答案数据转换为新的标准格式：
 * 确保答案包裹在answers对象中
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

// 数据库配置
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
echo "开始迁移学生答案数据...\n";
echo "====================================\n\n";

// 查询所有提交记录
$stmt = $pdo->query("SELECT id, assignment_id, user_id, content FROM kg_assignment_submission WHERE delete_time = 0");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount = count($submissions);
$migratedCount = 0;
$skippedCount = 0;
$errorCount = 0;

echo "发现提交记录数量: {$totalCount}\n\n";

foreach ($submissions as $submission) {
    $id = $submission['id'];
    $content = $submission['content'];

    echo "处理提交 #{$id}\n";

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
        if (isset($data['answers']) && is_array($data['answers'])) {
            echo "  ✓ 已经是新格式，跳过\n";
            $skippedCount++;
            continue;
        }

        // 转换为新格式
        $newData = convertAnswersToNewFormat($data);

        // 保存回数据库
        $newContent = json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $updateStmt = $pdo->prepare("UPDATE kg_assignment_submission SET content = ? WHERE id = ?");
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
 * 转换答案数据为新格式
 */
function convertAnswersToNewFormat(array $data): array
{
    // 旧格式：直接是答案对象 {"q1": "A", "q2": ["B", "C"]}
    // 新格式：包裹在answers中 {"answers": {"q1": "A", "q2": ["B", "C"]}}
    
    return ['answers' => $data];
}

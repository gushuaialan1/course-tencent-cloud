#!/usr/bin/env php
<?php
/**
 * 提交状态数据迁移 - 简化状态管理
 * 
 * 将 status + grade_status 双字段模式简化为单个 status 字段
 * 
 * 状态映射规则：
 * - 草稿 (status=1, any grade_status) -> draft
 * - 已提交未评分 (status=2, grade_status=0) -> submitted
 * - 已提交评分中 (status=2, grade_status=1) -> grading
 * - 已提交已评分 (status=2, grade_status=2) -> graded
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
echo "开始迁移提交状态...\n";
echo "====================================\n\n";

// 查询所有提交记录
$stmt = $pdo->query("
    SELECT id, status, grade_status, score, grader_id 
    FROM kg_assignment_submission 
    WHERE delete_time = 0
");
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCount = count($submissions);
$migratedCount = 0;
$skippedCount = 0;

echo "发现提交记录数量: {$totalCount}\n\n";

foreach ($submissions as $submission) {
    $id = $submission['id'];
    $oldStatus = $submission['status'];
    $gradeStatus = $submission['grade_status'];
    $score = $submission['score'];
    $graderId = $submission['grader_id'];

    echo "处理提交 #{$id} (status={$oldStatus}, grade_status={$gradeStatus})\n";

    // 检查是否已经是新格式（字符串状态）
    if (!is_numeric($oldStatus)) {
        echo "  ✓ 已经是新格式，跳过\n";
        $skippedCount++;
        continue;
    }

    // 转换为新状态
    $newStatus = convertStatus($oldStatus, $gradeStatus, $score, $graderId);

    try {
        // 更新状态，同时将 grade_status 设置为 NULL
        $updateStmt = $pdo->prepare("
            UPDATE kg_assignment_submission 
            SET status = ?, grade_status = NULL 
            WHERE id = ?
        ");
        $updateStmt->execute([$newStatus, $id]);

        $migratedCount++;
        echo "  ✅ 迁移成功: {$newStatus}\n";
    } catch (Exception $e) {
        echo "  ❌ 迁移失败: {$e->getMessage()}\n";
    }
}

echo "\n====================================\n";
echo "迁移完成！\n";
echo "====================================\n";
echo "总计: {$totalCount}\n";
echo "成功: {$migratedCount}\n";
echo "跳过: {$skippedCount}\n";
echo "====================================\n\n";

/**
 * 转换旧状态为新状态
 */
function convertStatus($status, $gradeStatus, $score, $graderId): string
{
    // 草稿
    if ($status == 1) {
        return 'draft';
    }

    // 已提交
    if ($status == 2) {
        // 根据评分状态细分
        if ($gradeStatus == 0) {
            // 未评分 -> 检查是否有自动评分
            if ($score !== null && $score > 0) {
                return 'auto_graded';  // 自动评分完成
            }
            return 'submitted';  // 待评分
        } elseif ($gradeStatus == 1) {
            return 'grading';  // 评分中
        } elseif ($gradeStatus == 2) {
            return 'graded';  // 已评分
        }
    }

    // 默认：已提交
    return 'submitted';
}

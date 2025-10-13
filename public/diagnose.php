<?php
/**
 * 前台标签页诊断工具
 * 访问: http://域名/diagnose.php
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>前台标签页诊断</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; }
        h1 { color: #333; border-bottom: 2px solid #16BAAA; padding-bottom: 10px; }
        .success { background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .error { background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .test-item { margin: 20px 0; padding: 15px; background: #fafafa; border-left: 3px solid #16BAAA; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 前台标签页诊断</h1>
<?php
$baseUrl = 'http://localhost:88';
$courseId = 1;

function testApi($url, $name) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $success = ($httpCode >= 200 && $httpCode < 300);
    echo "<div class='test-item'>";
    echo "<h3>{$name}</h3>";
    echo "<p><strong>URL:</strong> {$url}</p>";
    echo "<p><strong>状态:</strong> " . ($success ? '✅ 成功' : '❌ 失败') . " (HTTP {$httpCode})</p>";
    echo "<p><strong>响应长度:</strong> " . strlen($response) . " 字节</p>";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
    echo "</div>";
    return $success;
}

echo "<h2>测试1：作业接口</h2>";
testApi("{$baseUrl}/course/{$courseId}/assignments", "作业列表");

echo "<h2>测试2：知识图谱接口</h2>";
testApi("{$baseUrl}/course/{$courseId}/knowledge-graph", "知识图谱");

echo "<h2>测试3：前端日志API</h2>";
$ch = curl_init("{$baseUrl}/api/log/frontend");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['level'=>'info','message'=>'测试']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "<div class='test-item'>";
echo "<p><strong>状态:</strong> " . ($httpCode == 200 ? '✅ 成功' : '❌ 失败') . " (HTTP {$httpCode})</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
echo "</div>";
?>

<h2>📝 下一步操作</h2>
<div class="success">
<ol>
<li>打开课程页面: http://82.156.52.63:88/course/1</li>
<li>按 F12 打开浏览器开发者工具</li>
<li>切换到 Console 标签</li>
<li>点击"作业"或"知识图谱"标签</li>
<li>查看 Console 中的错误信息（这是关键！）</li>
</ol>
<p><strong>重要:</strong> 前端JS错误只在浏览器Console可见，不在error.log中！</p>
</div>

</div>
</body>
</html>


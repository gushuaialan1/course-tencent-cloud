<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>课程数据看板 - 暂未设置</title>
    {{ icon_link('favicon.ico') }}
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-container {
            text-align: center;
            background: #fff;
            padding: 60px 80px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
            max-width: 600px;
        }
        .empty-icon {
            font-size: 120px;
            color: #ddd;
            margin-bottom: 30px;
        }
        .empty-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .empty-desc {
            font-size: 16px;
            color: #999;
            line-height: 1.8;
        }
    </style>
</head>
<body>
    <div class="empty-container">
        <div class="empty-icon">📊</div>
        <div class="empty-title">课程数据看板暂未设置</div>
        <div class="empty-desc">
            管理员还未配置要展示的课程数据<br>
            请稍后再访问
        </div>
    </div>
</body>
</html>


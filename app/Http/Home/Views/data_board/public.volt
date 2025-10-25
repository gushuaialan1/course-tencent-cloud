<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ board_title|default('数据看板') }} - {{ site_info.title }}</title>
    {{ icon_link('favicon.ico') }}
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        .kg-dashboard-header {
            text-align: center;
            padding: 60px 20px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        .kg-dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }
        .kg-dashboard-title {
            font-size: 48px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 12px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 4px;
            position: relative;
            z-index: 1;
            background: linear-gradient(to right, #fff 0%, #f0f0f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: titleShine 3s ease-in-out infinite;
        }
        @keyframes titleShine {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }
        .kg-dashboard-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.9);
            margin: 0;
            letter-spacing: 2px;
            position: relative;
            z-index: 1;
        }
        .kg-stats-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .kg-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        .kg-stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 28px 24px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            transition: all .3s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
        }
        .kg-stat-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color) 0%, var(--card-color-light) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s ease;
        }
        .kg-stat-card:hover {
            box-shadow: 0 12px 24px rgba(0,0,0,.15);
            transform: translateY(-6px);
        }
        .kg-stat-card:hover::before {
            transform: scaleX(1);
        }
        .kg-stat-card:hover .kg-stat-icon {
            transform: scale(1.15) rotate(8deg);
            opacity: 0.9;
        }
        .kg-stat-card.blue { --card-color: #1E9FFF; --card-color-light: #5FB8FF; }
        .kg-stat-card.green { --card-color: #5FB878; --card-color-light: #8FD99F; }
        .kg-stat-card.orange { --card-color: #FFB800; --card-color-light: #FFD666; }
        .kg-stat-card.red { --card-color: #FF5722; --card-color-light: #FF8A65; }
        .kg-stat-card.cyan { --card-color: #00D7B9; --card-color-light: #5EEBD7; }
        .kg-stat-card.purple { --card-color: #9C26B0; --card-color-light: #BA68C8; }
        .kg-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            min-height: 44px;
        }
        .kg-stat-title {
            font-size: 15px;
            color: #666;
            font-weight: 600;
            letter-spacing: 0.5px;
            line-height: 44px;
        }
        .kg-stat-icon {
            font-size: 44px;
            opacity: 0.18;
            transition: all .4s ease;
            color: var(--card-color);
            line-height: 1;
            flex-shrink: 0;
        }
        .kg-stat-value {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            margin-bottom: 16px;
            line-height: 1;
            font-family: 'Arial', sans-serif;
        }
        .kg-stat-value .number {
            display: inline-block;
            animation: numberPop 0.6s ease;
        }
        @keyframes numberPop {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); opacity: 1; }
        }
        .kg-stat-unit {
            font-size: 18px;
            color: #999;
            margin-left: 8px;
            font-weight: normal;
        }
        .kg-stat-desc {
            font-size: 13px;
            color: #999;
            line-height: 1.6;
            padding-bottom: 24px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: -1px;
        }
        .kg-stat-footer {
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to top, rgba(0,0,0,.02) 0%, transparent 100%);
            margin: 20px -24px 0;
            font-size: 13px;
            color: #999;
            opacity: 0;
            transition: opacity .3s ease;
        }
        .kg-stat-card:hover .kg-stat-footer {
            opacity: 1;
        }
        .kg-footer {
            text-align: center;
            padding: 30px 20px;
            color: #999;
            font-size: 14px;
        }
        /* 简易图标 */
        .icon {
            display: inline-block;
            width: 1em;
            height: 1em;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="kg-dashboard-header">
        <h1 class="kg-dashboard-title">{{ board_title|default('数据看板') }}</h1>
        <p class="kg-dashboard-subtitle">{{ board_subtitle|default('实时展示平台核心数据指标') }}</p>
    </div>

    <div class="kg-stats-container">
        <div class="kg-stats-row">
            {% for stat in stats %}
                <div class="kg-stat-card {{ stat.color }}">
                    <div class="kg-stat-header">
                        <div class="kg-stat-title">{{ stat.stat_name }}</div>
                        <div class="kg-stat-icon">
                            {% if stat.color == 'blue' %}📚{% endif %}
                            {% if stat.color == 'green' %}👨‍🏫{% endif %}
                            {% if stat.color == 'orange' %}👥{% endif %}
                            {% if stat.color == 'red' %}📈{% endif %}
                            {% if stat.color == 'cyan' %}📖{% endif %}
                            {% if stat.color == 'purple' %}⭐{% endif %}
                        </div>
                    </div>
                    <div class="kg-stat-value">
                        <span class="number">{{ stat.display_value }}</span><span class="kg-stat-unit">{{ stat.unit }}</span>
                    </div>
                    {% if stat.description %}
                        <div class="kg-stat-desc">{{ stat.description }}</div>
                    {% endif %}
                    <div class="kg-stat-footer">
                        <span>💡 数据实时更新</span>
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>

    <div class="kg-footer">
        <p>© {{ date('Y') }} {{ site_info.title }} - 数据看板</p>
    </div>
</body>
</html>


{% extends 'templates/main.volt' %}

{% block content %}

    <div class="kg-nav">
        <div class="kg-nav-right" style="position: absolute; right: 20px; top: 10px;">
            <button class="layui-btn layui-btn-sm layui-btn-normal" id="share-btn">
                <i class="layui-icon layui-icon-share"></i>分享
            </button>
        </div>
    </div>

    <div class="kg-dashboard-header">
        <h1 class="kg-dashboard-title">{{ board_title|default('数据看板') }}</h1>
        <p class="kg-dashboard-subtitle">{{ board_subtitle|default('实时展示平台核心数据指标') }}</p>
    </div>

    <style>
        .kg-dashboard-header {
            text-align: center;
            padding: 40px 20px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: -20px -20px 30px;
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
            font-size: 42px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            letter-spacing: 3px;
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
            font-size: 14px;
            color: rgba(255,255,255,0.85);
            margin: 0;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }
        .kg-stats-container {
            padding: 20px;
        }
        .kg-stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 24px 20px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,.06);
            transition: all .3s cubic-bezier(.4,0,.2,1);
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid transparent;
        }
        .kg-stat-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--card-color) 0%, var(--card-color-light) 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s ease;
        }
        .kg-stat-card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,.12);
            transform: translateY(-4px);
        }
        .kg-stat-card:hover::before {
            transform: scaleX(1);
        }
        .kg-stat-card:hover .kg-stat-icon {
            transform: scale(1.1) rotate(5deg);
            opacity: 0.8;
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
            margin-bottom: 18px;
            min-height: 40px;
        }
        .kg-stat-title {
            font-size: 14px;
            color: #666;
            font-weight: 500;
            letter-spacing: 0.5px;
            line-height: 40px;
        }
        .kg-stat-icon {
            font-size: 40px;
            opacity: 0.15;
            transition: all .3s ease;
            color: var(--card-color);
            line-height: 1;
            flex-shrink: 0;
        }
        .kg-stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 12px;
            line-height: 1;
            font-family: 'Arial', sans-serif;
        }
        .kg-stat-value .number {
            display: inline-block;
            animation: numberPop 0.5s ease;
        }
        @keyframes numberPop {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); opacity: 1; }
        }
        .kg-stat-unit {
            font-size: 16px;
            color: #999;
            margin-left: 6px;
            font-weight: normal;
        }
        .kg-stat-desc {
            font-size: 12px;
            color: #999;
            line-height: 1.6;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: -1px;
        }
        .kg-stat-footer {
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to top, rgba(0,0,0,.02) 0%, transparent 100%);
            margin: 15px -20px 0;
            font-size: 12px;
            color: #999;
            opacity: 0;
            transition: opacity .3s ease;
        }
        .kg-stat-card:hover .kg-stat-footer {
            opacity: 1;
        }
    </style>

    <div class="kg-stats-container">
        <div class="layui-row layui-col-space15">
            {% for stat in stats %}
                <div class="layui-col-md6 layui-col-lg4">
                    <div class="kg-stat-card {{ stat.color }}">
                        <div class="kg-stat-header">
                            <div class="kg-stat-title">{{ stat.stat_name }}</div>
                            <i class="layui-icon {{ stat.icon }} kg-stat-icon"></i>
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
                </div>
            {% endfor %}
        </div>
    </div>

{% endblock %}

{% block include_js %}
    <script>
    layui.use(['layer', 'jquery'], function() {
        var layer = layui.layer;
        var $ = layui.jquery;

        $('#share-btn').on('click', function() {
            var shareUrl = window.location.origin + '/admin/data_board/show';
            
            layer.open({
                type: 1,
                title: '分享数据看板',
                area: ['500px', '200px'],
                content: '<div style="padding: 20px;">' +
                    '<p style="margin-bottom: 15px;">复制以下链接分享给他人：</p>' +
                    '<div class="layui-input-inline" style="width: 100%;">' +
                    '<input type="text" id="share-url-input" class="layui-input" value="' + shareUrl + '" readonly>' +
                    '</div>' +
                    '<div style="margin-top: 15px; text-align: center;">' +
                    '<button class="layui-btn layui-btn-sm" id="copy-url-btn">复制链接</button>' +
                    '</div>' +
                    '</div>',
                success: function() {
                    $('#copy-url-btn').on('click', function() {
                        var input = document.getElementById('share-url-input');
                        input.select();
                        document.execCommand('copy');
                        layer.msg('链接已复制到剪贴板', {icon: 1, time: 1500});
                    });
                }
            });
        });
    });
    </script>
{% endblock %}


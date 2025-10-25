{% extends 'templates/main.volt' %}

{% block content %}

    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a><cite>数据看板</cite></a>
            </span>
        </div>
        <div class="kg-nav-right">
            <a class="layui-btn layui-btn-sm layui-btn-normal" href="{{ url({'for':'admin.data_board.list'}) }}">
                <i class="layui-icon layui-icon-set"></i>数据管理
            </a>
        </div>
    </div>

    <style>
        .kg-stats-container {
            padding: 20px;
        }
        .kg-stat-card {
            background: #fff;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 2px 0 rgba(0,0,0,.05);
            transition: all .3s;
        }
        .kg-stat-card:hover {
            box-shadow: 0 2px 8px 0 rgba(0,0,0,.1);
        }
        .kg-stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .kg-stat-title {
            font-size: 14px;
            color: #666;
        }
        .kg-stat-icon {
            font-size: 32px;
            opacity: 0.3;
        }
        .kg-stat-icon.blue { color: #1E9FFF; }
        .kg-stat-icon.green { color: #5FB878; }
        .kg-stat-icon.orange { color: #FFB800; }
        .kg-stat-icon.red { color: #FF5722; }
        .kg-stat-icon.cyan { color: #00D7B9; }
        .kg-stat-icon.purple { color: #9C26B0; }
        .kg-stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .kg-stat-unit {
            font-size: 16px;
            color: #999;
            margin-left: 5px;
        }
        .kg-stat-desc {
            font-size: 12px;
            color: #999;
            line-height: 1.6;
        }
    </style>

    <div class="kg-stats-container">
        <div class="layui-row layui-col-space15">
            {% for stat in stats %}
                <div class="layui-col-md6 layui-col-lg4">
                    <div class="kg-stat-card">
                        <div class="kg-stat-header">
                            <div class="kg-stat-title">{{ stat.stat_name }}</div>
                            <i class="layui-icon {{ stat.icon }} kg-stat-icon {{ stat.color }}"></i>
                        </div>
                        <div class="kg-stat-value">
                            {{ stat.display_value|number_format }}<span class="kg-stat-unit">{{ stat.unit }}</span>
                        </div>
                        {% if stat.description %}
                            <div class="kg-stat-desc">{{ stat.description }}</div>
                        {% endif %}
                    </div>
                </div>
            {% endfor %}
        </div>
    </div>

{% endblock %}


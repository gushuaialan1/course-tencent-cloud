{% extends "templates/main.volt" %}

{% block content %}

<div class="kg-nav">
    <div class="kg-nav-left">
        <span class="layui-breadcrumb">
            <a><cite>数据看板</cite></a>
            <a><cite>数据管理</cite></a>
        </span>
    </div>
    <div class="kg-nav-right">
        <a class="layui-btn layui-btn-sm layui-btn-normal" href="{{ url({'for':'admin.data_board.show'}) }}">
            <i class="layui-icon layui-icon-chart"></i>查看全局看板
        </a>
        <a class="layui-btn layui-btn-sm layui-btn-warm" href="{{ url({'for':'admin.data_board.show_course'}) }}">
            <i class="layui-icon layui-icon-chart-screen"></i>查看课程看板
        </a>
    </div>
</div>

<div class="layui-tab layui-tab-brief" lay-filter="data-board-tab">
    <ul class="layui-tab-title">
        <li><a href="{{ url({'for':'admin.data_board.list'}) }}">全局统计</a></li>
        <li class="layui-this">课程统计</li>
    </ul>
    <div class="layui-tab-content">
        <div class="layui-tab-item layui-show">

<div class="layui-card" style="margin-bottom: 20px;">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-search"></i> 课程选择
    </div>
    <div class="layui-card-body">
        <form class="layui-form" id="course-select-form">
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px;">选择课程</label>
                <div class="layui-input-block" style="margin-left: 130px;">
                    <input type="text" id="course-search" class="layui-input" placeholder="请输入课程名称搜索" autocomplete="off">
                    <input type="hidden" id="selected-course-id" value="{{ current_course_id }}">
                    {% if course_info %}
                    <div id="current-course-info" style="margin-top: 10px; padding: 10px; background: #f8f8f8; border-radius: 4px;">
                        <div style="display: flex; align-items: center;">
                            {% if course_info.cover %}
                            <img src="{{ course_info.cover }}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 12px;">
                            {% endif %}
                            <div>
                                <div style="font-weight: bold; margin-bottom: 4px;">{{ course_info.title }}</div>
                                <div style="font-size: 12px; color: #999;">ID: {{ course_info.id }}</div>
                            </div>
                        </div>
                    </div>
                    {% endif %}
                </div>
            </div>
            {% if not course_info %}
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px;"></label>
                <div class="layui-input-block" style="margin-left: 130px;">
                    <div class="layui-text" style="color: #FF5722;">
                        <i class="layui-icon layui-icon-tips"></i> 请先搜索并选择一个课程
                    </div>
                </div>
            </div>
            {% endif %}
        </form>
    </div>
</div>

{% if course_info %}
<div class="layui-card" style="margin-bottom: 20px;">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-edit"></i> 课程简介设置
    </div>
    <div class="layui-card-body">
        <form class="layui-form" id="intro-form">
            <div class="layui-form-item layui-form-text">
                <label class="layui-form-label" style="width: 100px;">课程简介</label>
                <div class="layui-input-block" style="margin-left: 130px;">
                    <textarea name="course_intro" id="course_intro" class="layui-textarea" placeholder="请输入课程简介" rows="4">{{ course_intro }}</textarea>
                    <div class="layui-word-aux" style="margin-top: 5px;">简介将在课程看板页面显示，未设置时自动从课程信息获取</div>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px;"></label>
                <div class="layui-input-block" style="margin-left: 130px;">
                    <button type="button" class="layui-btn layui-btn-sm" id="save-intro-btn">
                        <i class="layui-icon layui-icon-ok"></i>保存简介
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-list"></i> 课程统计项列表
        <button class="layui-btn layui-btn-xs layui-btn-normal" id="refresh-course-all-btn" style="float: right; margin-top: -2px;">
            <i class="layui-icon layui-icon-refresh"></i>刷新全部真实数据
        </button>
    </div>
    <div class="layui-card-body">
        <table class="layui-table">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th width="150">统计项名称</th>
                    <th width="120">真实数据</th>
                    <th width="120">虚拟增量</th>
                    <th width="120">最终显示</th>
                    <th width="60">单位</th>
                    <th width="80">显示状态</th>
                    <th>说明</th>
                    <th width="200">操作</th>
                </tr>
            </thead>
            <tbody>
                {% for stat in stats %}
                <tr>
                    <td>{{ stat.id }}</td>
                    <td>
                        <i class="layui-icon {{ stat.icon }}" style="color: {% if stat.color == 'blue' %}#1E9FFF{% elseif stat.color == 'green' %}#5FB878{% elseif stat.color == 'orange' %}#FFB800{% elseif stat.color == 'red' %}#FF5722{% elseif stat.color == 'cyan' %}#00D7B9{% elseif stat.color == 'purple' %}#9C26B0{% endif %};"></i>
                        {{ stat.stat_name }}
                    </td>
                    <td><strong>{{ stat.real_value }}</strong></td>
                    <td>
                        <input type="number" class="layui-input virtual-input" data-id="{{ stat.id }}" value="{{ stat.virtual_value }}" style="width: 100px; display: inline-block;">
                    </td>
                    <td><span class="display-value" data-real="{{ stat.real_value }}" data-virtual="{{ stat.virtual_value }}">{{ stat.display_value }}</span></td>
                    <td>{{ stat.unit }}</td>
                    <td>
                        <input type="checkbox" class="visible-switch" data-id="{{ stat.id }}" lay-skin="switch" lay-text="显示|隐藏" {% if stat.is_visible == 1 %}checked{% endif %}>
                    </td>
                    <td style="color: #999; font-size: 12px;">{{ stat.description }}</td>
                    <td>
                        <div class="layui-btn-group">
                            <button class="layui-btn layui-btn-xs save-stat-btn" data-id="{{ stat.id }}" title="保存">
                                <i class="layui-icon layui-icon-ok"></i>保存
                            </button>
                            <button class="layui-btn layui-btn-xs layui-btn-normal refresh-course-single-btn" 
                                    data-id="{{ stat.id }}" title="刷新真实数据">
                                <i class="layui-icon layui-icon-refresh"></i>刷新
                            </button>
                        </div>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
    </div>
</div>

<div class="layui-card" style="margin-top: 20px;">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-tips"></i> 操作提示
    </div>
    <div class="layui-card-body">
        <div class="layui-text">
            <ul>
                <li>首先需要搜索并选择一个课程，系统会自动初始化该课程的统计数据</li>
                <li>真实数据会根据课程实际情况自动计算，点击"刷新"按钮可更新</li>
                <li>虚拟增量值会和真实数据相加，得到最终显示值</li>
                <li>修改虚拟增量或显示状态后，需点击"保存"按钮保存更改</li>
                <li>课程时长单位为小时（自动从秒转换），作业平均分保留1位小数</li>
            </ul>
        </div>
    </div>
</div>

{% endif %}

        </div>
    </div>
</div>

{% endblock %}

{% block include_js %}
    {{ js_include('admin/js/data_board.course.js') }}
{% endblock %}



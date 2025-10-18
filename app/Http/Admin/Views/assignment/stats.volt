{% extends "templates/main.volt" %}

{% block content %}

<div class="kg-nav">
    <div class="kg-nav-left">
        <span class="layui-breadcrumb">
            <a><cite>作业管理</cite></a>
            <a><cite>作业统计</cite></a>
        </span>
    </div>
    <div class="kg-nav-right">
        <a class="layui-btn layui-btn-sm" href="{{ url({'for':'admin.assignment.list'}) }}">
            <i class="layui-icon layui-icon-return"></i>返回列表
        </a>
    </div>
</div>

<!-- 总体统计卡片 -->
<div class="layui-row layui-col-space15">
    <div class="layui-col-md3">
        <div class="layui-card">
            <div class="layui-card-body">
                <div class="kg-stat-card">
                    <div class="kg-stat-value">{{ total_stats.total_assignments }}</div>
                    <div class="kg-stat-label">总作业数</div>
                </div>
            </div>
        </div>
    </div>
    <div class="layui-col-md3">
        <div class="layui-card">
            <div class="layui-card-body">
                <div class="kg-stat-card">
                    <div class="kg-stat-value" style="color: #16BAAA;">{{ total_stats.total_submissions }}</div>
                    <div class="kg-stat-label">总提交数</div>
                </div>
            </div>
        </div>
    </div>
    <div class="layui-col-md3">
        <div class="layui-card">
            <div class="layui-card-body">
                <div class="kg-stat-card">
                    <div class="kg-stat-value" style="color: #1E9FFF;">{{ total_stats.avg_completion_rate }}%</div>
                    <div class="kg-stat-label">平均完成率</div>
                </div>
            </div>
        </div>
    </div>
    <div class="layui-col-md3">
        <div class="layui-card">
            <div class="layui-card-body">
                <div class="kg-stat-card">
                    <div class="kg-stat-value" style="color: #FFB800;">{{ total_stats.total_pending }}</div>
                    <div class="kg-stat-label">待批改数</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 作业统计列表 -->
<div class="layui-card">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-chart"></i> 作业统计详情
    </div>
    <div class="layui-card-body">
        <table class="layui-table">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>作业标题</th>
                    <th width="100">总学生数</th>
                    <th width="100">已提交</th>
                    <th width="100">已批改</th>
                    <th width="100">待批改</th>
                    <th width="100">完成率</th>
                    <th width="100">平均分</th>
                    <th width="120">操作</th>
                </tr>
            </thead>
            <tbody>
                {% for item in assignments_stats %}
                {% set stats = item.stats %}
                <tr>
                    <td>{{ item.id }}</td>
                    <td>
                        <a href="{{ url({'for':'admin.assignment.show','id':item.id}) }}" class="kg-link">
                            {{ item.title }}
                        </a>
                    </td>
                    <td>{{ stats.total|default(0) }}</td>
                    <td>
                        <span class="layui-badge layui-bg-blue">{{ stats.submitted|default(0) }}</span>
                    </td>
                    <td>
                        <span class="layui-badge layui-bg-green">{{ stats.graded|default(0) }}</span>
                    </td>
                    <td>
                        <span class="layui-badge layui-bg-orange">{{ stats.pending|default(0) }}</span>
                    </td>
                    <td>
                        {% if stats.total > 0 %}
                        {% set completion = (stats.submitted / stats.total * 100)|number_format(1) %}
                        <div class="layui-progress" lay-showPercent="true">
                            <div class="layui-progress-bar" lay-percent="{{ completion }}%"></div>
                        </div>
                        {% else %}
                        <span class="kg-text-muted">0%</span>
                        {% endif %}
                    </td>
                    <td>
                        {% if stats.avg_score %}
                        <strong style="color: #4CAF50;">{{ stats.avg_score|number_format(1) }}</strong>
                        {% else %}
                        <span class="kg-text-muted">-</span>
                        {% endif %}
                    </td>
                    <td>
                        <a href="{{ url({'for':'admin.assignment.grading.list'}) }}?assignment_id={{ item.id }}" 
                           class="layui-btn layui-btn-xs">
                            查看提交
                        </a>
                    </td>
                </tr>
                {% endfor %}
                
                {% if assignments_stats is empty %}
                <tr>
                    <td colspan="9" class="kg-text-center kg-text-muted">
                        <p style="padding: 40px 0;">暂无作业统计数据</p>
                    </td>
                </tr>
                {% endif %}
            </tbody>
        </table>
    </div>
</div>

{% endblock %}

{% block include_js %}
<script>
layui.use(['element'], function(){
    var element = layui.element;
    element.render('progress');
});
</script>
{% endblock %}


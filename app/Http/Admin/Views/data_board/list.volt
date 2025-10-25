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
            <i class="layui-icon layui-icon-chart"></i>查看看板
        </a>
        <button class="layui-btn layui-btn-sm" id="refresh-all-btn">
            <i class="layui-icon layui-icon-refresh"></i>刷新全部真实数据
        </button>
    </div>
</div>

<div class="layui-card" style="margin-bottom: 20px;">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-set-fill"></i> 看板标题设置
    </div>
    <div class="layui-card-body">
        <form class="layui-form" id="title-form">
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px;">标题内容</label>
                <div class="layui-input-block" style="margin-left: 130px;">
                    <input type="text" name="board_title" id="board_title" class="layui-input" value="{{ board_title|default('数据看板') }}" placeholder="请输入看板标题">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label" style="width: 100px;"></label>
                <div class="layui-input-block" style="margin-left: 130px;">
                    <button type="button" class="layui-btn layui-btn-sm" id="save-title-btn">
                        <i class="layui-icon layui-icon-ok"></i>保存标题
                    </button>
                    <span class="layui-word-aux">修改后将在数据看板页面显示</span>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-list"></i> 统计项列表
    </div>
    <div class="layui-card-body">
        <table class="layui-table">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th width="120">统计项名称</th>
                    <th width="100">真实数据</th>
                    <th width="100">虚拟增量</th>
                    <th width="100">最终显示</th>
                    <th width="60">单位</th>
                    <th width="80">排序</th>
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
                    <td>
                        <span class="layui-badge layui-bg-blue">{{ stat.real_value }}</span>
                    </td>
                    <td>
                        <span class="layui-badge layui-bg-orange">{{ stat.virtual_value }}</span>
                    </td>
                    <td>
                        <span class="layui-badge layui-bg-green">{{ stat.display_value }}</span>
                    </td>
                    <td>{{ stat.unit }}</td>
                    <td>{{ stat.sort_order }}</td>
                    <td>
                        {% if stat.is_visible == 1 %}
                        <span class="layui-badge layui-bg-green">显示</span>
                        {% else %}
                        <span class="layui-badge layui-bg-gray">隐藏</span>
                        {% endif %}
                    </td>
                    <td class="kg-text-muted">{{ stat.description }}</td>
                    <td>
                        <div class="kg-table-actions">
                            <a href="{{ url({'for':'admin.data_board.edit','id':stat.id}) }}" 
                               class="layui-btn layui-btn-xs" title="编辑">
                                <i class="layui-icon layui-icon-edit"></i>编辑
                            </a>
                            <button class="layui-btn layui-btn-xs layui-btn-normal refresh-single-btn" 
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

{% endblock %}

{% block include_js %}
    {{ js_include('admin/js/data_board.list.js') }}
{% endblock %}


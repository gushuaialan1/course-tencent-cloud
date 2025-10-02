{% extends 'templates/main.volt' %}

{% block content %}

    {% set templates_url = url({'for':'admin.knowledge_graph.templates'}) %}

    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a><cite>知识图谱管理</cite></a>
            </span>
        </div>
        <div class="kg-nav-right">
            <a class="layui-btn layui-btn-sm layui-btn-warm" href="{{ templates_url }}">
                <i class="layui-icon layui-icon-template-1"></i>图谱模板
            </a>
        </div>
    </div>

    <table class="layui-table layui-form kg-table" lay-size="lg">
        <colgroup>
            <col>
            <col width="20%">
            <col width="15%">
            <col width="15%">
        </colgroup>
        <thead>
        <tr>
            <th>课程信息</th>
            <th>知识图谱统计</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        </thead>
        <tbody>
        {% if courses is empty %}
            <tr>
                <td colspan="4" class="center">暂无数据</td>
            </tr>
        {% else %}
            {% for course in courses %}
                {% set editor_url = url({'for':'admin.knowledge_graph.editor','courseId':course.id}) %}
                {% set nodes_url = url({'for':'admin.knowledge_graph.nodes','courseId':course.id}) %}
                {% set export_url = url({'for':'admin.knowledge_graph.export','courseId':course.id}) %}
                <tr>
                    <td>
                        <p><strong>{{ course.title }}</strong> <span class="layui-badge">ID: {{ course.id }}</span></p>
                        {% if course.category is defined and course.category.name is defined %}
                            <p class="meta"><span>分类：{{ course.category.name }}</span></p>
                        {% endif %}
                        {% if course.teacher is defined and course.teacher.name is defined %}
                            <p class="meta"><span>讲师：{{ course.teacher.name }}</span></p>
                        {% endif %}
                    </td>
                    <td>
                        {% if course.node_stats is defined %}
                            <p>节点总数：<span class="layui-badge layui-bg-blue">{{ course.node_stats.total }}</span></p>
                            {% if course.node_stats.by_type is defined %}
                                <p class="meta">
                                    {% for type, stat in course.node_stats.by_type %}
                                        {% if stat.count > 0 %}
                                            <span>{{ stat.label }}：{{ stat.count }}</span>
                                        {% endif %}
                                    {% endfor %}
                                </p>
                            {% endif %}
                        {% else %}
                            <p class="meta">暂无图谱数据</p>
                        {% endif %}
                    </td>
                    <td>
                        <p>{{ date('Y-m-d', course.create_time) }}</p>
                        <p class="meta">{{ date('H:i:s', course.create_time) }}</p>
                    </td>
                    <td class="center">
                        <div class="layui-btn-group">
                            <a class="layui-btn layui-btn-sm layui-btn-normal" href="{{ editor_url }}" title="图谱编辑器">
                                <i class="layui-icon layui-icon-edit"></i>编辑图谱
                            </a>
                            <button class="layui-btn layui-btn-sm layui-btn-primary" onclick="viewNodes('{{ nodes_url }}')" title="节点列表">
                                <i class="layui-icon layui-icon-list"></i>
                            </button>
                            <button class="layui-btn layui-btn-sm layui-btn-primary" onclick="exportGraph('{{ export_url }}')" title="导出图谱">
                                <i class="layui-icon layui-icon-export"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            {% endfor %}
        {% endif %}
        </tbody>
    </table>

    <script>
        layui.use(['layer'], function(){
            var layer = layui.layer;

            // 查看节点列表
            window.viewNodes = function(url) {
                layer.open({
                    type: 2,
                    title: '节点列表',
                    area: ['900px', '600px'],
                    content: url
                });
            };

            // 导出图谱
            window.exportGraph = function(url) {
                layer.confirm('请选择导出格式', {
                    btn: ['JSON格式', 'CSV格式', '取消']
                }, function(index) {
                    window.location.href = url + '?format=json';
                    layer.close(index);
                }, function(index) {
                    window.location.href = url + '?format=csv';
                    layer.close(index);
                });
            };
        });
    </script>

{% endblock %}


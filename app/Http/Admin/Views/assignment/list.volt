{% extends "templates/main.volt" %}

{% block link_css %}
{{ css_link('admin/css/assignment.css') }}
{% endblock %}

{% block content %}

<div class="kg-nav">
    <div class="kg-nav-left">
        <span class="layui-breadcrumb">
            <a><cite>作业管理</cite></a>
            <a><cite>作业列表</cite></a>
        </span>
    </div>
    <div class="kg-nav-right">
        <a class="layui-btn layui-btn-sm layui-btn-normal" href="{{ url({'for':'admin.assignment.create'}) }}">
            <i class="layui-icon layui-icon-add-1"></i>创建作业
        </a>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-list"></i> 作业列表
    </div>
    <div class="layui-card-body">
        <!-- 搜索表单 -->
        <form class="layui-form kg-form" method="get">
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">课程</label>
                    <div class="layui-input-inline">
                        <select name="course_id">
                            <option value="">全部课程</option>
                            {% for course in courses %}
                            <option value="{{ course.id }}" {% if course.id == request.get('course_id') %}selected{% endif %}>
                                {{ course.title }}
                            </option>
                            {% endfor %}
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">状态</label>
                    <div class="layui-input-inline">
                        <select name="status">
                            <option value="">全部状态</option>
                            <option value="draft" {% if request.get('status') == 'draft' %}selected{% endif %}>草稿</option>
                            <option value="published" {% if request.get('status') == 'published' %}selected{% endif %}>已发布</option>
                            <option value="closed" {% if request.get('status') == 'closed' %}selected{% endif %}>已关闭</option>
                            <option value="archived" {% if request.get('status') == 'archived' %}selected{% endif %}>已归档</option>
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">类型</label>
                    <div class="layui-input-inline">
                        <select name="type">
                            <option value="">全部类型</option>
                            <option value="choice" {% if request.get('type') == 'choice' %}selected{% endif %}>选择题</option>
                            <option value="essay" {% if request.get('type') == 'essay' %}selected{% endif %}>简答题</option>
                            <option value="upload" {% if request.get('type') == 'upload' %}selected{% endif %}>文件上传</option>
                            <option value="mixed" {% if request.get('type') == 'mixed' %}selected{% endif %}>混合题型</option>
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <button class="layui-btn" type="submit">
                        <i class="layui-icon layui-icon-search"></i>搜索
                    </button>
                </div>
            </div>
        </form>

        <!-- 批量操作工具栏 -->
        <div class="kg-toolbar">
            <div class="kg-toolbar-left">
                <button class="layui-btn layui-btn-sm" data-action="publish">
                    <i class="layui-icon layui-icon-release"></i>批量发布
                </button>
                <button class="layui-btn layui-btn-sm layui-btn-warm" data-action="close">
                    <i class="layui-icon layui-icon-close"></i>批量关闭
                </button>
                <button class="layui-btn layui-btn-sm layui-btn-danger" data-action="archive">
                    <i class="layui-icon layui-icon-delete"></i>批量归档
                </button>
            </div>
            <div class="kg-toolbar-right">
                <span class="kg-toolbar-text">共 {{ pager.total }} 条记录</span>
            </div>
        </div>

        <!-- 作业列表表格 -->
        <table class="layui-table" lay-filter="assignment-table">
            <thead>
                <tr>
                    <th width="30">
                        <input type="checkbox" lay-filter="check-all">
                    </th>
                    <th width="60">ID</th>
                    <th>标题</th>
                    <th width="120">课程</th>
                    <th width="80">类型</th>
                    <th width="80">总分</th>
                    <th width="80">状态</th>
                    <th width="100">提交/总数</th>
                    <th width="120">截止时间</th>
                    <th width="120">创建时间</th>
                    <th width="180">操作</th>
                </tr>
            </thead>
            <tbody>
                {% for assignment in assignments %}
                <tr>
                    <td>
                        <input type="checkbox" name="assignment_ids" value="{{ assignment.id }}" lay-filter="check-item">
                    </td>
                    <td>{{ assignment.id }}</td>
                    <td>
                        <a href="{{ url({'for':'admin.assignment.show','id':assignment.id}) }}" class="kg-link">
                            {{ assignment.title }}
                        </a>
                        {% if assignment.description %}
                        <p class="kg-text-muted">{{ substr(assignment.description, 0, 50) }}...</p>
                        {% endif %}
                    </td>
                    <td>{{ assignment.course_title|default('') }}</td>
                    <td>
                        {% if assignment.assignment_type == 'choice' %}
                        <span class="layui-badge layui-bg-blue">选择题</span>
                        {% elseif assignment.assignment_type == 'essay' %}
                        <span class="layui-badge layui-bg-green">简答题</span>
                        {% elseif assignment.assignment_type == 'upload' %}
                        <span class="layui-badge layui-bg-orange">文件上传</span>
                        {% else %}
                        <span class="layui-badge">混合题型</span>
                        {% endif %}
                    </td>
                    <td>{{ assignment.max_score }}</td>
                    <td>
                        {% if assignment.status == 'draft' %}
                        <span class="layui-badge layui-bg-gray">草稿</span>
                        {% elseif assignment.status == 'published' %}
                        <span class="layui-badge layui-bg-green">已发布</span>
                        {% elseif assignment.status == 'closed' %}
                        <span class="layui-badge layui-bg-orange">已关闭</span>
                        {% else %}
                        <span class="layui-badge layui-bg-red">已归档</span>
                        {% endif %}
                    </td>
                    <td>
                        {% set stats = assignment.submission_stats %}
                        <span class="kg-text-info">{{ stats.submitted|default(0) }}/{{ stats.total|default(0) }}</span>
                    </td>
                    <td>
                        {% if assignment.due_date > 0 %}
                        {{ date('Y-m-d H:i', assignment.due_date) }}
                        {% else %}
                        <span class="kg-text-muted">无限制</span>
                        {% endif %}
                    </td>
                    <td>{{ date('Y-m-d H:i', assignment.create_time) }}</td>
                    <td>
                        <div class="kg-table-actions">
                            <a href="{{ url({'for':'admin.assignment.edit','id':assignment.id}) }}" 
                               class="layui-btn layui-btn-xs" title="编辑">
                                <i class="layui-icon layui-icon-edit"></i>
                            </a>
                            
                            {% if assignment.status == 'draft' %}
                            <button class="layui-btn layui-btn-xs layui-btn-normal" 
                                    data-action="publish" data-id="{{ assignment.id }}" title="发布">
                                <i class="layui-icon layui-icon-release"></i>
                            </button>
                            {% endif %}
                            
                            <a href="{{ url({'for':'admin.assignment.submission.list'}) }}?assignment_id={{ assignment.id }}" 
                               class="layui-btn layui-btn-xs layui-btn-warm" title="查看提交">
                                <i class="layui-icon layui-icon-list"></i>
                            </a>
                            
                            <button class="layui-btn layui-btn-xs" 
                                    data-action="duplicate" data-id="{{ assignment.id }}" title="复制">
                                <i class="layui-icon layui-icon-file"></i>
                            </button>
                            
                            <button class="layui-btn layui-btn-xs layui-btn-danger" 
                                    data-action="delete" data-id="{{ assignment.id }}" title="删除">
                                <i class="layui-icon layui-icon-delete"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                {% endfor %}
            </tbody>
        </table>

        <!-- 分页 -->
        {% include "partials/pager.volt" %}
    </div>
</div>

{% endblock %}

{% block include_js %}
{{ js_include('admin/js/assignment.list.js') }}
{% endblock %}

{% extends "templates/main.volt" %}

{% block link_css %}
{{ css_link('admin/css/assignment.css') }}
<style>
.grading-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.badge-pending {
    background-color: #FFB800;
    color: #fff;
}
.badge-grading {
    background-color: #1E9FFF;
    color: #fff;
}
.badge-completed {
    background-color: #5FB878;
    color: #fff;
}
.badge-late {
    background-color: #FF5722;
    color: #fff;
}
.score-display {
    font-weight: bold;
    color: #009688;
}
</style>
{% endblock %}

{% block content %}

<div class="kg-nav">
    <div class="kg-nav-left">
        <span class="layui-breadcrumb">
            <a><cite>作业管理</cite></a>
            <a><cite>批改工作台</cite></a>
        </span>
    </div>
    <div class="kg-nav-right">
        <a class="layui-btn layui-btn-sm layui-btn-normal" href="{{ url({'for':'admin.assignment.list'}) }}">
            <i class="layui-icon layui-icon-return"></i>返回作业列表
        </a>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-form"></i> 待批改作业列表
    </div>
    <div class="layui-card-body">
        <!-- 搜索表单 -->
        <form class="layui-form kg-form" method="get">
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">课程</label>
                    <div class="layui-input-inline">
                        <select name="course_id" lay-filter="course-select">
                            <option value="">全部课程</option>
                            {% for course in courses %}
                            <option value="{{ course.id }}" {% if course.id == course_id %}selected{% endif %}>
                                {{ course.title }}
                            </option>
                            {% endfor %}
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">作业</label>
                    <div class="layui-input-inline">
                        <select name="assignment_id">
                            <option value="">全部作业</option>
                            {% for assignment in assignments %}
                            <option value="{{ assignment.id }}" {% if assignment.id == assignment_id %}selected{% endif %}>
                                {{ assignment.title }}
                            </option>
                            {% endfor %}
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">提交状态</label>
                    <div class="layui-input-inline">
                        <select name="status">
                            <option value="">全部状态</option>
                            <option value="submitted" {% if status == 'submitted' %}selected{% endif %}>已提交</option>
                            <option value="auto_graded" {% if status == 'auto_graded' %}selected{% endif %}>自动批改完成</option>
                            <option value="grading" {% if status == 'grading' %}selected{% endif %}>批改中</option>
                            <option value="graded" {% if status == 'graded' %}selected{% endif %}>已批改</option>
                            <option value="returned" {% if status == 'returned' %}selected{% endif %}>已退回</option>
                        </select>
                    </div>
                </div>
                <div class="layui-inline">
                    <label class="layui-form-label">迟交筛选</label>
                    <div class="layui-input-inline">
                        <select name="is_late">
                            <option value="">全部</option>
                            <option value="1" {% if is_late == 1 %}selected{% endif %}>仅迟交</option>
                            <option value="0" {% if is_late == 0 %}selected{% endif %}>仅按时</option>
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
                <button class="layui-btn layui-btn-sm" data-action="assign">
                    <i class="layui-icon layui-icon-user"></i>分配给我
                </button>
                <button class="layui-btn layui-btn-sm layui-btn-normal" data-action="auto_grade">
                    <i class="layui-icon layui-icon-ok"></i>自动评分
                </button>
            </div>
            <div class="kg-toolbar-right">
                <span class="kg-toolbar-text">共 {{ pager.total }} 条待批改</span>
            </div>
        </div>

        <!-- 提交列表表格 -->
        <table class="layui-table" lay-filter="grading-table">
            <thead>
                <tr>
                    <th width="30">
                        <input type="checkbox" lay-filter="check-all">
                    </th>
                    <th width="60">ID</th>
                    <th>作业标题</th>
                    <th>课程名称</th>
                    <th width="100">学生姓名</th>
                    <th width="100">作业类型</th>
                    <th width="100">批改状态</th>
                    <th width="120">提交时间</th>
                    <th width="80">是否迟交</th>
                    <th width="100">得分</th>
                    <th width="100">批改老师</th>
                    <th width="180">操作</th>
                </tr>
            </thead>
            <tbody>
                {% if submissions|length > 0 %}
                    {% for submission in submissions %}
                    <tr>
                        <td>
                            <input type="checkbox" class="submission-check" data-id="{{ submission.id }}" lay-filter="check-item">
                        </td>
                        <td>{{ submission.id }}</td>
                        <td>{{ submission.assignment_title }}</td>
                        <td>{{ submission.course_title }}</td>
                        <td>{{ submission.user_name }}</td>
                        <td>
                            {% if submission.assignment_type == 'choice' %}
                                <span class="layui-badge layui-bg-blue">选择题</span>
                            {% elseif submission.assignment_type == 'essay' %}
                                <span class="layui-badge layui-bg-orange">简答题</span>
                            {% elseif submission.assignment_type == 'upload' %}
                                <span class="layui-badge layui-bg-green">文件上传</span>
                            {% else %}
                                <span class="layui-badge">混合题型</span>
                            {% endif %}
                        </td>
                        <td>
                            {% if submission.status == 'submitted' %}
                                <span class="grading-badge badge-pending">待批改</span>
                            {% elseif submission.status == 'auto_graded' %}
                                <span class="grading-badge badge-completed">自动批改完成</span>
                            {% elseif submission.status == 'grading' %}
                                <span class="grading-badge badge-grading">批改中</span>
                            {% elseif submission.status == 'graded' %}
                                <span class="grading-badge badge-completed">已完成</span>
                            {% elseif submission.status == 'returned' %}
                                <span class="grading-badge badge-returned">已退回</span>
                            {% endif %}
                        </td>
                        <td>{{ date('Y-m-d H:i', submission.submit_time) }}</td>
                        <td>
                            {% if submission.is_late == 1 %}
                                <span class="grading-badge badge-late">迟交</span>
                            {% else %}
                                <span class="layui-badge layui-bg-green">按时</span>
                            {% endif %}
                        </td>
                        <td>
                            {% if submission.score is not null %}
                                <span class="score-display">{{ submission.score }}/{{ submission.max_score }}</span>
                            {% else %}
                                <span class="layui-badge layui-bg-gray">未评分</span>
                            {% endif %}
                        </td>
                        <td>{{ submission.grader_name|default('-') }}</td>
                        <td>
                            {% if submission.status == 'auto_graded' %}
                                <!-- 自动批改完成：只能查看，跟前台一样 -->
                                <a class="layui-btn layui-btn-xs layui-btn-primary" href="{{ url({'for':'home.assignment.show', 'id': submission.assignment_id}) }}" target="_blank">
                                    <i class="layui-icon layui-icon-file"></i>查看作业
                                </a>
                            {% elseif submission.status == 'submitted' %}
                                <!-- 待批改：显示批改按钮 -->
                                <a class="layui-btn layui-btn-xs layui-btn-normal" href="{{ url({'for':'admin.assignment.submission.detail', 'id': submission.id}) }}">
                                    <i class="layui-icon layui-icon-form"></i>批改
                                </a>
                            {% elseif submission.status == 'grading' or submission.status == 'graded' %}
                                <!-- 批改中/已完成：显示查看+重新批改 -->
                                <a class="layui-btn layui-btn-xs layui-btn-primary" href="{{ url({'for':'admin.assignment.submission.detail', 'id': submission.id}) }}">
                                    <i class="layui-icon layui-icon-file"></i>查看
                                </a>
                                <button class="layui-btn layui-btn-xs layui-btn-warm btn-regrade" data-id="{{ submission.id }}">
                                    <i class="layui-icon layui-icon-edit"></i>重新批改
                                </button>
                            {% endif %}
                        </td>
                    </tr>
                    {% endfor %}
                {% else %}
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 30px;">
                            <i class="layui-icon layui-icon-face-cry" style="font-size: 60px; color: #ccc;"></i>
                            <p style="color: #999; margin-top: 10px;">暂无待批改作业</p>
                        </td>
                    </tr>
                {% endif %}
            </tbody>
        </table>

        <!-- 分页 -->
        {% if pager.total > 0 %}
        <div id="pager-container"></div>
        {% endif %}
    </div>
</div>

{% endblock %}

{% block link_js %}
<script>
layui.use(['form', 'laypage', 'layer'], function(){
    var form = layui.form;
    var laypage = layui.laypage;
    var layer = layui.layer;
    var $ = layui.jquery;

    // 分页
    {% if pager.total > 0 %}
    laypage.render({
        elem: 'pager-container',
        count: {{ pager.total }},
        limit: {{ pager.limit }},
        curr: {{ pager.page }},
        layout: ['count', 'prev', 'page', 'next', 'limit', 'skip'],
        jump: function(obj, first){
            if(!first){
                var url = new URL(window.location.href);
                url.searchParams.set('page', obj.curr);
                url.searchParams.set('limit', obj.limit);
                window.location.href = url.toString();
            }
        }
    });
    {% endif %}

    // 全选/取消全选
    form.on('checkbox(check-all)', function(data){
        $('.submission-check').each(function(){
            this.checked = data.elem.checked;
        });
        form.render('checkbox');
    });

    // 单项选择
    form.on('checkbox(check-item)', function(data){
        var allChecked = true;
        $('.submission-check').each(function(){
            if(!this.checked){
                allChecked = false;
                return false;
            }
        });
        $('input[lay-filter="check-all"]')[0].checked = allChecked;
        form.render('checkbox');
    });

    // 批量操作
    $('.kg-toolbar-left button').on('click', function(){
        var action = $(this).data('action');
        var checkedIds = [];
        
        $('.submission-check:checked').each(function(){
            checkedIds.push($(this).data('id'));
        });

        if(checkedIds.length === 0){
            layer.msg('请先选择要操作的提交', {icon: 0});
            return;
        }

        var actionText = '';
        switch(action){
            case 'assign':
                actionText = '分配给我';
                break;
            case 'auto_grade':
                actionText = '自动评分';
                break;
        }

        layer.confirm('确定要' + actionText + '吗？共选中 ' + checkedIds.length + ' 个提交', function(index){
            $.ajax({
                url: '{{ url({"for":"admin.assignment.grading.batch"}) }}',
                type: 'POST',
                data: {
                    action: action,
                    ids: checkedIds.join(',')
                },
                dataType: 'json',
                success: function(res){
                    if(res.code === 0){
                        layer.msg(res.data.message, {icon: 1}, function(){
                            window.location.reload();
                        });
                    } else {
                        layer.msg(res.msg || '操作失败', {icon: 2});
                    }
                },
                error: function(){
                    layer.msg('网络错误', {icon: 2});
                }
            });
            layer.close(index);
        });
    });

    // 重新批改
    $('.btn-regrade').on('click', function(){
        var submissionId = $(this).data('id');
        layer.confirm('确定要重新批改这个提交吗？', function(index){
            window.location.href = '{{ url({"for":"admin.assignment.submission.detail"}) }}/' + submissionId;
            layer.close(index);
        });
    });

    // 课程选择联动
    form.on('select(course-select)', function(data){
        // 可以在这里实现作业列表的动态更新
        // 简化版：直接提交表单
        // $(data.elem).closest('form').submit();
    });
});
</script>
{% endblock %}


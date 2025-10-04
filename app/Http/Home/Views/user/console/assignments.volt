{% extends 'templates/main.volt' %}

{% block content %}

    <div class="layout-main">
        <div class="my-sidebar">{{ partial('user/console/menu') }}</div>
        <div class="my-content">
            <div class="wrap">
                <div class="my-nav">
                    <span class="title">我的作业</span>
                </div>
                
                {# 筛选栏 #}
                <div class="filter-bar" style="margin-bottom: 20px; padding: 15px; background: #FAFAFA; border-radius: 2px;">
                    <form class="layui-form" action="{{ url({'for':'home.uc.assignments'}) }}" method="GET">
                        <div class="layui-form-item" style="margin-bottom: 0;">
                            <label class="layui-form-label" style="width: 80px;">状态筛选</label>
                            <div class="layui-input-inline" style="width: 150px;">
                                <select name="status" lay-filter="status-filter">
                                    <option value="">全部</option>
                                    <option value="draft" {% if request.getQuery('status') == 'draft' %}selected{% endif %}>草稿</option>
                                    <option value="pending" {% if request.getQuery('status') == 'pending' %}selected{% endif %}>待批改</option>
                                    <option value="grading" {% if request.getQuery('status') == 'grading' %}selected{% endif %}>批改中</option>
                                    <option value="graded" {% if request.getQuery('status') == 'graded' %}selected{% endif %}>已批改</option>
                                </select>
                            </div>
                            <button class="layui-btn layui-btn-sm layui-btn-normal" type="submit">
                                <i class="layui-icon layui-icon-search"></i> 筛选
                            </button>
                            {% if request.getQuery('status') %}
                                <a href="{{ url({'for':'home.uc.assignments'}) }}" class="layui-btn layui-btn-sm layui-btn-primary">
                                    <i class="layui-icon layui-icon-refresh"></i> 重置
                                </a>
                            {% endif %}
                        </div>
                    </form>
                </div>
                
                {% if pager.total_pages > 0 %}
                    <table class="layui-table" lay-skin="line">
                        <colgroup>
                            <col>
                            <col width="15%">
                            <col width="15%">
                            <col width="15%">
                        </colgroup>
                        <thead>
                        <tr>
                            <th>作业</th>
                            <th>状态</th>
                            <th>成绩</th>
                            <th>操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        {% for item in pager.items %}
                            {% set assignment = item.assignment %}
                            {% set submission = item.submission %}
                            {% set assignment_url = url({'for':'home.assignment.show','id':assignment.id}) %}
                            {% set result_url = url({'for':'home.assignment.result','id':assignment.id}) %}
                            
                            <tr>
                                <td>
                                    <p><strong>{{ assignment.title }}</strong></p>
                                    <p class="meta" style="color: #999; font-size: 12px; margin-top: 5px;">
                                        题目：{{ assignment.question_count }} 道 
                                        &nbsp;|&nbsp; 总分：{{ assignment.total_score }} 分
                                        {% if assignment.deadline > 0 %}
                                            &nbsp;|&nbsp; 截止：{{ date('m-d H:i', assignment.deadline) }}
                                        {% endif %}
                                        {% if assignment.is_overdue %}
                                            <span class="layui-badge layui-bg-gray" style="margin-left: 5px;">已截止</span>
                                        {% endif %}
                                    </p>
                                </td>
                                <td>
                                    {% if submission.status == 'draft' %}
                                        <span class="layui-badge layui-bg-gray">草稿</span>
                                    {% elseif submission.status == 'pending' %}
                                        <span class="layui-badge layui-bg-orange">待批改</span>
                                        {% if submission.is_late %}
                                            <br><span style="color: #FF5722; font-size: 12px;">逾期提交</span>
                                        {% endif %}
                                    {% elseif submission.status == 'grading' %}
                                        <span class="layui-badge layui-bg-blue">批改中</span>
                                    {% elseif submission.status == 'graded' %}
                                        <span class="layui-badge layui-bg-green">已批改</span>
                                    {% endif %}
                                </td>
                                <td>
                                    {% if submission.status == 'graded' %}
                                        <div style="font-size: 18px; font-weight: bold; color: #16BAAA;">
                                            {{ submission.score }}
                                        </div>
                                        <div style="font-size: 12px; color: #999;">
                                            / {{ assignment.total_score }} 分
                                        </div>
                                    {% else %}
                                        <span style="color: #999;">-</span>
                                    {% endif %}
                                </td>
                                <td class="center">
                                    {% if submission.status == 'graded' %}
                                        <a href="{{ result_url }}" class="layui-btn layui-btn-sm layui-btn-normal" target="_blank">
                                            <i class="layui-icon layui-icon-read"></i> 查看成绩
                                        </a>
                                        {% if assignment.allow_resubmit and not assignment.is_overdue %}
                                            <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-primary" target="_blank" style="margin-top: 5px;">
                                                <i class="layui-icon layui-icon-edit"></i> 重新提交
                                            </a>
                                        {% endif %}
                                    {% elseif submission.status == 'pending' or submission.status == 'grading' %}
                                        <button class="layui-btn layui-btn-sm layui-btn-disabled" disabled>
                                            <i class="layui-icon layui-icon-time"></i> 等待批改
                                        </button>
                                    {% elseif submission.status == 'draft' %}
                                        <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-warm" target="_blank">
                                            <i class="layui-icon layui-icon-edit"></i> 继续答题
                                        </a>
                                    {% else %}
                                        {% if assignment.is_overdue and not assignment.allow_resubmit %}
                                            <button class="layui-btn layui-btn-sm layui-btn-disabled" disabled>
                                                <i class="layui-icon layui-icon-close"></i> 已截止
                                            </button>
                                        {% else %}
                                            <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-normal" target="_blank">
                                                <i class="layui-icon layui-icon-edit"></i> 开始作业
                                            </a>
                                        {% endif %}
                                    {% endif %}
                                </td>
                            </tr>
                        {% endfor %}
                        </tbody>
                    </table>
                    {{ partial('partials/pager') }}
                {% else %}
                    <div class="no-records">
                        <p><i class="layui-icon layui-icon-form" style="font-size: 48px; color: #E6E6E6;"></i></p>
                        <p>您还没有作业记录</p>
                    </div>
                {% endif %}
            </div>
        </div>
    </div>

{% endblock %}

{% block include_js %}

    <script>
        layui.use(['form'], function() {
            var form = layui.form;
            
            // 状态筛选自动提交
            form.on('select(status-filter)', function(data) {
                $(this).closest('form').submit();
            });
        });
    </script>

{% endblock %}


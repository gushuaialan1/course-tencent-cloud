{% if pager.total_pages > 0 %}
    <div class="assignment-list">
        {% for item in pager.items %}
            {% set assignment_url = url({'for':'home.assignment.show','id':item.id}) %}
            {% set result_url = url({'for':'home.assignment.result','id':item.id}) %}
            <div class="assignment-card wrap" style="margin-bottom: 15px;">
                <div class="assignment-header">
                    <h3 class="assignment-title">
                        <a href="{{ assignment_url }}" target="_blank">{{ item.title }}</a>
                    </h3>
                    <div class="assignment-status">
                        {% if item.submission %}
                            {% if item.submission.status == 'graded' or item.submission.status == 'auto_graded' %}
                                <span class="layui-badge layui-bg-green">已批改</span>
                            {% elseif item.submission.status == 'grading' %}
                                <span class="layui-badge layui-bg-blue">批改中</span>
                            {% elseif item.submission.status == 'submitted' %}
                                <span class="layui-badge layui-bg-cyan">已提交</span>
                            {% elseif item.submission.status == 'returned' %}
                                <span class="layui-badge layui-bg-orange">已退回</span>
                            {% else %}
                                <span class="layui-badge layui-bg-gray">草稿</span>
                            {% endif %}
                        {% elseif item.is_overdue %}
                            <span class="layui-badge layui-bg-gray">已截止</span>
                        {% else %}
                            <span class="layui-badge layui-bg-orange">未提交</span>
                        {% endif %}
                    </div>
                </div>
                
                {% if item.description %}
                    <div class="assignment-description" style="margin: 10px 0; color: #666; line-height: 1.6;">
                        {{ item.description }}
                    </div>
                {% endif %}
                
                <div class="assignment-meta" style="margin: 15px 0; color: #999; font-size: 13px;">
                    <span style="margin-right: 20px;">
                        <i class="layui-icon layui-icon-form"></i> {{ item.question_count }} 道题
                    </span>
                    <span style="margin-right: 20px;">
                        <i class="layui-icon layui-icon-praise"></i> 总分 {{ item.max_score }} 分
                    </span>
                    <span style="margin-right: 20px;">
                        <i class="layui-icon layui-icon-time"></i> 
                        截止时间: {{ item.due_date_text }}
                    </span>
                    {% if item.submission and (item.submission.status == 'graded' or item.submission.status == 'auto_graded') %}
                        <span style="color: #16BAAA; font-weight: bold;">
                            <i class="layui-icon layui-icon-rate-solid"></i> 
                            得分: {{ item.submission.score }} 分
                        </span>
                    {% endif %}
                </div>
                
                <div class="assignment-actions" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #E6E6E6;">
                    {% if item.submission %}
                        {% if item.submission.status == 'graded' or item.submission.status == 'auto_graded' %}
                            {# 批改完成，可以查看成绩 #}
                            <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-normal" target="_blank">
                                <i class="layui-icon layui-icon-read"></i> 查看作业
                            </a>
                            {% if item.allow_late and not item.is_overdue %}
                                <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-primary" target="_blank">
                                    <i class="layui-icon layui-icon-edit"></i> 重新提交
                                </a>
                            {% endif %}
                        {% elseif item.submission.status == 'grading' %}
                            {# 人工批改中 #}
                            <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-warm" target="_blank">
                                <i class="layui-icon layui-icon-time"></i> 批改中
                            </a>
                        {% elseif item.submission.status == 'submitted' %}
                            {# 已提交，等待批改 #}
                            <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-disabled" target="_blank">
                                <i class="layui-icon layui-icon-time"></i> 等待批改
                            </a>
                        {% else %}
                            {# 草稿状态，继续编辑 #}
                            <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-primary" target="_blank">
                                <i class="layui-icon layui-icon-edit"></i> 继续编辑
                            </a>
                        {% endif %}
                    {% elseif item.is_overdue %}
                        <button class="layui-btn layui-btn-sm layui-btn-disabled">
                            <i class="layui-icon layui-icon-close"></i> 已截止
                        </button>
                    {% else %}
                        <a href="{{ assignment_url }}" class="layui-btn layui-btn-sm layui-btn-normal" target="_blank">
                            <i class="layui-icon layui-icon-edit"></i> 开始作业
                        </a>
                    {% endif %}
                </div>
            </div>
        {% endfor %}
    </div>
    {{ partial('partials/pager_ajax') }}
{% else %}
    <div class="no-records">
        <p><i class="layui-icon layui-icon-form" style="font-size: 48px; color: #E6E6E6;"></i></p>
        <p>该课程暂无作业</p>
    </div>
{% endif %}


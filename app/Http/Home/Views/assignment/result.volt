{% extends 'templates/main.volt' %}

{% block content %}

    {% set course_url = url({'for':'home.course.show','id':assignment.course.id}) %}
    {% set assignment_url = url({'for':'home.assignment.show','id':assignment.id}) %}

    <div class="breadcrumb">
        <span class="layui-breadcrumb">
            <a href="/">首页</a>
            <a href="{{ course_url }}">{{ assignment.course.title }}</a>
            <a><cite>作业成绩</cite></a>
        </span>
    </div>

    <div class="layout-main">
        <div class="layout-content">
            
            {# 成绩概览卡片 #}
            <div class="assignment-score-card wrap" style="margin-bottom: 20px; text-align: center; padding: 40px 20px;">
                <h2 style="margin: 0 0 20px 0; font-size: 20px; color: #333;">
                    {{ assignment.title }}
                </h2>
                
                {# 批改状态提示 #}
                {% if submission.status == 'graded' and submission.grade_status == 'pending' %}
                    <div class="layui-alert layui-alert-normal" style="margin: 0 auto 20px; max-width: 600px;">
                        <i class="layui-icon layui-icon-tips"></i>
                        选择题已自动批改，主观题正在批改中，当前分数为部分得分。老师批改完成后，最终成绩可能会有变化。
                    </div>
                {% elseif submission.status == 'graded' and submission.grade_status == 'completed' %}
                    <div class="layui-alert layui-alert-success" style="margin: 0 auto 20px; max-width: 600px;">
                        <i class="layui-icon layui-icon-ok-circle"></i>
                        批改已完成，以下为您的最终成绩。
                    </div>
                {% endif %}
                
                <div class="score-display" style="margin: 30px 0;">
                    <div class="score-number" style="font-size: 72px; font-weight: bold; color: #16BAAA; line-height: 1;">
                        {{ submission.score }}
                        <span style="font-size: 36px; color: #999;">/ {{ assignment.max_score }}</span>
                    </div>
                    <div class="score-percentage" style="margin-top: 15px; font-size: 24px; color: #666;">
                        得分率：{{ ((submission.score / assignment.max_score) * 100)|round(1) }}%
                    </div>
                </div>
                
                <div class="score-meta" style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #E6E6E6; color: #999; font-size: 14px;">
                    <span style="margin: 0 20px;">
                        <i class="layui-icon layui-icon-time"></i> 
                        提交时间：{{ date('Y-m-d H:i:s', submission.submitted_at) }}
                    </span>
                    <span style="margin: 0 20px;">
                        <i class="layui-icon layui-icon-ok-circle"></i> 
                        批改时间：{{ date('Y-m-d H:i:s', submission.graded_at) }}
                    </span>
                    {% if submission.is_late %}
                        <span class="layui-badge layui-bg-orange">逾期提交</span>
                    {% endif %}
                </div>
                
                {% if submission.feedback %}
                    <div class="teacher-feedback" style="margin-top: 30px; padding: 20px; background: #F0F9FF; border-left: 3px solid #1E9FFF; text-align: left; border-radius: 2px;">
                        <h4 style="margin: 0 0 10px 0; color: #333;">
                            <i class="layui-icon layui-icon-read"></i> 教师评语
                        </h4>
                        <div style="line-height: 1.8; color: #666;">
                            {{ submission.feedback }}
                        </div>
                    </div>
                {% endif %}
            </div>
            
            {# 题目批改详情 #}
            <div class="assignment-questions-result wrap">
                <h3 style="margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #16BAAA; font-size: 18px; color: #333;">
                    <i class="layui-icon layui-icon-form"></i> 批改详情
                </h3>
                
                {% for question in questions %}
                    {% set is_correct = question.earned_score == question.score %}
                    {% set is_partial = question.earned_score > 0 and question.earned_score < question.score %}
                    {% set is_wrong = question.earned_score == 0 %}
                    
                    {% if is_correct %}
                        {% set border_color = '#5FB878' %}
                        {% set badge_class = 'layui-bg-green' %}
                        {% set badge_text = '√ 正确' %}
                    {% elseif is_partial %}
                        {% set border_color = '#FFB800' %}
                        {% set badge_class = 'layui-bg-orange' %}
                        {% set badge_text = '部分正确' %}
                    {% else %}
                        {% set border_color = '#FF5722' %}
                        {% set badge_class = 'layui-bg-red' %}
                        {% set badge_text = '× 错误' %}
                    {% endif %}
                    
                    <div class="question-result-item" style="margin-bottom: 25px; padding: 20px; background: #FAFAFA; border-radius: 2px; border-left: 4px solid {{ border_color }};">
                        
                        {# 题目标题 #}
                        <div class="question-header" style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <h4 style="margin: 0; font-size: 16px; color: #333;">
                                <span class="question-number" style="display: inline-block; width: 30px; height: 30px; line-height: 30px; text-align: center; background: {{ border_color }}; color: #fff; border-radius: 50%; margin-right: 10px; font-size: 14px;">{{ loop.index }}</span>
                                {{ question.title }}
                            </h4>
                            <div>
                                <span class="layui-badge {{ badge_class }}" style="margin-right: 10px;">{{ badge_text }}</span>
                                <span class="score-display" style="font-size: 18px; font-weight: bold; color: {{ border_color }};">
                                    {{ question.earned_score }} / {{ question.score }} 分
                                </span>
                            </div>
                        </div>
                        
                        {# 题目内容 #}
                        {% if question.content %}
                            <div class="question-content" style="margin-bottom: 15px; padding: 10px 15px; background: #fff; border-radius: 2px; line-height: 1.8; color: #666;">
                                {{ question.content }}
                            </div>
                        {% endif %}
                        
                        <div style="padding-left: 40px;">
                            
                            {# 选项列表（仅选择题） #}
                            {% if question.type == 'choice_single' or question.type == 'choice_multiple' %}
                                {% if question.options %}
                                    <div class="question-options" style="margin-bottom: 15px; padding: 10px 15px; background: #fff; border-radius: 2px;">
                                        {% for key, option in question.options %}
                                            <div style="margin: 8px 0; padding: 8px; border-radius: 2px; 
                                                {% if question.type == 'choice_single' %}
                                                    {% if question.user_answer == key %}background: #E6F7FF;{% endif %}
                                                {% else %}
                                                    {% if question.user_answer and key in question.user_answer %}background: #E6F7FF;{% endif %}
                                                {% endif %}">
                                                <strong>{{ key }}.</strong> {{ option }}
                                            </div>
                                        {% endfor %}
                                    </div>
                                {% endif %}
                            {% endif %}
                            
                            {# 学生答案 #}
                            <div class="user-answer" style="margin-bottom: 15px;">
                                <strong style="color: #666;">您的答案：</strong>
                                <div style="margin-top: 8px; padding: 12px 15px; background: #fff; border-radius: 2px; border: 1px solid #E6E6E6;">
                                    {% if question.type == 'choice_single' %}
                                        <span style="color: #333; font-weight: bold;">{{ question.user_answer ? question.user_answer : '未作答' }}</span>
                                    {% elseif question.type == 'choice_multiple' %}
                                        {% if question.user_answer %}
                                            <span style="color: #333; font-weight: bold;">{{ question.user_answer|join(', ') }}</span>
                                        {% else %}
                                            <span style="color: #999;">未作答</span>
                                        {% endif %}
                                    {% else %}
                                        <div style="color: #333; line-height: 1.8; white-space: pre-wrap;">{{ question.user_answer ? question.user_answer : '未作答' }}</div>
                                    {% endif %}
                                </div>
                            </div>
                            
                            {# 参考答案 #}
                            {% if question.answer %}
                                <div class="correct-answer" style="margin-bottom: 15px;">
                                    <strong style="color: #5FB878;">
                                        <i class="layui-icon layui-icon-ok-circle"></i> 参考答案：
                                    </strong>
                                    <div style="margin-top: 8px; padding: 12px 15px; background: #F6FFED; border-radius: 2px; border: 1px solid #B7EB8F;">
                                        <div style="color: #333; line-height: 1.8; white-space: pre-wrap;">{{ question.answer }}</div>
                                    </div>
                                </div>
                            {% endif %}
                            
                            {# 批改评语 #}
                            {% if question.feedback %}
                                <div class="question-feedback" style="margin-top: 15px;">
                                    <strong style="color: #1E9FFF;">
                                        <i class="layui-icon layui-icon-tips"></i> 批改评语：
                                    </strong>
                                    <div style="margin-top: 8px; padding: 12px 15px; background: #F0F9FF; border-radius: 2px; border-left: 3px solid #1E9FFF;">
                                        <div style="color: #666; line-height: 1.8;">{{ question.feedback }}</div>
                                    </div>
                                </div>
                            {% endif %}
                            
                        </div>
                    </div>
                {% endfor %}
                
            </div>
            
            {# 操作按钮 #}
            <div class="action-area" style="margin-top: 30px; padding: 20px; background: #fff; border-radius: 2px; text-align: center;">
                <a href="{{ course_url }}" class="layui-btn layui-btn-primary">
                    <i class="layui-icon layui-icon-return"></i> 返回课程
                </a>
                {% if assignment.allow_late and not assignment.is_overdue %}
                    <a href="{{ assignment_url }}" class="layui-btn layui-btn-normal">
                        <i class="layui-icon layui-icon-edit"></i> 重新提交
                    </a>
                {% endif %}
            </div>

        </div>

        {# 右侧边栏 #}
        <div class="layout-sidebar">
            
            {# 成绩统计 #}
            <div class="sidebar score-stats-card">
                <div class="sidebar-title">
                    <i class="layui-icon layui-icon-chart"></i> 成绩统计
                </div>
                <div class="sidebar-body">
                    <div class="stat-item" style="padding: 15px 0; border-bottom: 1px solid #E6E6E6; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #16BAAA;">
                            {{ submission.score }}
                        </div>
                        <div style="color: #999; font-size: 13px; margin-top: 5px;">
                            得分
                        </div>
                    </div>
                    <div class="stat-item" style="padding: 15px 0; border-bottom: 1px solid #E6E6E6; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #666;">
                            {{ assignment.max_score }}
                        </div>
                        <div style="color: #999; font-size: 13px; margin-top: 5px;">
                            总分
                        </div>
                    </div>
                    <div class="stat-item" style="padding: 15px 0; text-align: center;">
                        <div style="font-size: 32px; font-weight: bold; color: #FFB800;">
                            {{ ((submission.score / assignment.max_score) * 100)|round(1) }}%
                        </div>
                        <div style="color: #999; font-size: 13px; margin-top: 5px;">
                            得分率
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

{% endblock %}


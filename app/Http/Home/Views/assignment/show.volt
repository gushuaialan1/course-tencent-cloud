{% extends 'templates/main.volt' %}

{% block content %}

    {% set course_url = url({'for':'home.course.show','id':assignment.course.id}) %}
    {% set submit_url = url({'for':'home.assignment.submit','id':assignment.id}) %}
    {% set draft_url = url({'for':'home.assignment.draft','id':assignment.id}) %}

    <div class="breadcrumb">
        <span class="layui-breadcrumb">
            <a href="/">首页</a>
            <a href="{{ course_url }}">{{ assignment.course.title }}</a>
            <a><cite>{{ assignment.title }}</cite></a>
        </span>
    </div>

    <div class="layout-main">
        <div class="layout-content">
            
            {# 作业信息卡片 #}
            <div class="assignment-info-card wrap" style="margin-bottom: 20px;">
                <h2 style="margin: 0 0 15px 0; font-size: 24px; color: #333;">
                    <i class="layui-icon layui-icon-form" style="color: #16BAAA;"></i> 
                    {{ assignment.title }}
                </h2>
                
                {% if assignment.description %}
                    <div class="assignment-description" style="margin: 15px 0; padding: 15px; background: #FAFAFA; border-radius: 2px; line-height: 1.8; color: #666;">
                        <strong style="color: #333;"><i class="layui-icon layui-icon-read"></i> 作业说明：</strong>
                        <p style="margin-top: 10px;">{{ assignment.description }}</p>
                    </div>
                {% endif %}
                
                <div class="assignment-meta" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #E6E6E6; color: #999; font-size: 13px;">
                    <span style="margin-right: 25px;">
                        <i class="layui-icon layui-icon-form"></i> 题目数量：<strong style="color: #333;">{{ assignment.question_count }}</strong> 道
                    </span>
                    <span style="margin-right: 25px;">
                        <i class="layui-icon layui-icon-praise"></i> 总分：<strong style="color: #333;">{{ assignment.max_score }}</strong> 分
                    </span>
                    {% if assignment.due_date > 0 %}
                        <span style="margin-right: 25px;">
                            <i class="layui-icon layui-icon-time"></i> 截止时间：
                            <strong style="color: {% if assignment.is_overdue %}#FF5722{% else %}#333{% endif %};">
                                {{ date('Y-m-d H:i', assignment.due_date) }}
                            </strong>
                        </span>
                    {% endif %}
                    {% if assignment.is_overdue %}
                        <span class="layui-badge layui-bg-gray">已截止</span>
                    {% endif %}
                </div>
            </div>

            {# 题目列表 #}
            <div class="assignment-questions wrap">
                <h3 style="margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 2px solid #16BAAA; font-size: 18px; color: #333;">
                    <i class="layui-icon layui-icon-edit"></i> 答题区域
                </h3>
                
                <form class="layui-form" id="assignment-form">
                    {% for question in assignment.questions %}
                        <div class="question-item" data-question-id="{{ question.id }}" style="margin-bottom: 30px; padding: 20px; background: #FAFAFA; border-radius: 2px; border-left: 3px solid #16BAAA;">
                            
                            {# 题目标题 #}
                            <div class="question-header" style="margin-bottom: 15px;">
                                <h4 style="margin: 0; font-size: 16px; color: #333; line-height: 1.6;">
                                    <span class="question-number" style="display: inline-block; width: 30px; height: 30px; line-height: 30px; text-align: center; background: #16BAAA; color: #fff; border-radius: 50%; margin-right: 10px; font-size: 14px;">{{ loop.index }}</span>
                                    {{ question.title }}
                                    {% if question.required == 1 %}
                                        <span style="color: #FF5722; margin-left: 5px;">*</span>
                                    {% endif %}
                                    <span class="question-score" style="color: #999; font-size: 14px; font-weight: normal; margin-left: 10px;">({{ question.score }} 分)</span>
                                </h4>
                            </div>
                            
                            {# 题目内容 #}
                            {% if question.content %}
                                <div class="question-content" style="margin-bottom: 15px; padding: 10px 15px; background: #fff; border-radius: 2px; line-height: 1.8; color: #666;">
                                    {{ question.content }}
                                </div>
                            {% endif %}
                            
                            {# 题目答案区 #}
                            <div class="question-answer" style="margin-top: 15px; padding-left: 40px;">
                                
                                {% if question.type == 'choice' %}
                                    {# 选择题（根据multiple判断单选/多选）#}
                                    {% if question.multiple %}
                                        {# 多选题 #}
                                        {% for key, option in question.options %}
                                            <div style="margin: 10px 0;">
                                                <input type="checkbox" 
                                                       name="answer_{{ question.id }}[]" 
                                                       value="{{ key }}" 
                                                       title="{{ option }}" 
                                                       lay-filter="question-{{ question.id }}"
                                                       {% if assignment.submission and assignment.submission.content[question.id] and key in assignment.submission.content[question.id] %}checked{% endif %}>
                                            </div>
                                        {% endfor %}
                                    {% else %}
                                        {# 单选题 #}
                                        {% for key, option in question.options %}
                                            <div style="margin: 10px 0;">
                                                <input type="radio" 
                                                       name="answer_{{ question.id }}" 
                                                       value="{{ key }}" 
                                                       title="{{ option }}" 
                                                       lay-filter="question-{{ question.id }}"
                                                       {% if assignment.submission and assignment.submission.content[question.id] == key %}checked{% endif %}>
                                            </div>
                                        {% endfor %}
                                    {% endif %}
                                    
                                {% elseif question.type == 'text' or question.type == 'essay' %}
                                    {# 简答题/论述题 #}
                                    <textarea 
                                        name="answer_{{ question.id }}" 
                                        placeholder="请输入您的答案..." 
                                        class="layui-textarea" 
                                        style="min-height: {% if question.type == 'essay' %}200px{% else %}100px{% endif %}; resize: vertical;"
                                        lay-filter="question-{{ question.id }}">{% if assignment.submission and assignment.submission.content[question.id] %}{{ assignment.submission.content[question.id] }}{% endif %}</textarea>
                                    
                                {% elseif question.type == 'file' %}
                                    {# 文件题 #}
                                    <div class="file-upload-area">
                                        <button type="button" 
                                                class="layui-btn layui-btn-normal layui-btn-sm" 
                                                id="upload-btn-{{ question.id }}">
                                            <i class="layui-icon layui-icon-upload"></i> 上传文件
                                        </button>
                                        <input type="hidden" 
                                               name="answer_{{ question.id }}" 
                                               value="{% if assignment.submission and assignment.submission.content[question.id] %}{{ assignment.submission.content[question.id] }}{% endif %}" 
                                               lay-filter="question-{{ question.id }}">
                                        <div class="file-preview" id="file-preview-{{ question.id }}" style="margin-top: 10px;">
                                            {% if assignment.submission and assignment.submission.content[question.id] %}
                                                <div class="file-item" style="padding: 8px 12px; background: #fff; border: 1px solid #E6E6E6; border-radius: 2px; display: inline-block;">
                                                    <i class="layui-icon layui-icon-file"></i>
                                                    <span>{{ assignment.submission.content[question.id] }}</span>
                                                    <a href="javascript:;" class="remove-file" style="color: #FF5722; margin-left: 10px;">删除</a>
                                                </div>
                                            {% endif %}
                                        </div>
                                    </div>
                                {% endif %}
                                
                            </div>
                        </div>
                    {% endfor %}
                    
                    {% if assignment.questions|length == 0 %}
                        <div class="no-questions" style="padding: 60px 20px; text-align: center; color: #999;">
                            <p><i class="layui-icon layui-icon-form" style="font-size: 48px; color: #E6E6E6;"></i></p>
                            <p>该作业暂无题目</p>
                        </div>
                    {% endif %}
                </form>
            </div>

        </div>

        {# 右侧边栏 #}
        <div class="layout-sidebar">
            
            {# 答题进度卡片 #}
            <div class="sidebar assignment-progress-card">
                <div class="sidebar-title">
                    <i class="layui-icon layui-icon-chart"></i> 答题进度
                </div>
                <div class="sidebar-body">
                    <div class="progress-info" style="text-align: center; padding: 20px 0;">
                        <div class="progress-circle" style="font-size: 36px; font-weight: bold; color: #16BAAA; margin-bottom: 10px;">
                            <span id="answered-count">0</span> / {{ assignment.question_count }}
                        </div>
                        <div class="progress-text" style="color: #999; font-size: 13px;">
                            已完成题目
                        </div>
                        <div class="layui-progress layui-progress-big" lay-filter="progress-bar" style="margin-top: 20px;">
                            <div class="layui-progress-bar layui-bg-green" lay-percent="0%"></div>
                        </div>
                    </div>
                    
                    {% if assignment.due_date > 0 and not assignment.is_overdue %}
                        <div class="time-info" style="padding: 15px; background: #FFF7E6; border-radius: 2px; margin-top: 15px;">
                            <div style="color: #FFB800; text-align: center;">
                                <i class="layui-icon layui-icon-time"></i> 距离截止还有
                            </div>
                            <div id="countdown" style="text-align: center; font-size: 18px; font-weight: bold; color: #FF5722; margin-top: 10px;">
                                计算中...
                            </div>
                        </div>
                    {% endif %}
                    
                    <div class="action-buttons" style="margin-top: 20px;">
                        <button type="button" 
                                class="layui-btn layui-btn-fluid" 
                                id="save-draft-btn"
                                {% if assignment.is_overdue and not assignment.allow_late %}disabled{% endif %}>
                            <i class="layui-icon layui-icon-file"></i> 保存草稿
                        </button>
                        <button type="button" 
                                class="layui-btn layui-btn-normal layui-btn-fluid" 
                                id="submit-btn"
                                style="margin-top: 10px;"
                                {% if assignment.is_overdue and not assignment.allow_late %}disabled{% endif %}>
                            <i class="layui-icon layui-icon-ok"></i> 提交作业
                        </button>
                    </div>
                </div>
            </div>
            
            {# 温馨提示 #}
            <div class="sidebar tips-card">
                <div class="sidebar-title">
                    <i class="layui-icon layui-icon-tips"></i> 温馨提示
                </div>
                <div class="sidebar-body">
                    <ul style="list-style: none; padding: 0; margin: 0; line-height: 2; color: #666; font-size: 13px;">
                        <li><i class="layui-icon layui-icon-right" style="color: #16BAAA;"></i> 答题过程中会自动保存草稿</li>
                        <li><i class="layui-icon layui-icon-right" style="color: #16BAAA;"></i> 提交前请仔细检查答案</li>
                        <li><i class="layui-icon layui-icon-right" style="color: #16BAAA;"></i> 带 <span style="color: #FF5722;">*</span> 号为必答题</li>
                        {% if assignment.allow_late %}
                            <li><i class="layui-icon layui-icon-right" style="color: #16BAAA;"></i> 本作业允许重新提交</li>
                        {% else %}
                            <li><i class="layui-icon layui-icon-right" style="color: #FF5722;"></i> 本作业提交后不可修改</li>
                        {% endif %}
                    </ul>
                </div>
            </div>
            
        </div>
    </div>

    <input type="hidden" id="assignment-id" value="{{ assignment.id }}">
    <input type="hidden" id="assignment-due-date" value="{{ assignment.due_date }}">
    <input type="hidden" id="assignment-question-count" value="{{ assignment.question_count }}">
    <input type="hidden" id="submit-url" value="{{ submit_url }}">
    <input type="hidden" id="draft-url" value="{{ draft_url }}">

{% endblock %}

{% block include_js %}

    {{ js_include('home/js/assignment.show.js') }}

{% endblock %}


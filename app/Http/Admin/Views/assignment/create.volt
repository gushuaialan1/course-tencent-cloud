{% extends "templates/main.volt" %}

{% block link_css %}
{{ css_link('admin/css/assignment.css') }}
{% endblock %}

{% block content %}

<div class="kg-nav">
    <div class="kg-nav-left">
        <span class="layui-breadcrumb">
            <a><cite>作业管理</cite></a>
            <a><cite>创建作业</cite></a>
        </span>
    </div>
    <div class="kg-nav-right">
        <button class="layui-btn layui-btn-sm" id="btn-save-draft">
            <i class="layui-icon layui-icon-file"></i>保存草稿
        </button>
        <button class="layui-btn layui-btn-sm" id="btn-preview">
            <i class="layui-icon layui-icon-preview"></i>预览
        </button>
        <button class="layui-btn layui-btn-sm layui-btn-normal" id="btn-publish">
            <i class="layui-icon layui-icon-release"></i>发布作业
        </button>
        <a class="layui-btn layui-btn-sm layui-btn-primary" href="{{ url({'for':'admin.assignment.list'}) }}">
            <i class="layui-icon layui-icon-return"></i>返回列表
        </a>
    </div>
</div>

<div class="layui-row layui-col-space15">
    <!-- 左侧：作业内容编辑 -->
    <div class="layui-col-md9">
        <form class="layui-form" lay-filter="assignment-form" id="assignment-form">
            <!-- 隐藏字段：编辑模式标记 -->
            {% if is_edit is defined and is_edit %}
            <input type="hidden" name="id" value="{{ assignment.id }}">
            <input type="hidden" id="edit-mode" value="1">
            <input type="hidden" id="assignment-data" value="{{ assignment|json_encode }}">
            {% endif %}
            
            <!-- 基本信息卡片 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-form"></i> 基本信息
                </div>
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md8">
                            <div class="layui-form-item">
                                <label class="layui-form-label">作业标题</label>
                                <div class="layui-input-block">
                                    <input type="text" name="title" placeholder="请输入作业标题" class="layui-input" lay-verify="required">
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-md4">
                            <div class="layui-form-item">
                                <label class="layui-form-label">作业类型</label>
                                <div class="layui-input-block">
                                    <select name="assignment_type" lay-verify="required">
                                        <option value="">请选择类型</option>
                                        <option value="choice">选择题</option>
                                        <option value="essay">简答题</option>
                                        <option value="upload">文件上传</option>
                                        <option value="mixed">混合题型</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-row">
                        <div class="layui-col-md6">
                            <div class="layui-form-item">
                                <label class="layui-form-label">关联课程</label>
                                <div class="layui-input-block">
                                    <select name="course_id" lay-verify="required" lay-filter="course-select">
                                        <option value="">请选择课程</option>
                                        {% for course in courses %}
                                        <option value="{{ course.id }}">{{ course.title }}</option>
                                        {% endfor %}
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-md6">
                            <div class="layui-form-item">
                                <label class="layui-form-label">关联章节</label>
                                <div class="layui-input-block">
                                    <select name="chapter_id">
                                        <option value="">选择章节(可选)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">作业描述</label>
                        <div class="layui-input-block">
                            <textarea name="description" placeholder="请输入作业描述" class="layui-textarea" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 评分设置卡片 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-star"></i> 评分设置
                </div>
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md4">
                            <div class="layui-form-item">
                                <label class="layui-form-label">总分</label>
                                <div class="layui-input-block">
                                    <input type="number" name="max_score" value="100" min="1" max="999" class="layui-input" lay-verify="required">
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-md4">
                            <div class="layui-form-item">
                                <label class="layui-form-label">评分模式</label>
                                <div class="layui-input-block">
                                    <select name="grade_mode">
                                        <option value="manual">手动评分</option>
                                        <option value="auto">自动评分</option>
                                        <option value="mixed">混合评分</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-md4">
                            <div class="layui-form-item">
                                <label class="layui-form-label">最大提交次数</label>
                                <div class="layui-input-block">
                                    <input type="number" name="max_attempts" value="1" min="1" max="10" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 时间设置卡片 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-time"></i> 时间设置
                </div>
                <div class="layui-card-body">
                    <div class="layui-row">
                        <div class="layui-col-md6">
                            <div class="layui-form-item">
                                <label class="layui-form-label">截止时间</label>
                                <div class="layui-input-block">
                                    <input type="text" name="due_date" placeholder="选择截止时间" class="layui-input" id="due-date-picker">
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-md6">
                            <div class="layui-form-item">
                                <label class="layui-form-label">时间限制</label>
                                <div class="layui-input-block">
                                    <input type="number" name="time_limit" value="0" min="0" max="1440" placeholder="分钟，0表示无限制" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="layui-row">
                        <div class="layui-col-md6">
                            <div class="layui-form-item">
                                <label class="layui-form-label">允许迟交</label>
                                <div class="layui-input-block">
                                    <input type="checkbox" name="allow_late" value="1" lay-skin="switch" lay-text="允许|禁止">
                                </div>
                            </div>
                        </div>
                        <div class="layui-col-md6">
                            <div class="layui-form-item">
                                <label class="layui-form-label">迟交扣分比例</label>
                                <div class="layui-input-block">
                                    <input type="number" name="late_penalty" value="0" min="0" max="1" step="0.1" placeholder="0-1之间" class="layui-input">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 题目内容卡片 -->
            <div class="layui-card" id="questions-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-list"></i> 题目内容
                    <div class="kg-card-header-actions">
                        <button type="button" class="layui-btn layui-btn-xs" id="btn-add-question">
                            <i class="layui-icon layui-icon-add-1"></i>添加题目
                        </button>
                    </div>
                </div>
                <div class="layui-card-body">
                    <div id="questions-container">
                        <!-- 题目将通过JS动态添加 -->
                    </div>
                </div>
            </div>

            <!-- 作业说明卡片 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-tips"></i> 作业说明
                </div>
                <div class="layui-card-body">
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <textarea name="instructions" id="instructions-editor" placeholder="请输入作业说明（选填）" class="layui-textarea" rows="5"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- 右侧：设置面板 -->
    <div class="layui-col-md3">
        <!-- 发布设置 -->
        <div class="layui-card">
            <div class="layui-card-header">
                <i class="layui-icon layui-icon-set"></i> 发布设置
            </div>
            <div class="layui-card-body">
                <div class="layui-form-item">
                    <label class="layui-form-label">状态</label>
                    <div class="layui-input-block">
                        <select name="status" lay-filter="status-select">
                            <option value="draft">草稿</option>
                            <option value="published">发布</option>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item" id="publish-time-item" style="display: none;">
                    <label class="layui-form-label">发布时间</label>
                    <div class="layui-input-block">
                        <input type="text" name="publish_time" placeholder="立即发布" class="layui-input" id="publish-time-picker">
                    </div>
                </div>
            </div>
        </div>

        <!-- 附件管理 -->
        <div class="layui-card">
            <div class="layui-card-header">
                <i class="layui-icon layui-icon-file"></i> 附件管理
            </div>
            <div class="layui-card-body">
                <div class="kg-upload-area" id="attachments-upload">
                    <div class="kg-upload-hint">
                        <i class="layui-icon layui-icon-upload-drag"></i>
                        <p>点击或拖拽文件到此处上传</p>
                        <p class="kg-text-muted">支持 PDF、Word、图片等格式</p>
                    </div>
                </div>
                <div id="attachments-list" class="kg-file-list">
                    <!-- 附件列表 -->
                </div>
            </div>
        </div>

        <!-- 快速操作 -->
        <div class="layui-card">
            <div class="layui-card-header">
                <i class="layui-icon layui-icon-util"></i> 快速操作
            </div>
            <div class="layui-card-body">
                <div class="kg-quick-actions">
                    <button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" id="btn-import-questions">
                        <i class="layui-icon layui-icon-download-circle"></i>导入题库
                    </button>
                    <button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" id="btn-template-library">
                        <i class="layui-icon layui-icon-template-1"></i>模板库
                    </button>
                    <button type="button" class="layui-btn layui-btn-fluid layui-btn-sm" id="btn-help">
                        <i class="layui-icon layui-icon-help"></i>使用帮助
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{% endblock %}

{% block include_js %}
{{ js_include('admin/js/assignment.create.js') }}
{% endblock %}

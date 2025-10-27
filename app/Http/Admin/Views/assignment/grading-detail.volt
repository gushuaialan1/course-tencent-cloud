{% extends "templates/main.volt" %}

{% block link_css %}
{{ css_link('admin/css/assignment.css') }}
<style>
.grading-container {
    display: flex;
    gap: 20px;
}
.grading-left {
    flex: 1;
}
.grading-right {
    width: 400px;
}
.question-item {
    background: #f8f8f8;
    border: 1px solid #e6e6e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}
.question-header {
    font-weight: bold;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.question-type {
    display: inline-block;
    padding: 2px 8px;
    background: #1E9FFF;
    color: white;
    border-radius: 3px;
    font-size: 12px;
}
.answer-section {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #ddd;
}
.answer-label {
    font-weight: bold;
    color: #666;
    margin-bottom: 5px;
}
.user-answer {
    background: #fff;
    padding: 10px;
    border-radius: 3px;
    border: 1px solid #e6e6e6;
}
.correct-answer {
    background: #e8f8f5;
    padding: 10px;
    border-radius: 3px;
    border: 1px solid #5FB878;
}
.answer-correct {
    color: #5FB878;
    font-weight: bold;
}
.answer-wrong {
    color: #FF5722;
    font-weight: bold;
}
.score-input-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
}
.score-input-group input {
    width: 80px;
}
.grading-summary {
    background: #fff;
    border: 1px solid #e6e6e6;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}
.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed #eee;
}
.summary-item:last-child {
    border-bottom: none;
}
.summary-label {
    color: #666;
}
.summary-value {
    font-weight: bold;
}
.late-badge {
    background: #FF5722;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
{% endblock %}

{% block content %}

<div class="kg-nav">
    <div class="kg-nav-left">
        <span class="layui-breadcrumb">
            <a><cite>作业管理</cite></a>
            <a href="{{ url({'for':'admin.assignment.grading.list'}) }}"><cite>批改工作台</cite></a>
            <a><cite>批改详情</cite></a>
        </span>
    </div>
    <div class="kg-nav-right">
        <a class="layui-btn layui-btn-sm" href="{{ url({'for':'admin.assignment.grading.list'}) }}">
            <i class="layui-icon layui-icon-return"></i>返回列表
        </a>
    </div>
</div>

<div class="layui-card">
    <div class="layui-card-header">
        <i class="layui-icon layui-icon-form"></i> 批改作业：{{ assignment.title }}
    </div>
    <div class="layui-card-body">
        <div class="grading-container">
            <!-- 左侧：作业内容和学生答案 -->
            <div class="grading-left">
                <div class="layui-card">
                    <div class="layui-card-header">作业说明</div>
                    <div class="layui-card-body">
                        {% if assignment.instructions %}
                            <p>{{ assignment.instructions }}</p>
                        {% else %}
                            <p>无说明</p>
                        {% endif %}
                    </div>
                </div>

                <div class="layui-card" style="margin-top: 15px;">
                    <div class="layui-card-header">学生答题详情</div>
                    <div class="layui-card-body">
                        {% set content = assignment.questions is defined ? assignment.questions : [] %}
                        {% set submissionData = submission.getContentData() %}
                        {% set userContent = submissionData['answers'] is defined ? submissionData['answers'] : [] %}
                        {% set referenceAnswer = assignment.reference_answer is defined ? assignment.reference_answer : [] %}
                        
                        {% if content|length > 0 %}
                            {% for index, question in content %}
                            <div class="question-item" data-question-index="{{ index }}" data-question-id="{{ question.id }}">
                                <div class="question-header">
                                    <span>
                                        <span class="question-type">{{ question.type }}</span>
                                        题目 {{ index + 1 }}：
                                        {% if question.title %}
                                            {{ question.title }}
                                        {% elseif question.question %}
                                            {{ question.question }}
                                        {% else %}
                                            未命名题目
                                        {% endif %}
                                    </span>
                                    <span style="color: #009688;">分值：{{ question.score ? question.score : 0 }}分</span>
                                </div>
                                
                                <div class="question-content">
                                    {% if question.content %}
                                        {{ question.content }}
                                    {% endif %}
                                    
                                    {% if question.options is defined %}
                                        <div style="margin-top: 10px;">
                                            {% for optKey, optValue in question.options %}
                                            <div>{{ optKey }}. {{ optValue }}</div>
                                            {% endfor %}
                                        </div>
                                    {% endif %}
                                </div>

                                <div class="answer-section">
                                    <div class="answer-label">学生答案：</div>
                                    <div class="user-answer">
                                        {% if userContent[question.id] is defined %}
                                            {% if question.type == 'choice' %}
                                                {% if userContent[question.id] is iterable and not (userContent[question.id] is string) %}
                                                    {{ userContent[question.id]|join(', ') }}
                                                {% else %}
                                                    {{ userContent[question.id] }}
                                                {% endif %}
                                            {% elseif question.type == 'file' or question.type == 'upload' %}
                                                {# 文件类型题目 #}
                                                <?php
                                                $fileList = [];
                                                $questionId = $question->id;
                                                $answer = isset($userContent[$questionId]) ? $userContent[$questionId] : null;
                                                
                                                if (is_string($answer)) {
                                                    try {
                                                        $decoded = json_decode($answer, true);
                                                        if (is_array($decoded)) {
                                                            $fileList = $decoded;
                                                        }
                                                    } catch (\Exception $e) {
                                                        $fileList = [];
                                                    }
                                                } elseif (is_array($answer)) {
                                                    $fileList = $answer;
                                                }
                                                
                                                // 处理嵌套数组结构: [[{...}]] -> [{...}]
                                                if (!empty($fileList) && is_array($fileList[0]) && isset($fileList[0][0])) {
                                                    $fileList = $fileList[0];
                                                }
                                                
                                                // 将数组元素转换为对象，以便 Volt 模板可以用对象语法访问
                                                foreach ($fileList as $key => $item) {
                                                    if (is_array($item)) {
                                                        $fileList[$key] = (object)$item;
                                                    }
                                                }
                                                ?>
                                                {% if fileList %}
                                                    <div class="uploaded-files-list">
                                                        {% for file in fileList %}
                                                            <div style="padding: 8px; margin: 5px 0; background: #F5F5F5; border-radius: 2px; display: flex; align-items: center; gap: 10px;">
                                                                <i class="layui-icon layui-icon-file" style="color: #16BAAA; font-size: 18px;"></i>
                                                                <div style="flex: 1;">
                                                                    {% if file.url %}
                                                                        <a href="{{ file.url }}" style="color: #1E9FFF; text-decoration: none; font-weight: bold;">
                                                                            {{ file.name }}
                                                                        </a>
                                                                    {% else %}
                                                                        <span style="font-weight: bold;">{{ file.name }}</span>
                                                                    {% endif %}
                                                                    {% if file.size %}
                                                                        <?php 
                                                                        $fileSize = is_object($file) ? $file->size : 0;
                                                                        $fileSizeKb = round($fileSize / 1024, 2); 
                                                                        ?>
                                                                        <span style="color: #999; font-size: 12px; margin-left: 10px;">
                                                                            ({{ fileSizeKb }} KB)
                                                                        </span>
                                                                    {% endif %}
                                                                </div>
                                                                {% if file.url %}
                                                                    <a href="{{ file.url }}" class="layui-btn layui-btn-xs" download>
                                                                        <i class="layui-icon layui-icon-download-circle"></i> 下载
                                                                    </a>
                                                                {% endif %}
                                                            </div>
                                                        {% endfor %}
                                                    </div>
                                                {% else %}
                                                    <span style="color: #999;">未上传文件</span>
                                                {% endif %}
                                            {% else %}
                                                {{ userContent[question.id] }}
                                            {% endif %}
                                        {% else %}
                                            <span style="color: #999;">未作答</span>
                                        {% endif %}
                                    </div>

                                    {% if referenceAnswer[question.id] is defined %}
                                    <div style="margin-top: 10px;">
                                        <div class="answer-label">参考答案：</div>
                                        <div class="correct-answer">
                                            {% if question.type == 'choice' %}
                                                {% if referenceAnswer[question.id] is iterable and not (referenceAnswer[question.id] is string) %}
                                                    {{ referenceAnswer[question.id]|join(', ') }}
                                                {% else %}
                                                    {{ referenceAnswer[question.id] }}
                                                {% endif %}
                                            {% else %}
                                                {{ referenceAnswer[question.id] }}
                                            {% endif %}
                                        </div>
                                    </div>
                                    {% endif %}

                                    <!-- 分题评分 -->
                                    {% if question.type == 'essay' or question.type == 'upload' or question.type == 'file' %}
                                    <div class="score-input-group">
                                        <span>得分：</span>
                                        <input type="number" 
                                               class="layui-input question-score" 
                                               name="question_scores[{{ question.id }}]" 
                                               max="{{ question.score|default(0) }}" 
                                               min="0" 
                                               step="0.5" 
                                               value="0"
                                               placeholder="0-{{ question.score|default(0) }}">
                                        <span>/ {{ question.score|default(0) }}分</span>
                                    </div>
                                    {% endif %}
                                </div>
                            </div>
                            {% endfor %}
                        {% else %}
                            <p style="text-align: center; color: #999; padding: 30px;">暂无题目内容</p>
                        {% endif %}
                    </div>
                </div>
            </div>

            <!-- 右侧：批改信息和评分表单 -->
            <div class="grading-right">
                <!-- 提交信息摘要 -->
                <div class="grading-summary">
                    <h3 style="margin-bottom: 15px;">提交信息</h3>
                    <div class="summary-item">
                        <span class="summary-label">学生姓名：</span>
                        <span class="summary-value">{{ submission.user_name }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">提交时间：</span>
                        <span class="summary-value">{{ date('Y-m-d H:i:s', submission.submit_time) }}</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">是否迟交：</span>
                        <span class="summary-value">
                            {% if submission.is_late == 1 %}
                                <span class="late-badge">迟交</span>
                            {% else %}
                                <span style="color: #5FB878;">按时</span>
                            {% endif %}
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">作业类型：</span>
                        <span class="summary-value">
                            {% if assignment.assignment_type == 'choice' %}
                                选择题
                            {% elseif assignment.assignment_type == 'essay' %}
                                简答题
                            {% elseif assignment.assignment_type == 'upload' %}
                                文件上传
                            {% else %}
                                混合题型
                            {% endif %}
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">评分模式：</span>
                        <span class="summary-value">
                            {% if assignment.grade_mode == 'auto' %}
                                <span style="color: #1E9FFF;">自动评分</span>
                            {% elseif assignment.grade_mode == 'manual' %}
                                <span style="color: #FF5722;">手动评分</span>
                            {% else %}
                                <span style="color: #FFB800;">混合评分</span>
                            {% endif %}
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">满分：</span>
                        <span class="summary-value" style="color: #009688;">{{ submission.max_score }}分</span>
                    </div>
                </div>

                <!-- 评分表单 -->
                <form class="layui-form" id="grading-form">
                    <div class="layui-form-item">
                        <label class="layui-form-label">总分 <span style="color:red;">*</span></label>
                        <div class="layui-input-block">
                            <input type="number" 
                                   name="score" 
                                   id="total-score"
                                   required 
                                   lay-verify="required|number" 
                                   placeholder="0-{{ submission.max_score }}" 
                                   max="{{ submission.max_score }}"
                                   min="0"
                                   step="0.5"
                                   autocomplete="off" 
                                   class="layui-input">
                            <div class="layui-form-mid layui-word-aux">满分：{{ submission.max_score }}分</div>
                        </div>
                    </div>

                    {% if assignment.grade_mode == 'mixed' %}
                    <div class="layui-form-item">
                        <label class="layui-form-label">手动评分</label>
                        <div class="layui-input-block">
                            <input type="number" 
                                   name="manual_score" 
                                   id="manual-score"
                                   placeholder="非选择题部分得分" 
                                   min="0"
                                   step="0.5"
                                   autocomplete="off" 
                                   class="layui-input">
                            <div class="layui-form-mid layui-word-aux">混合模式：选择题自动+主观题手动</div>
                        </div>
                    </div>
                    {% endif %}

                    <div class="layui-form-item layui-form-text">
                        <label class="layui-form-label">批改反馈</label>
                        <div class="layui-input-block">
                            <textarea name="feedback" 
                                      placeholder="请输入批改意见和建议" 
                                      class="layui-textarea" 
                                      rows="6"></textarea>
                        </div>
                    </div>

                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button type="button" class="layui-btn layui-btn-fluid" id="btn-submit-grade">
                                <i class="layui-icon layui-icon-ok"></i>提交批改
                            </button>
                            {% if assignment.grade_mode == 'auto' %}
                            <button type="button" class="layui-btn layui-btn-normal layui-btn-fluid" id="btn-auto-grade" style="margin-top: 10px;">
                                <i class="layui-icon layui-icon-refresh"></i>自动评分
                            </button>
                            {% endif %}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{% endblock %}

{% block link_js %}
<script>
console.log('======================================');
console.log('🚀 批改详情页面 JavaScript 初始化开始');
console.log('当前时间:', new Date().toLocaleString());
console.log('======================================');

// 全局错误捕获
window.addEventListener('error', function(e){
    console.error('🔴 全局错误:', e.message, e.filename, e.lineno, e.colno);
});

console.log('🟢 页面 JavaScript 开始执行');
console.log('Layui 对象:', typeof layui);

layui.use(['form', 'layer'], function(){
    console.log('🟢 Layui form 和 layer 模块加载完成');
    
    var form = layui.form;
    var layer = layui.layer;
    var $ = layui.jquery;
    
    console.log('form 对象:', typeof form);
    console.log('layer 对象:', typeof layer);
    console.log('$ 对象:', typeof $);

    var submissionId = {{ submission.id }};
    var gradeMode = '{{ assignment.grade_mode }}';
    
    console.log('提交ID:', submissionId);
    console.log('评分模式:', gradeMode);

    // 自动计算分题总分
    $('.question-score').on('input', function(){
        var totalScore = 0;
        $('.question-score').each(function(){
            var score = parseFloat($(this).val()) || 0;
            totalScore += score;
        });
        $('#total-score').val(totalScore.toFixed(1));
    });

    // 自动评分按钮
    $('#btn-auto-grade').on('click', function(){
        layer.confirm('确定要自动评分吗？（仅对选择题有效）', function(index){
            layer.msg('计算中...', {icon: 16, shade: 0.3, time: 0});
            
            // 模拟自动评分（实际应该通过API）
            setTimeout(function(){
                layer.closeAll();
                layer.msg('自动评分完成，请检查并提交', {icon: 1});
                
                // 这里应该调用API获取自动评分结果
                // 暂时模拟设置一个分数
                $('#total-score').val('0');
            }, 500);
            
            layer.close(index);
        });
    });

    // 提交批改表单 - 改用直接绑定按钮点击事件
    console.log('🟡 开始绑定提交按钮点击事件');
    
    $('#btn-submit-grade').on('click', function(){
        console.log('🔵 提交按钮被点击');
        
        // 验证必填字段
        var score = $('#total-score').val();
        if(!score || score === ''){
            layer.msg('请输入总分', {icon: 2});
            return false;
        }
        
        // 收集表单数据
        var formData = {
            score: score,
            feedback: $('textarea[name="feedback"]').val()
        };
        
        // 如果有混合评分模式的手动评分
        var manualScore = $('#manual-score').val();
        if(manualScore){
            formData.manual_score = manualScore;
        }
        
        console.log('表单数据:', formData);
        
        layer.confirm('确定要提交批改吗？提交后学生将收到成绩通知', function(index){
            console.log('✅ 用户点击了确认');
            layer.close(index);
            layer.msg('提交中...', {icon: 16, shade: 0.3, time: 0});
            
            // 收集分题评分详情
            var gradeDetails = {};
            $('.question-score').each(function(){
                var questionId = $(this).closest('.question-item').data('question-id');
                gradeDetails[questionId] = {
                    score: parseFloat($(this).val()) || 0
                };
            });
            console.log('分题评分详情:', gradeDetails);
            
            var postData = $.extend({}, formData);
            if(Object.keys(gradeDetails).length > 0){
                postData.grade_details = JSON.stringify(gradeDetails);
            }
            console.log('准备提交的数据:', postData);
            
            var ajaxUrl = '{{ url({"for":"admin.assignment.submission.grade", "id": submission.id}) }}';
            console.log('AJAX URL:', ajaxUrl);
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: postData,
                dataType: 'json',
                beforeSend: function(xhr){
                    console.log('📤 正在发送AJAX请求...');
                },
                success: function(res){
                    console.log('✅ AJAX成功响应:', res);
                    layer.closeAll();
                    if(res.code === 0){
                        layer.msg('批改成功！', {icon: 1}, function(){
                            window.location.href = '{{ url({"for":"admin.assignment.grading.list"}) }}';
                        });
                    } else {
                        console.error('❌ 批改失败:', res.msg || res.message);
                        layer.msg(res.msg || res.message || '批改失败', {icon: 2});
                    }
                },
                error: function(xhr, status, error){
                    console.error('❌ AJAX请求失败');
                    console.error('状态:', status);
                    console.error('错误:', error);
                    console.error('响应状态码:', xhr.status);
                    console.error('响应内容:', xhr.responseText);
                    
                    layer.closeAll();
                    var errorMsg = '网络错误';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        errorMsg = res.msg || res.message || errorMsg;
                        console.error('解析后的错误信息:', errorMsg);
                    } catch(e) {
                        console.error('无法解析响应:', e);
                    }
                    layer.msg(errorMsg, {icon: 2, time: 5000});
                }
            });
        }, function(index){
            console.log('❌ 用户点击了取消');
            layer.close(index);
        });
    });
    
    console.log('✅ 所有事件监听器绑定完成');
    console.log('页面上的提交按钮:', $('#btn-submit-grade').length);
});

console.log('🟢 页面 JavaScript 执行完成，等待 Layui 模块加载...');
</script>
{% endblock %}


/**
 * 作业创建页面
 */

layui.use(['layer', 'form', 'laydate', 'upload'], function () {
    var $ = layui.$;
    var layer = layui.layer;
    var form = layui.form;
    var laydate = layui.laydate;
    var upload = layui.upload;

    var questionCount = 0;
    var attachments = [];
    var isEditMode = $('#edit-mode').val() === '1';
    var assignmentData = null;

    // 如果是编辑模式，解析作业数据
    if (isEditMode) {
        try {
            var dataEl = document.getElementById('assignment-data');
            if (dataEl) {
                var raw = dataEl.textContent || dataEl.innerText || '';
                // 某些环境可能带有 HTML 实体或转义，先尝试直接 JSON 解析
                try {
                    assignmentData = JSON.parse(raw);
                } catch (innerErr) {
                    // 回退：尝试还原常见实体再解析
                    var unescaped = raw
                        .replace(/&quot;/g, '"')
                        .replace(/&#39;/g, "'")
                        .replace(/&lt;/g, '<')
                        .replace(/&gt;/g, '>')
                        .replace(/&amp;/g, '&');
                    assignmentData = JSON.parse(unescaped);
                }
                console.log('编辑模式，作业数据:', assignmentData);
            }
        } catch (e) {
            layer.msg('加载作业数据失败', { icon: 2 });
            console.error('解析作业数据失败:', e);
        }
    }

    // 初始化日期时间选择器
    laydate.render({
        elem: '#due-date-picker',
        type: 'datetime',
        min: 0 // 限制不能选择今天之前的日期
    });

    laydate.render({
        elem: '#publish-time-picker',
        type: 'datetime',
        min: 0
    });

    // 监听状态选择
    form.on('select(status-select)', function (data) {
        if (data.value === 'published') {
            $('#publish-time-item').show();
        } else {
            $('#publish-time-item').hide();
        }
    });

    // 监听课程选择，加载章节
    form.on('select(course-select)', function (data) {
        var courseId = data.value;
        loadChapters(courseId);
    });

    // 加载课程章节
    function loadChapters(courseId, callback) {
        if (!courseId) {
            $('select[name="chapter_id"]').html('<option value="">选择章节(可选)</option>');
            form.render('select');
            return;
        }

        $.get('/admin/course/' + courseId + '/chapters', function (res) {
            if (res && (res.code === 200 || res.code === 0)) {
                var options = '<option value="">选择章节(可选)</option>';
                var chapters = (res.data && res.data.chapters) ? res.data.chapters : (res.chapters || []);
                $.each(chapters, function (i, chapter) {
                    if (chapter && chapter.id != null) {
                        options += '<option value="' + chapter.id + '">' + (chapter.title || ('章节' + chapter.id)) + '</option>';
                    }
                });
                $('select[name="chapter_id"]').html(options);
                form.render('select');
                
                if (callback) callback();
            }
        }).fail(function(xhr) {
            layer.msg('加载章节失败，请重试', { icon: 2 });
        });
    }

    // 添加题目
    $('#btn-add-question').on('click', function () {
        showQuestionTypeDialog();
    });

    // 显示题目类型选择对话框
    function showQuestionTypeDialog() {
        var content = `
            <div class="kg-question-type-selector">
                <div class="kg-type-item" data-type="choice">
                    <i class="layui-icon layui-icon-survey"></i>
                    <h4>选择题</h4>
                    <p>单选题或多选题</p>
                </div>
                <div class="kg-type-item" data-type="essay">
                    <i class="layui-icon layui-icon-edit"></i>
                    <h4>简答题</h4>
                    <p>文字回答题目</p>
                </div>
                <div class="kg-type-item" data-type="upload">
                    <i class="layui-icon layui-icon-upload"></i>
                    <h4>文件上传题</h4>
                    <p>上传文件作为答案</p>
                </div>
            </div>
        `;

        layer.open({
            type: 1,
            title: '选择题目类型',
            content: content,
            area: ['500px', '300px'],
            success: function (layero) {
                layero.find('.kg-type-item').on('click', function () {
                    var type = $(this).data('type');
                    addQuestion(type);
                    layer.closeAll();
                });
            }
        });
    }

    // 添加题目
    function addQuestion(type) {
        questionCount++;
        var questionHtml = createQuestionHtml(questionCount, type);
        $('#questions-container').append(questionHtml);
        form.render();
    }

    // 创建题目HTML
    function createQuestionHtml(index, type) {
        var typeText = {
            'choice': '选择题',
            'essay': '简答题',
            'upload': '文件上传题'
        };

        var html = `
            <div class="kg-question-item" data-question-id="${index}" data-question-type="${type}">
                <div class="kg-question-header">
                    <span class="kg-question-number">题目 ${index}</span>
                    <span class="kg-question-type">${typeText[type]}</span>
                    <div class="kg-question-actions">
                        <button type="button" class="layui-btn layui-btn-xs" onclick="moveQuestionUp(${index})">
                            <i class="layui-icon layui-icon-up"></i>
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs" onclick="moveQuestionDown(${index})">
                            <i class="layui-icon layui-icon-down"></i>
                        </button>
                        <button type="button" class="layui-btn layui-btn-xs layui-btn-danger" onclick="removeQuestion(${index})">
                            <i class="layui-icon layui-icon-delete"></i>
                        </button>
                    </div>
                </div>
                <div class="kg-question-content">
                    <div class="layui-form-item">
                        <label class="layui-form-label">题目标题</label>
                        <div class="layui-input-block">
                            <textarea name="questions[${index}][title]" placeholder="请输入题目标题" class="layui-textarea" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">分值</label>
                        <div class="layui-input-block">
                            <input type="number" name="questions[${index}][score]" value="10" min="1" max="100" class="layui-input" style="width: 100px;">
                        </div>
                    </div>
                    ${createQuestionTypeContent(index, type)}
                </div>
            </div>
        `;

        return html;
    }

    // 创建不同类型题目的内容
    function createQuestionTypeContent(index, type) {
        switch (type) {
            case 'choice':
                return createChoiceQuestionContent(index);
            case 'essay':
                return createEssayQuestionContent(index);
            case 'upload':
                return createUploadQuestionContent(index);
            default:
                return '';
        }
    }

    // 创建选择题内容
    function createChoiceQuestionContent(index) {
        return `
            <div class="layui-form-item">
                <label class="layui-form-label">题目类型</label>
                <div class="layui-input-block">
                    <input type="radio" name="questions[${index}][multiple]" value="0" title="单选题" checked>
                    <input type="radio" name="questions[${index}][multiple]" value="1" title="多选题">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">选项</label>
                <div class="layui-input-block">
                    <div class="kg-choice-options" data-question="${index}">
                        <div class="kg-choice-option">
                            <span class="kg-option-label">A.</span>
                            <input type="text" name="questions[${index}][options][A]" placeholder="选项A" class="layui-input">
                            <input type="checkbox" name="questions[${index}][correct][]" value="A" title="正确答案">
                        </div>
                        <div class="kg-choice-option">
                            <span class="kg-option-label">B.</span>
                            <input type="text" name="questions[${index}][options][B]" placeholder="选项B" class="layui-input">
                            <input type="checkbox" name="questions[${index}][correct][]" value="B" title="正确答案">
                        </div>
                    </div>
                    <button type="button" class="layui-btn layui-btn-sm" onclick="addChoiceOption(${index})">
                        <i class="layui-icon layui-icon-add-1"></i>添加选项
                    </button>
                </div>
            </div>
        `;
    }

    // 创建简答题内容
    function createEssayQuestionContent(index) {
        return `
            <div class="layui-form-item">
                <label class="layui-form-label">字数限制</label>
                <div class="layui-input-block">
                    <input type="number" name="questions[${index}][min_length]" placeholder="最少字数" class="layui-input" style="width: 120px;">
                    <span style="margin: 0 10px;">-</span>
                    <input type="number" name="questions[${index}][max_length]" placeholder="最多字数" class="layui-input" style="width: 120px;">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">参考答案</label>
                <div class="layui-input-block">
                    <textarea name="questions[${index}][reference_answer]" placeholder="请输入参考答案(可选)" class="layui-textarea" rows="3"></textarea>
                </div>
            </div>
        `;
    }

    // 创建文件上传题内容
    function createUploadQuestionContent(index) {
        return `
            <div class="layui-form-item">
                <label class="layui-form-label">允许文件类型</label>
                <div class="layui-input-block">
                    <input type="checkbox" name="questions[${index}][allowed_types][]" value="pdf" title="PDF" checked>
                    <input type="checkbox" name="questions[${index}][allowed_types][]" value="doc" title="Word">
                    <input type="checkbox" name="questions[${index}][allowed_types][]" value="docx" title="Word">
                    <input type="checkbox" name="questions[${index}][allowed_types][]" value="txt" title="文本">
                    <input type="checkbox" name="questions[${index}][allowed_types][]" value="jpg" title="图片">
                    <input type="checkbox" name="questions[${index}][allowed_types][]" value="png" title="图片">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">最大文件数量</label>
                <div class="layui-input-block">
                    <input type="number" name="questions[${index}][max_files]" value="1" min="1" max="10" class="layui-input" style="width: 100px;">
                </div>
            </div>
        `;
    }

    // 全局函数：添加选择题选项
    window.addChoiceOption = function (questionIndex) {
        var $container = $('.kg-choice-options[data-question="' + questionIndex + '"]');
        var optionCount = $container.find('.kg-choice-option').length;
        var optionLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        
        if (optionCount >= 8) {
            layer.msg('最多只能添加8个选项');
            return;
        }

        var label = optionLabels[optionCount];
        var optionHtml = `
            <div class="kg-choice-option">
                <span class="kg-option-label">${label}.</span>
                <input type="text" name="questions[${questionIndex}][options][${label}]" placeholder="选项${label}" class="layui-input">
                <input type="checkbox" name="questions[${questionIndex}][correct][]" value="${label}" title="正确答案">
                <button type="button" class="layui-btn layui-btn-xs layui-btn-danger" onclick="removeChoiceOption(this)">
                    <i class="layui-icon layui-icon-delete"></i>
                </button>
            </div>
        `;
        
        $container.append(optionHtml);
        form.render();
    };

    // 全局函数：删除选择题选项
    window.removeChoiceOption = function (btn) {
        var $option = $(btn).closest('.kg-choice-option');
        var $container = $option.closest('.kg-choice-options');
        
        if ($container.find('.kg-choice-option').length <= 2) {
            layer.msg('至少需要保留2个选项');
            return;
        }
        
        $option.remove();
    };

    // 全局函数：删除题目
    window.removeQuestion = function (questionIndex) {
        layer.confirm('确定要删除这个题目吗？', function (index) {
            $('.kg-question-item[data-question-id="' + questionIndex + '"]').remove();
            updateQuestionNumbers();
            layer.close(index);
        });
    };

    // 全局函数：上移题目
    window.moveQuestionUp = function (questionIndex) {
        var $question = $('.kg-question-item[data-question-id="' + questionIndex + '"]');
        var $prev = $question.prev('.kg-question-item');
        
        if ($prev.length > 0) {
            $question.insertBefore($prev);
            updateQuestionNumbers();
        }
    };

    // 全局函数：下移题目
    window.moveQuestionDown = function (questionIndex) {
        var $question = $('.kg-question-item[data-question-id="' + questionIndex + '"]');
        var $next = $question.next('.kg-question-item');
        
        if ($next.length > 0) {
            $question.insertAfter($next);
            updateQuestionNumbers();
        }
    };

    // 更新题目编号
    function updateQuestionNumbers() {
        $('#questions-container .kg-question-item').each(function (index) {
            $(this).find('.kg-question-number').text('题目 ' + (index + 1));
        });
    }

    // 附件上传
    upload.render({
        elem: '#attachments-upload',
        url: '/admin/upload/file',
        multiple: true,
        drag: true,
        accept: 'file',
        acceptMime: '.pdf,.doc,.docx,.txt,.jpg,.png,.zip',
        done: function (res) {
            if (res.code === 200) {
                attachments.push(res.data.file);
                renderAttachmentsList();
                layer.msg('上传成功');
            } else {
                layer.msg(res.data.message, { icon: 2 });
            }
        },
        error: function () {
            layer.msg('上传失败', { icon: 2 });
        }
    });

    // 渲染附件列表
    function renderAttachmentsList() {
        var html = '';
        $.each(attachments, function (index, file) {
            html += `
                <div class="kg-file-item" data-index="${index}">
                    <i class="layui-icon layui-icon-file"></i>
                    <span class="kg-file-name">${file.name}</span>
                    <span class="kg-file-size">${formatFileSize(file.size)}</span>
                    <button type="button" class="layui-btn layui-btn-xs layui-btn-danger" onclick="removeAttachment(${index})">
                        <i class="layui-icon layui-icon-delete"></i>
                    </button>
                </div>
            `;
        });
        $('#attachments-list').html(html);
    }

    // 删除附件
    window.removeAttachment = function (index) {
        attachments.splice(index, 1);
        renderAttachmentsList();
    };

    // 格式化文件大小
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // 保存草稿
    $('#btn-save-draft').on('click', function () {
        saveAssignment('draft');
    });

    // 发布作业
    $('#btn-publish').on('click', function () {
        saveAssignment('published');
    });

    // 预览作业
    $('#btn-preview').on('click', function () {
        var formData = collectFormData();
        // 实现预览功能
        layer.msg('预览功能开发中...');
    });

    // 保存作业
    function saveAssignment(status) {
        var formData = collectFormData();
        formData.status = status;

        var loading = layer.load(1, { shade: [0.5, '#000'] });
        var url = isEditMode ? '/admin/assignment/update' : '/admin/assignment/create';

        $.post(url, formData, function (res) {
            layer.close(loading);
            
            if (res.code === 0) {
                var message = isEditMode ? '作业更新成功' : (status === 'draft' ? '草稿保存成功' : '作业发布成功');
                layer.msg(message, { icon: 1 }, function () {
                    location.href = '/admin/assignment/list';
                });
            } else {
                layer.msg(res.msg || res.message || '操作失败', { icon: 2 });
            }
        }).fail(function (xhr) {
            layer.close(loading);
            var errorMsg = '保存失败，请重试';
            try {
                var response = JSON.parse(xhr.responseText);
                errorMsg = response.msg || response.message || errorMsg;
            } catch (e) {
                // 解析失败使用默认消息
            }
            layer.msg(errorMsg, { icon: 2 });
        });
    }

    // 收集表单数据
    function collectFormData() {
        var formData = {};
        
        // 基本信息
        $('#assignment-form').serializeArray().forEach(function (item) {
            formData[item.name] = item.value;
        });

        // 富文本内容
        formData.instructions = $('#instructions-editor').val();

        // 转换日期字符串为时间戳
        if (formData.due_date) {
            formData.due_date = Math.floor(new Date(formData.due_date).getTime() / 1000);
        }
        if (formData.publish_time) {
            formData.publish_time = Math.floor(new Date(formData.publish_time).getTime() / 1000);
        }

        // 处理复选框值（未选中时不会出现在serializeArray中）
        formData.allow_late = $('input[name="allow_late"]').is(':checked') ? 1 : 0;

        // 题目数据
        var questions = [];
        $('#questions-container .kg-question-item').each(function (index) {
            var $question = $(this);
            var questionType = $question.data('question-type');
            var questionData = {
                id: index + 1,
                type: questionType,
                title: $question.find('textarea[name*="[title]"]').val(),
                score: parseFloat($question.find('input[name*="[score]"]').val()) || 0
            };

            // 根据题目类型收集不同的数据
            switch (questionType) {
                case 'choice':
                    questionData.multiple = $question.find('input[name*="[multiple]"]:checked').val() === '1';
                    questionData.options = {};
                    questionData.correct_answer = [];
                    
                    $question.find('.kg-choice-option').each(function () {
                        var $option = $(this);
                        var label = $option.find('.kg-option-label').text().replace('.', '');
                        var text = $option.find('input[type="text"]').val();
                        var isCorrect = $option.find('input[type="checkbox"]').is(':checked');
                        
                        if (text.trim()) {
                            questionData.options[label] = text;
                            if (isCorrect) {
                                questionData.correct_answer.push(label);
                            }
                        }
                    });
                    break;

                case 'essay':
                    questionData.min_length = parseInt($question.find('input[name*="[min_length]"]').val()) || 0;
                    questionData.max_length = parseInt($question.find('input[name*="[max_length]"]').val()) || 0;
                    questionData.reference_answer = $question.find('textarea[name*="[reference_answer]"]').val();
                    break;

                case 'upload':
                    questionData.allowed_types = [];
                    $question.find('input[name*="[allowed_types]"]:checked').each(function () {
                        questionData.allowed_types.push($(this).val());
                    });
                    questionData.max_files = parseInt($question.find('input[name*="[max_files]"]').val()) || 1;
                    break;
            }

            questions.push(questionData);
        });

        formData.content = JSON.stringify(questions);
        formData.attachments = JSON.stringify(attachments);

        return formData;
    }

    // 快速操作
    $('#btn-import-questions').on('click', function () {
        layer.msg('题库导入功能开发中...');
    });

    $('#btn-template-library').on('click', function () {
        layer.msg('模板库功能开发中...');
    });

    $('#btn-help').on('click', function () {
        layer.open({
            type: 2,
            title: '使用帮助',
            content: '/admin/help/assignment',
            area: ['800px', '600px']
        });
    });

    // 编辑模式：回填数据
    if (isEditMode && assignmentData) {
        fillFormData(assignmentData);
    }

    /**
     * 回填表单数据（编辑模式）
     */
    function fillFormData(data) {
        console.log('开始回填数据', data);

        // 基本信息
        $('input[name="title"]').val(data.title || '');
        $('select[name="assignment_type"]').val(data.assignment_type || '');
        $('select[name="course_id"]').val(data.course_id || '');
        $('textarea[name="description"]').val(data.description || '');

        // 加载章节（如果有课程ID）
        if (data.course_id) {
            loadChapters(data.course_id, function() {
                $('select[name="chapter_id"]').val(data.chapter_id || '');
                form.render('select');
            });
        }

        // 评分设置
        $('input[name="max_score"]').val(data.max_score || 100);
        $('select[name="grade_mode"]').val(data.grade_mode || 'manual');
        $('input[name="max_attempts"]').val(data.max_attempts || 1);

        // 时间设置
        if (data.due_date && data.due_date > 0) {
            var dueDate = new Date(data.due_date * 1000);
            var dueDateStr = formatDateTime(dueDate);
            $('input[name="due_date"]').val(dueDateStr);
        }
        $('input[name="time_limit"]').val(data.time_limit || 0);
        $('input[name="allow_late"]').prop('checked', data.allow_late == 1);
        $('input[name="late_penalty"]').val(data.late_penalty || 0);

        // 作业说明
        $('#instructions-editor').val(data.instructions || '');

        // 发布设置
        $('select[name="status"]').val(data.status || 'draft');

        // 附件
        if (data.attachments && typeof data.attachments === 'string') {
            try {
                attachments = JSON.parse(data.attachments);
            } catch (e) {
                attachments = [];
            }
        } else if (Array.isArray(data.attachments)) {
            attachments = data.attachments;
        }
        renderAttachmentsList();

        // 题目内容
        if (data.content) {
            var questions = [];
            if (typeof data.content === 'string') {
                try {
                    questions = JSON.parse(data.content);
                } catch (e) {
                    questions = [];
                }
            } else if (Array.isArray(data.content)) {
                questions = data.content;
            }

            // 添加题目
            questions.forEach(function(q, index) {
                addQuestionFromData(q, index + 1);
            });
        }

        // 刷新表单渲染
        form.render();
    }

    /**
     * 从数据添加题目（编辑模式）
     */
    function addQuestionFromData(questionData, index) {
        questionCount = index;
        var type = questionData.type || 'choice';
        var questionHtml = createQuestionHtml(questionCount, type);
        $('#questions-container').append(questionHtml);

        var $question = $('.kg-question-item[data-question-id="' + questionCount + '"]');
        
        // 填充题目数据
        $question.find('textarea[name*="[title]"]').val(questionData.title || '');
        $question.find('input[name*="[score]"]').val(questionData.score || 10);

        switch (type) {
            case 'choice':
                // 单选/多选
                if (questionData.multiple) {
                    $question.find('input[name*="[multiple]"][value="1"]').prop('checked', true);
                } else {
                    $question.find('input[name*="[multiple]"][value="0"]').prop('checked', true);
                }

                // 清空默认选项
                $question.find('.kg-choice-options').empty();

                // 添加选项（兼容两种结构：
                // 1) options 为对象映射 { A: '文本', B: '文本' }
                // 2) options 为数组 [{label:'A', content:'文本', is_correct:true}, ...]）
                var options = questionData.options || {};
                var correctAnswer = questionData.correct_answer || [];

                if (Array.isArray(options)) {
                    // 数组结构
                    options.forEach(function (opt) {
                        if (!opt) return;
                        var label = opt.label || '';
                        var text = opt.content != null ? String(opt.content) : '';
                        var isCorrect = (opt.is_correct === true) || (correctAnswer.indexOf(label) !== -1);
                        if (!label) return;
                        var optionHtml = `
                            <div class="kg-choice-option">
                                <span class="kg-option-label">${label}.</span>
                                <input type="text" name="questions[${questionCount}][options][${label}]" value="${text.replace(/"/g, '&quot;')}" placeholder="选项${label}" class="layui-input">
                                <input type="checkbox" name="questions[${questionCount}][correct][]" value="${label}" ${isCorrect ? 'checked' : ''} title="正确答案">
                                <button type="button" class="layui-btn layui-btn-xs layui-btn-danger" onclick="removeChoiceOption(this)"><i class="layui-icon layui-icon-delete"></i></button>
                            </div>
                        `;
                        $question.find('.kg-choice-options').append(optionHtml);
                    });
                } else {
                    // 对象映射结构
                    var optionLabels = Object.keys(options);
                    optionLabels.forEach(function(label) {
                        var text = options[label] != null ? String(options[label]) : '';
                        var isCorrect = correctAnswer.indexOf(label) !== -1;
                        var optionHtml = `
                            <div class="kg-choice-option">
                                <span class="kg-option-label">${label}.</span>
                                <input type="text" name="questions[${questionCount}][options][${label}]" value="${text.replace(/"/g, '&quot;')}" placeholder="选项${label}" class="layui-input">
                                <input type="checkbox" name="questions[${questionCount}][correct][]" value="${label}" ${isCorrect ? 'checked' : ''} title="正确答案">
                                ${optionLabels.length > 2 ? '<button type="button" class="layui-btn layui-btn-xs layui-btn-danger" onclick="removeChoiceOption(this)"><i class="layui-icon layui-icon-delete"></i></button>' : ''}
                            </div>
                        `;
                        $question.find('.kg-choice-options').append(optionHtml);
                    });
                }
                break;

            case 'essay':
                $question.find('input[name*="[min_length]"]').val(questionData.min_length || '');
                $question.find('input[name*="[max_length]"]').val(questionData.max_length || '');
                $question.find('textarea[name*="[reference_answer]"]').val(questionData.reference_answer || '');
                break;

            case 'upload':
                var allowedTypes = questionData.allowed_types || [];
                $question.find('input[name*="[allowed_types]"]').each(function() {
                    var $checkbox = $(this);
                    $checkbox.prop('checked', allowedTypes.indexOf($checkbox.val()) !== -1);
                });
                $question.find('input[name*="[max_files]"]').val(questionData.max_files || 1);
                break;
        }

        form.render();
    }

    /**
     * 格式化日期时间
     */
    function formatDateTime(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hour = String(date.getHours()).padStart(2, '0');
        var minute = String(date.getMinutes()).padStart(2, '0');
        var second = String(date.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hour}:${minute}:${second}`;
    }

});

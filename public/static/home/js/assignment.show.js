layui.use(['jquery', 'layer', 'form', 'element', 'upload'], function () {

    var $ = layui.jquery;
    var layer = layui.layer;
    var form = layui.form;
    var element = layui.element;
    var upload = layui.upload;

    var assignmentId = $('#assignment-id').val();
    var deadline = parseInt($('#assignment-due-date').val());
    var questionCount = parseInt($('#assignment-question-count').val());
    var submitUrl = $('#submit-url').val();
    var draftUrl = $('#draft-url').val();

    var draftTimer = null;
    var countdownTimer = null;

    /**
     * 初始化
     */
    function init() {
        updateProgress();
        startCountdown();
        startAutoDraft();
        bindEvents();
        initFilePreview();
    }

    /**
     * 绑定事件
     */
    function bindEvents() {
        // 保存草稿按钮
        $('#save-draft-btn').on('click', function () {
            saveDraft(true); // 手动保存，显示提示
        });

        // 提交作业按钮
        $('#submit-btn').on('click', function () {
            submitAssignment();
        });

        // 监听表单变化，更新进度
        $('input[type=radio], input[type=checkbox], textarea').on('change input', function () {
            updateProgress();
        });

        // 文件上传（如有）
        $('.file-upload-area button').on('click', function () {
            var questionId = $(this).attr('id').replace('upload-btn-', '');
            handleFileUpload(questionId);
        });

        // 删除已上传文件（使用事件委托，支持动态添加的元素）
        $(document).on('click', '.remove-uploaded-file', function () {
            var questionId = $(this).data('question');
            var fileIndex = parseInt($(this).data('index'));
            
            layer.confirm('确定要删除这个文件吗？', { icon: 3, title: '删除确认' }, function(index) {
                layer.close(index);
                
                // 获取当前文件列表
                var uploadedFiles = [];
                try {
                    var currentVal = $('input[name="answer_' + questionId + '"]').val();
                    if (currentVal) {
                        uploadedFiles = JSON.parse(currentVal);
                    }
                } catch (e) {
                    uploadedFiles = [];
                }
                
                // 删除指定文件
                uploadedFiles.splice(fileIndex, 1);
                
                // 更新隐藏域
                $('input[name="answer_' + questionId + '"]').val(uploadedFiles.length > 0 ? JSON.stringify(uploadedFiles) : '');
                
                // 更新预览
                var maxFiles = parseInt($('[data-question-id="' + questionId + '"]').data('max-files')) || 1;
                updateFilePreview(questionId, uploadedFiles, maxFiles);
                
                // 更新进度
                updateProgress();
                
                layer.msg('删除成功', { icon: 1 });
            });
        });
    }

    /**
     * 更新答题进度
     */
    function updateProgress() {
        var answered = 0;

        $('.question-item').each(function () {
            var questionId = $(this).data('question-id');
            var isAnswered = false;

            // 检查单选题
            if ($('input[name="answer_' + questionId + '"]:checked').length > 0) {
                isAnswered = true;
            }

            // 检查多选题
            if ($('input[name="answer_' + questionId + '[]"]:checked').length > 0) {
                isAnswered = true;
            }

            // 检查文本题
            var textVal = $('textarea[name="answer_' + questionId + '"]').val();
            if (textVal && textVal.trim() !== '') {
                isAnswered = true;
            }

            // 检查文件题
            var fileVal = $('input[name="answer_' + questionId + '"]').val();
            if (fileVal && fileVal.trim() !== '') {
                isAnswered = true;
            }

            if (isAnswered) {
                answered++;
            }
        });

        // 更新进度显示
        $('#answered-count').text(answered);
        
        var percent = questionCount > 0 ? Math.round((answered / questionCount) * 100) : 0;
        element.progress('progress-bar', percent + '%');
    }

    /**
     * 开始倒计时
     */
    function startCountdown() {
        if (deadline <= 0) return;

        var now = Math.floor(Date.now() / 1000);
        if (now >= deadline) {
            $('#countdown').text('已截止').css('color', '#FF5722');
            return;
        }

        countdownTimer = setInterval(function () {
            var now = Math.floor(Date.now() / 1000);
            var timeLeft = deadline - now;

            if (timeLeft <= 0) {
                $('#countdown').text('已截止').css('color', '#FF5722');
                clearInterval(countdownTimer);
                $('#save-draft-btn, #submit-btn').prop('disabled', true);
                layer.alert('作业已截止', { icon: 5 });
                return;
            }

            var days = Math.floor(timeLeft / 86400);
            var hours = Math.floor((timeLeft % 86400) / 3600);
            var minutes = Math.floor((timeLeft % 3600) / 60);
            var seconds = timeLeft % 60;

            var timeStr = '';
            if (days > 0) {
                timeStr = days + ' 天 ' + hours + ' 小时';
            } else if (hours > 0) {
                timeStr = hours + ' 小时 ' + minutes + ' 分钟';
            } else if (minutes > 0) {
                timeStr = minutes + ' 分钟 ' + seconds + ' 秒';
            } else {
                timeStr = seconds + ' 秒';
            }

            $('#countdown').text(timeStr);

            // 最后1小时变红色
            if (timeLeft < 3600) {
                $('#countdown').css('color', '#FF5722');
            }
        }, 1000);
    }

    /**
     * 开始自动保存草稿
     */
    function startAutoDraft() {
        // 每30秒自动保存一次
        draftTimer = setInterval(function () {
            saveDraft(false); // 自动保存，不显示提示
        }, 30000);
    }

    /**
     * 保存草稿
     */
    function saveDraft(showMessage) {
        var answers = collectAnswers();

        if (Object.keys(answers).length === 0) {
            if (showMessage) {
                layer.msg('请先答题', { icon: 5 });
            }
            return;
        }

        $.ajax({
            type: 'POST',
            url: draftUrl,
            data: {
                answers: JSON.stringify(answers)
            },
            success: function (res) {
                if (showMessage) {
                    layer.msg(res.msg || '保存成功', { icon: 1 });
                }
                // 同时保存到localStorage作为备份
                localStorage.setItem('assignment_draft_' + assignmentId, JSON.stringify(answers));
            },
            error: function () {
                if (showMessage) {
                    layer.msg('保存失败，请重试', { icon: 2 });
                }
            }
        });
    }

    /**
     * 提交作业
     */
    function submitAssignment() {
        var answers = collectAnswers();

        if (Object.keys(answers).length === 0) {
            layer.msg('请先完成作业', { icon: 5 });
            return;
        }

        // 检查必答题
        var requiredQuestions = [];
        $('.question-item').each(function () {
            var $required = $(this).find('.question-header h4 span[style*="color: #FF5722"]');
            if ($required.length > 0) {
                var questionId = $(this).data('question-id');
                requiredQuestions.push(questionId);
            }
        });

        for (var i = 0; i < requiredQuestions.length; i++) {
            var qid = requiredQuestions[i];
            if (!answers[qid] || (Array.isArray(answers[qid]) && answers[qid].length === 0) || 
                (typeof answers[qid] === 'string' && answers[qid].trim() === '')) {
                layer.msg('请完成所有必答题', { icon: 5 });
                return;
            }
        }

        // 确认提交
        layer.confirm('确定要提交作业吗？提交后将无法修改', {
            icon: 3,
            title: '提交确认',
            btn: ['确定提交', '再检查一下']
        }, function (index) {
            layer.close(index);

            var loadingIndex = layer.load(1, { shade: [0.3, '#000'] });

            $.ajax({
                type: 'POST',
                url: submitUrl,
                data: {
                    answers: JSON.stringify(answers)
                },
                success: function (res) {
                    layer.close(loadingIndex);
                    
                    // 清除定时器
                    if (draftTimer) clearInterval(draftTimer);
                    if (countdownTimer) clearInterval(countdownTimer);
                    
                    // 清除localStorage备份
                    localStorage.removeItem('assignment_draft_' + assignmentId);

                    layer.msg(res.msg || '提交成功', { icon: 1 }, function () {
                        if (res.location) {
                            window.location.href = res.location;
                        }
                    });
                },
                error: function (xhr) {
                    layer.close(loadingIndex);
                    var res = xhr.responseJSON || {};
                    layer.msg(res.msg || '提交失败，请重试', { icon: 2 });
                }
            });
        });
    }

    /**
     * 收集所有答案
     */
    function collectAnswers() {
        var answers = {};

        $('.question-item').each(function () {
            var questionId = $(this).data('question-id');
            
            // 单选题
            var radioVal = $('input[name="answer_' + questionId + '"]:checked').val();
            if (radioVal) {
                answers[questionId] = radioVal;
            }

            // 多选题
            var checkboxVals = [];
            $('input[name="answer_' + questionId + '[]"]:checked').each(function () {
                checkboxVals.push($(this).val());
            });
            if (checkboxVals.length > 0) {
                answers[questionId] = checkboxVals;
            }

            // 文本题
            var textVal = $('textarea[name="answer_' + questionId + '"]').val();
            if (textVal && textVal.trim() !== '') {
                answers[questionId] = textVal.trim();
            }

            // 文件题
            var fileVal = $('input[name="answer_' + questionId + '"]').val();
            if (fileVal && fileVal.trim() !== '') {
                answers[questionId] = fileVal.trim();
            }
        });

        return answers;
    }

    /**
     * 处理文件上传
     */
    function handleFileUpload(questionId) {
        // 获取题目设置
        var $question = $('[data-question-id="' + questionId + '"]');
        var allowedTypes = $question.data('allowed-types') || 'pdf,doc,docx,txt,jpg,png';
        var maxFiles = parseInt($question.data('max-files')) || 1;
        var maxSize = parseInt($question.data('max-size')) || 50; // MB
        
        // 当前已上传的文件
        var uploadedFiles = [];
        try {
            var currentVal = $('input[name="answer_' + questionId + '"]').val();
            if (currentVal) {
                uploadedFiles = JSON.parse(currentVal);
            }
        } catch (e) {
            uploadedFiles = [];
        }
        
        if (uploadedFiles.length >= maxFiles) {
            layer.msg('最多只能上传' + maxFiles + '个文件', { icon: 0 });
            return;
        }
        
        // 动态创建文件上传按钮
        var uploadId = 'file-upload-' + questionId + '-' + Date.now();
        var $uploadBtn = $('<input type="file" name="file" id="' + uploadId + '" style="display:none;">');
        $('body').append($uploadBtn);
        
        // 渲染上传组件
        upload.render({
            elem: '#' + uploadId,
            url: '/upload/file',
            accept: 'file',
            acceptMime: '.' + allowedTypes.replace(/,/g, ',.'),
            size: maxSize * 1024,
            field: 'file', // 重要：指定字段名
            auto: true, // 自动上传
            before: function(obj) {
                layer.load(1, { shade: [0.3, '#000'] });
            },
            done: function(res) {
                layer.closeAll('loading');
                $uploadBtn.remove();
                
                // 调试：打印返回数据
                console.log('Upload response:', res);
                
                if (res.code === 0 && res.data) {
                    // 添加到已上传列表
                    uploadedFiles.push({
                        name: res.data.name || res.data.file_name || '未知文件',
                        url: res.data.url || res.data.file_url,
                        size: res.data.size || 0
                    });
                    
                    // 保存到隐藏域
                    $('input[name="answer_' + questionId + '"]').val(JSON.stringify(uploadedFiles));
                    
                    // 更新文件预览区域
                    updateFilePreview(questionId, uploadedFiles, maxFiles);
                    
                    // 更新进度
                    updateProgress();
                    
                    layer.msg('上传成功', { icon: 1 });
                } else {
                    // 显示详细错误信息
                    var errorMsg = res.msg || res.message || '上传失败';
                    console.error('Upload failed:', res);
                    layer.msg(errorMsg, { icon: 2 });
                }
            },
            error: function(xhr, status, error) {
                layer.closeAll('loading');
                $uploadBtn.remove();
                console.error('Upload error:', xhr.responseText);
                layer.msg('上传失败，请重试', { icon: 2 });
            }
        });
        
        // 触发点击
        setTimeout(function() {
            $uploadBtn.click();
        }, 100);
    }
    
    /**
     * 更新文件预览区域
     */
    function updateFilePreview(questionId, files, maxFiles) {
        var $preview = $('#file-preview-' + questionId);
        if (!$preview.length) return;
        
        var html = '';
        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            var sizeText = formatFileSize(file.size || 0);
            
            html += '<div class="file-item" data-index="' + i + '" style="padding: 10px 12px; margin: 5px 0; background: #fff; border: 1px solid #E6E6E6; border-radius: 2px; display: flex; align-items: center; justify-content: space-between;">';
            html += '  <div style="flex: 1; display: flex; align-items: center;">';
            html += '    <i class="layui-icon layui-icon-file" style="color: #16BAAA; font-size: 20px; margin-right: 8px;"></i>';
            html += '    <div style="flex: 1;">';
            
            // 如果有URL，添加下载链接
            if (file.url) {
                html += '      <a href="' + file.url + '" target="_blank" class="file-name" style="color: #333; text-decoration: none; display: block; font-size: 14px; margin-bottom: 3px;">' + file.name + '</a>';
            } else {
                html += '      <span class="file-name" style="color: #333; font-size: 14px; display: block; margin-bottom: 3px;">' + file.name + '</span>';
            }
            
            html += '      <span class="file-size" style="color: #999; font-size: 12px;">' + sizeText + '</span>';
            html += '    </div>';
            html += '  </div>';
            
            // 删除按钮（仅在未提交时显示）
            var isSubmitted = $('#submit-btn').prop('disabled');
            if (!isSubmitted) {
                html += '  <i class="layui-icon layui-icon-close remove-uploaded-file" data-question="' + questionId + '" data-index="' + i + '" style="color: #FF5722; cursor: pointer; font-size: 18px; margin-left: 10px;" title="删除文件"></i>';
            }
            
            html += '</div>';
        }
        
        $preview.html(html);
        
        // 如果还能继续上传，显示上传按钮
        var $uploadArea = $('#upload-btn-' + questionId).closest('.file-upload-area');
        if (files.length >= maxFiles) {
            $uploadArea.find('button').hide();
            var tipHtml = '<span style="color: #999;">已达到最大文件数量</span>';
            $uploadArea.find('.upload-tip').html(tipHtml);
        } else {
            $uploadArea.find('button').show();
            var allowedTypes = $uploadArea.data('allowed-types') || 'pdf,doc,docx,txt,jpg,png';
            var maxSize = $uploadArea.data('max-size') || 50;
            var tipHtml = '支持 ' + allowedTypes + '，最多 ' + maxFiles + ' 个，每个不超过 ' + maxSize + 'MB';
            if (files.length > 0) {
                tipHtml += ' <span style="color: #16BAAA;">（还可上传 ' + (maxFiles - files.length) + ' 个）</span>';
            }
            $uploadArea.find('.upload-tip').html(tipHtml);
        }
    }
    
    /**
     * 格式化文件大小
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    }
    
    /**
     * 初始化文件预览
     */
    function initFilePreview() {
        // 遍历所有文件题，初始化文件预览
        $('.file-upload-area').each(function() {
            var $uploadArea = $(this);
            var questionId = $uploadArea.data('question-id');
            var maxFiles = parseInt($uploadArea.data('max-files')) || 1;
            
            // 获取当前已有的文件
            var uploadedFiles = [];
            try {
                var currentVal = $('input[name="answer_' + questionId + '"]').val();
                if (currentVal) {
                    // 尝试解析为 JSON
                    uploadedFiles = JSON.parse(currentVal);
                    // 确保是数组
                    if (!Array.isArray(uploadedFiles)) {
                        uploadedFiles = [];
                    }
                }
            } catch (e) {
                uploadedFiles = [];
            }
            
            // 如果有文件，显示预览
            if (uploadedFiles.length > 0) {
                updateFilePreview(questionId, uploadedFiles, maxFiles);
            }
        });
    }

    /**
     * 页面离开前提示
     */
    window.onbeforeunload = function (e) {
        var answers = collectAnswers();
        if (Object.keys(answers).length > 0) {
            e = e || window.event;
            if (e) {
                e.returnValue = '您还有未提交的答案，确定要离开吗？';
            }
            return '您还有未提交的答案，确定要离开吗？';
        }
    };

    // 初始化
    init();

});


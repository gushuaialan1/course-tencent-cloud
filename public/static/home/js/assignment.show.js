layui.use(['jquery', 'layer', 'form', 'element'], function () {

    var $ = layui.jquery;
    var layer = layui.layer;
    var form = layui.form;
    var element = layui.element;

    var assignmentId = $('#assignment-id').val();
    var deadline = parseInt($('#assignment-deadline').val());
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

        // 删除已上传文件
        $('.remove-file').on('click', function () {
            var $preview = $(this).closest('.file-preview');
            $preview.html('');
            $preview.prev('input[type=hidden]').val('');
            updateProgress();
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
        // TODO: 实现文件上传功能
        // 这里需要集成现有的文件上传组件
        layer.msg('文件上传功能待实现', { icon: 0 });
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


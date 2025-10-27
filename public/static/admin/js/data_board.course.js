layui.use(['layer', 'jquery', 'form'], function() {
    var layer = layui.layer;
    var $ = layui.jquery;
    var form = layui.form;

    // 课程搜索自动完成
    var searchTimeout;
    var currentCourseId = $('#selected-course-id').val();

    $('#course-search').on('input', function() {
        var keyword = $(this).val();
        
        clearTimeout(searchTimeout);
        
        if (keyword.length < 2) {
            return;
        }
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                type: 'GET',
                url: '/admin/data_board/search_course',
                data: { keyword: keyword },
                dataType: 'json',
                success: function(res) {
                    if (res.code === 0 && res.data.length > 0) {
                        showCourseList(res.data);
                    }
                }
            });
        }, 300);
    });

    // 显示课程列表
    function showCourseList(courses) {
        var html = '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #e6e6e6; border-radius: 4px;">';
        courses.forEach(function(course) {
            html += '<div class="course-item" data-id="' + course.id + '" style="padding: 10px; cursor: pointer; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center;">';
            if (course.cover) {
                html += '<img src="' + course.cover + '" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px; margin-right: 10px;">';
            }
            html += '<div>';
            html += '<div style="font-weight: bold; margin-bottom: 2px;">' + course.title + '</div>';
            html += '<div style="font-size: 12px; color: #999;">ID: ' + course.id + '</div>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';

        layer.open({
            type: 1,
            title: '选择课程',
            area: ['500px', 'auto'],
            content: html,
            success: function(layero, index) {
                layero.find('.course-item').hover(
                    function() { $(this).css('background', '#f8f8f8'); },
                    function() { $(this).css('background', '#fff'); }
                ).on('click', function() {
                    var courseId = $(this).data('id');
                    selectCourse(courseId);
                    layer.close(index);
                });
            }
        });
    }

    // 选择课程
    function selectCourse(courseId) {
        layer.load(2);
        $.ajax({
            type: 'POST',
            url: '/admin/data_board/set_course',
            data: { course_id: courseId },
            dataType: 'json',
            success: function(res) {
                layer.closeAll('loading');
                if (res.code === 0) {
                    layer.msg('课程设置成功，正在刷新页面...', {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg || '设置失败', {icon: 2});
                }
            },
            error: function() {
                layer.closeAll('loading');
                layer.msg('操作失败，请稍后重试', {icon: 2});
            }
        });
    }

    // 保存课程看板设置（标题、副标题、简介）
    $('#save-intro-btn').on('click', function() {
        var title = $('#course_title').val();
        var subtitle = $('#course_subtitle').val();
        var intro = $('#course_intro').val();
        
        if (!title) {
            layer.msg('主标题不能为空', {icon: 2});
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: '/admin/data_board/update_course_intro',
            data: {
                course_title: title,
                course_subtitle: subtitle,
                course_intro: intro
            },
            dataType: 'json',
            success: function(res) {
                if (res.code === 0) {
                    layer.msg('保存成功', {icon: 1, time: 1500});
                } else {
                    layer.msg(res.msg || '保存失败', {icon: 2});
                }
            },
            error: function() {
                layer.msg('操作失败，请稍后重试', {icon: 2});
            }
        });
    });

    // 虚拟值输入变化时自动更新显示值
    $('.virtual-input').on('input', function() {
        var row = $(this).closest('tr');
        var realValue = parseInt(row.find('.display-value').data('real')) || 0;
        var virtualValue = parseInt($(this).val()) || 0;
        row.find('.display-value').text(realValue + virtualValue);
    });

    // 保存统计项
    $('.save-stat-btn').on('click', function() {
        var statId = $(this).data('id');
        var row = $(this).closest('tr');
        var virtualValue = row.find('.virtual-input').val();
        var isVisible = row.find('.visible-switch').is(':checked') ? 1 : 0;
        
        $.ajax({
            type: 'POST',
            url: '/admin/data_board/update_course_stat',
            data: {
                id: statId,
                virtual_value: virtualValue,
                is_visible: isVisible
            },
            dataType: 'json',
            success: function(res) {
                if (res.code === 0) {
                    layer.msg('保存成功', {icon: 1, time: 1500});
                } else {
                    layer.msg(res.msg || '保存失败', {icon: 2});
                }
            },
            error: function() {
                layer.msg('操作失败，请稍后重试', {icon: 2});
            }
        });
    });

    // 刷新单个统计项
    $('.refresh-course-single-btn').on('click', function() {
        var statId = $(this).data('id');
        var btn = $(this);
        var row = btn.closest('tr');
        
        btn.prop('disabled', true).html('<i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i>刷新中');
        
        $.ajax({
            type: 'POST',
            url: '/admin/data_board/refresh_course_single/' + statId,
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="layui-icon layui-icon-refresh"></i>刷新');
                if (res.code === 0) {
                    layer.msg('刷新成功，正在重新加载...', {icon: 1, time: 1000}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg || '刷新失败', {icon: 2});
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="layui-icon layui-icon-refresh"></i>刷新');
                layer.msg('操作失败，请稍后重试', {icon: 2});
            }
        });
    });

    // 刷新所有统计项
    $('#refresh-course-all-btn').on('click', function() {
        layer.confirm('确定要刷新所有课程统计项的真实数据吗？', {
            btn: ['确定', '取消']
        }, function(index) {
            layer.close(index);
            
            var btn = $('#refresh-course-all-btn');
            btn.prop('disabled', true).html('<i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i>刷新中');
            
            $.ajax({
                type: 'POST',
                url: '/admin/data_board/refresh_course',
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="layui-icon layui-icon-refresh"></i>刷新全部真实数据');
                    if (res.code === 0) {
                        layer.msg('刷新成功，正在重新加载...', {icon: 1, time: 1000}, function() {
                            location.reload();
                        });
                    } else {
                        layer.msg(res.msg || '刷新失败', {icon: 2});
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html('<i class="layui-icon layui-icon-refresh"></i>刷新全部真实数据');
                    layer.msg('操作失败，请稍后重试', {icon: 2});
                }
            });
        });
    });

    // 渲染表单
    form.render();
});


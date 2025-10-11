/**
 * 作业列表管理
 */

layui.use(['layer', 'form', 'table'], function () {
    var $ = layui.$;
    var layer = layui.layer;
    var form = layui.form;
    var table = layui.table;

    // 全选/反选
    form.on('checkbox(check-all)', function (data) {
        var checked = data.elem.checked;
        $('input[name="assignment_ids"]').prop('checked', checked);
        form.render('checkbox');
    });

    // 单选处理
    form.on('checkbox(check-item)', function (data) {
        var allChecked = $('input[name="assignment_ids"]:checked').length === $('input[name="assignment_ids"]').length;
        $('input[lay-filter="check-all"]').prop('checked', allChecked);
        form.render('checkbox');
    });

    // 批量操作
    $('.kg-toolbar button[data-action]').on('click', function () {
        var action = $(this).data('action');
        var checkedIds = [];
        
        $('input[name="assignment_ids"]:checked').each(function () {
            checkedIds.push($(this).val());
        });

        if (checkedIds.length === 0) {
            layer.msg('请选择要操作的作业');
            return;
        }

        var actionText = {
            'publish': '发布',
            'close': '关闭',
            'archive': '归档'
        };

        layer.confirm('确定要' + actionText[action] + '选中的 ' + checkedIds.length + ' 个作业吗？', function (index) {
            $.post('/admin/assignment/batch', {
                action: action,
                ids: checkedIds.join(',')
            }, function (res) {
                if (res.code === 0) {
                    layer.msg(res.msg || '操作成功', { icon: 1 }, function () {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg || '操作失败', { icon: 2 });
                }
            });
            layer.close(index);
        });
    });

    // 单个作业操作
    $('.kg-table-actions button[data-action]').on('click', function () {
        var action = $(this).data('action');
        var id = $(this).data('id');
        var $this = $(this);

        switch (action) {
            case 'publish':
                layer.confirm('确定要发布这个作业吗？', function (index) {
                    $.post('/admin/assignment/publish', { id: id }, function (res) {
                        if (res.code === 0) {
                            layer.msg('发布成功', { icon: 1 }, function () {
                                location.reload();
                            });
                        } else {
                            layer.msg(res.msg || '操作失败', { icon: 2 });
                        }
                    });
                    layer.close(index);
                });
                break;

            case 'duplicate':
                layer.prompt({
                    title: '复制作业',
                    formType: 0,
                    value: '副本 - ' + $this.closest('tr').find('.kg-link').text().trim()
                }, function (value, index) {
                    $.post('/admin/assignment/duplicate', {
                        id: id,
                        title: value
                    }, function (res) {
                        if (res.code === 0) {
                            layer.msg('复制成功', { icon: 1 }, function () {
                                location.reload();
                            });
                        } else {
                            layer.msg(res.msg || '操作失败', { icon: 2 });
                        }
                    });
                    layer.close(index);
                });
                break;

            case 'delete':
                layer.confirm('确定要删除这个作业吗？删除后不可恢复！', function (index) {
                    $.post('/admin/assignment/delete', { id: id }, function (res) {
                        if (res.code === 0) {
                            layer.msg('删除成功', { icon: 1 }, function () {
                                location.reload();
                            });
                        } else {
                            layer.msg(res.msg || '操作失败', { icon: 2 });
                        }
                    });
                    layer.close(index);
                });
                break;
        }
    });

    // 状态标签颜色处理
    $('.layui-badge').each(function () {
        var $badge = $(this);
        var text = $badge.text().trim();
        
        switch (text) {
            case '草稿':
                $badge.addClass('layui-bg-gray');
                break;
            case '已发布':
                $badge.addClass('layui-bg-green');
                break;
            case '已关闭':
                $badge.addClass('layui-bg-orange');
                break;
            case '已归档':
                $badge.addClass('layui-bg-red');
                break;
        }
    });

    // 搜索表单提交
    $('.kg-form').on('submit', function (e) {
        // 移除空值参数
        $(this).find('input, select').each(function () {
            if (!$(this).val()) {
                $(this).prop('disabled', true);
            }
        });
    });

});

layui.use(['layer', 'jquery'], function () {

    var $ = layui.jquery;
    var layer = layui.layer;

    // 保存标题和副标题
    $('#save-title-btn').on('click', function () {
        var title = $('#board_title').val();
        var subtitle = $('#board_subtitle').val();
        
        if (!title) {
            layer.msg('主标题不能为空', {icon: 2});
            return;
        }
        
        $.ajax({
            type: 'POST',
            url: '/admin/data_board/update_title',
            data: {
                board_title: title,
                board_subtitle: subtitle
            },
            dataType: 'json',
            success: function (res) {
                if (res.code === 0) {
                    layer.msg('保存成功', {icon: 1, time: 1500});
                } else {
                    layer.msg(res.msg || '保存失败', {icon: 2});
                }
            },
            error: function (xhr) {
                layer.msg('操作失败，请稍后重试', {icon: 2});
            }
        });
    });

    // 刷新全部真实数据
    $('#refresh-all-btn').on('click', function () {
        layer.confirm('确认要刷新全部统计项的真实数据吗？', function (index) {
            $.ajax({
                type: 'POST',
                url: '/admin/data_board/refresh',
                dataType: 'json',
                success: function (res) {
                    layer.msg(res.msg, {icon: 1, time: 1000}, function () {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    layer.msg('操作失败，请稍后重试', {icon: 2});
                }
            });
            layer.close(index);
        });
    });

    // 刷新单个真实数据
    $('.refresh-single-btn').on('click', function () {
        var id = $(this).data('id');
        var $btn = $(this);
        
        layer.confirm('确认要刷新该统计项的真实数据吗？', function (index) {
            $btn.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: '/admin/data_board/refresh/' + id,
                dataType: 'json',
                success: function (res) {
                    layer.msg(res.msg, {icon: 1, time: 1000}, function () {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    layer.msg('操作失败，请稍后重试', {icon: 2});
                    $btn.prop('disabled', false);
                }
            });
            layer.close(index);
        });
    });

});


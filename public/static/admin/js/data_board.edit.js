layui.use(['layer', 'form', 'jquery'], function () {

    var $ = layui.jquery;
    var layer = layui.layer;
    var form = layui.form;

    form.on('submit(go)', function (data) {
        var field = data.field;

        $.ajax({
            type: 'POST',
            url: '/admin/data_board/update',
            data: field,
            dataType: 'json',
            success: function (res) {
                if (res.code === 0) {
                    layer.msg(res.msg, {icon: 1, time: 1000}, function () {
                        window.location.href = '/admin/data_board/list';
                    });
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function (xhr) {
                layer.msg('操作失败，请稍后重试', {icon: 2});
            }
        });

        return false;
    });

    // 返回按钮
    $('.kg-back').on('click', function () {
        window.location.href = '/admin/data_board/list';
    });

});


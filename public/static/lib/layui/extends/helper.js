layui.define(['jquery', 'layer'], function (exports) {

    var MOD_NAME = 'helper';
    var $ = layui.jquery;
    var layer = layui.layer;

    var helper = {};

    helper.isEmail = function (email) {
        return /^([a-zA-Z]|[0-9])(\w|\-)+@[a-zA-Z0-9]+\.([a-zA-Z]{2,4})$/.test(email);
    };

    helper.isPhone = function (phone) {
        return /^1(3|4|5|6|7|8|9)\d{9}$/.test(phone);
    };

    helper.getRequestId = function () {
        var id = Date.now().toString(36);
        id += Math.random().toString(36).substring(3);
        return id;
    };

    helper.ajaxLoadHtml = function (url, target) {
        var $target = $('#' + target);
        var html = '<div class="loading"><i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i></div>';
        $target.html(html);
        $.get(url, function (html) {
            $target.html(html);
        }).fail(function(xhr, status, error) {
            console.error('AJAX加载失败:', url, status, error, xhr);
            var errorHtml = '<div class="no-records"><p><i class="layui-icon layui-icon-face-cry" style="font-size: 48px; color: #E6E6E6;"></i></p><p>加载失败：' + (xhr.statusText || '服务器错误') + '</p></div>';
            $target.html(errorHtml);
        });
    };

    /**
     * 前端日志记录器 - 将日志发送到服务器error_log
     * @param {string} level - 日志级别: info, warn, error
     * @param {string} message - 日志消息
     * @param {*} data - 可选的附加数据
     */
    helper.serverLog = function(level, message, data) {
        var logData = {
            level: level || 'info',
            message: message,
            url: window.location.href,
            timestamp: new Date().toISOString()
        };
        
        if (data !== undefined) {
            logData.data = typeof data === 'object' ? JSON.stringify(data) : String(data);
        }
        
        // 同时输出到浏览器控制台（开发调试用）
        if (level === 'error') {
            console.error('[ServerLog]', message, data);
        } else if (level === 'warn') {
            console.warn('[ServerLog]', message, data);
        } else {
            console.log('[ServerLog]', message, data);
        }
        
        // 发送到后端记录日志（使用$.ajax确保兼容性）
        $.ajax({
            url: '/api/log/frontend',
            type: 'POST',
            data: JSON.stringify(logData),
            contentType: 'application/json',
            async: true,
            timeout: 2000,
            success: function() {
                // 日志发送成功
            },
            error: function(xhr, status, error) {
                console.error('[ServerLog] 发送失败:', status, error, xhr);
            }
        });
    };

    helper.checkLogin = function (callback) {
        if (window.user.id === '0') {
            layer.msg('继续操作前请先登录', {icon: 2, anim: 6});
            return false;
        }
        callback();
    };

    helper.wechatShare = function (qrcode) {
        var content = '<div class="qrcode"><img src="' + qrcode + '" alt="分享到微信"></div>';
        layer.open({
            type: 1,
            title: false,
            closeBtn: 0,
            shadeClose: true,
            content: content
        });
    };

    helper.qqShare = function (title, url, pic) {
        var shareUrl = 'http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?';
        shareUrl += 'title=' + encodeURIComponent(title || document.title);
        shareUrl += '&url=' + encodeURIComponent(url || document.location);
        shareUrl += '&pics=' + pic;
        window.open(shareUrl, '_blank');
    };

    helper.weiboShare = function (title, url, pic) {
        var shareUrl = 'http://service.weibo.com/share/share.php?';
        shareUrl += 'title=' + encodeURIComponent(title || document.title);
        shareUrl += '&url=' + encodeURIComponent(url || document.location);
        shareUrl += '&pic=' + encodeURIComponent(pic || '');
        window.open(shareUrl, '_blank');
    };

    exports(MOD_NAME, helper);
});
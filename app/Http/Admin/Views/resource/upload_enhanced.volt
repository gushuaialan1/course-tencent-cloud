<!DOCTYPE html>
<html lang="zh-CN-Hans">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="renderer" content="webkit">
    <meta name="csrf-token" content="{{ security.getToken() }}">
    <title>资源管理 - 增强上传 - 管理后台</title>
    {{ icon_link('favicon.ico') }}
    {{ css_link('lib/layui/css/layui.css') }}
    {{ css_link('lib/layui/extends/kg-dropdown.css') }}
    {{ css_link('admin/css/common.css') }}
    {{ css_link('admin/css/resource-enhanced.css') }}
    <!-- Uppy.js CDN -->
    <link href="https://releases.transloadit.com/uppy/v3.3.1/uppy.min.css" rel="stylesheet">
</head>
<body class="kg-body">

    <!-- 导航栏 -->
    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a><cite>资源管理</cite></a>
                <a><cite>增强上传</cite></a>
            </span>
        </div>
        <div class="kg-nav-right">
            <a class="layui-btn layui-btn-sm" href="{{ url({'for':'admin.course.list'}) }}">
                <i class="layui-icon layui-icon-return"></i>返回课程
            </a>
            <a class="layui-btn layui-btn-sm" href="{{ url({'for':'admin.resource.recent'}) }}">
                <i class="layui-icon layui-icon-list"></i>资源列表
            </a>
        </div>
    </div>

    <!-- 主要内容区域 -->
    <div class="layui-row layui-col-space20">
        <!-- 左侧：上传区和设置 -->
        <div class="layui-col-md8">
            <!-- 文件上传卡片 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-upload-drag"></i> 增强文件上传
                    <div class="kg-card-tools">
                        <span class="layui-badge layui-bg-cyan">支持拖拽</span>
                        <span class="layui-badge layui-bg-green">批量上传</span>
                        <span class="layui-badge layui-bg-orange">在线预览</span>
                    </div>
                </div>
                <div class="layui-card-body" style="padding: 0;">
                    <!-- Uppy.js 上传组件区域 -->
                    <div id="uppy-dashboard" class="kg-uppy-container"></div>
                </div>
            </div>

            <!-- 上传设置卡片 -->
            <div class="layui-card" style="margin-top: 15px;">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-set"></i> 上传设置
                </div>
                <div class="layui-card-body">
                    <form class="layui-form" lay-filter="upload-settings">
                        <div class="layui-row">
                            <div class="layui-col-md6">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">资源分类</label>
                                    <div class="layui-input-block">
                                        <select name="category_id" lay-verify="required">
                                            <option value="">请选择分类</option>
                                            <option value="1">课程课件</option>
                                            <option value="2">教学文档</option>
                                            <option value="3">参考资料</option>
                                            <option value="4">作业模板</option>
                                            <option value="5">考试资料</option>
                                            <option value="6">多媒体资源</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md6">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">访问权限</label>
                                    <div class="layui-input-block">
                                        <select name="access_level">
                                            <option value="public">公开访问</option>
                                            <option value="member">会员访问</option>
                                            <option value="course">课程学员</option>
                                            <option value="private">私有访问</option>
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
                                        <select name="course_id">
                                            <option value="">选择课程（可选）</option>
                                            {% for course in courses %}
                                            <option value="{{ course.id }}">{{ course.title }}</option>
                                            {% endfor %}
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-col-md6">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">资源标签</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="tags" placeholder="多个标签用逗号分隔" class="layui-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="layui-form-item layui-form-text">
                            <label class="layui-form-label">资源描述</label>
                            <div class="layui-input-block">
                                <textarea name="description" placeholder="请输入资源描述..." class="layui-textarea" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <div class="layui-input-block">
                                <input type="checkbox" name="auto_extract" title="自动提取文档关键信息" lay-skin="primary" checked>
                                <input type="checkbox" name="generate_preview" title="生成预览图" lay-skin="primary" checked>
                                <input type="checkbox" name="enable_download" title="允许下载" lay-skin="primary" checked>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 右侧：统计和最近上传 -->
        <div class="layui-col-md4">
            <!-- 上传统计 -->
            <div class="layui-row layui-col-space10">
                <div class="layui-col-md12">
                    <div class="kg-stat-card">
                        <div class="kg-stat-number" id="total-files">0</div>
                        <div class="kg-stat-label">总文件数</div>
                    </div>
                </div>
            </div>
            <div class="layui-row layui-col-space10" style="margin-top: 10px;">
                <div class="layui-col-md6">
                    <div class="kg-stat-card" style="background: linear-gradient(135deg, #4CAF50, #66BB6A);">
                        <div class="kg-stat-number" id="upload-success">0</div>
                        <div class="kg-stat-label">成功上传</div>
                    </div>
                </div>
                <div class="layui-col-md6">
                    <div class="kg-stat-card" style="background: linear-gradient(135deg, #FF5722, #FF7043);">
                        <div class="kg-stat-number" id="upload-failed">0</div>
                        <div class="kg-stat-label">上传失败</div>
                    </div>
                </div>
            </div>

            <!-- 支持格式 -->
            <div class="layui-card" style="margin-top: 15px;">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-file"></i> 支持格式
                </div>
                <div class="layui-card-body">
                    <div class="kg-file-type-grid">
                        <div class="kg-file-type-item">
                            <div class="kg-file-type-icon">
                                <i class="layui-icon layui-icon-file-b"></i>
                            </div>
                            <div class="kg-file-type-name">PDF文档</div>
                            <div class="kg-file-type-count">.pdf</div>
                        </div>
                        <div class="kg-file-type-item">
                            <div class="kg-file-type-icon">
                                <i class="layui-icon layui-icon-template"></i>
                            </div>
                            <div class="kg-file-type-name">Office</div>
                            <div class="kg-file-type-count">.doc .xls .ppt</div>
                        </div>
                        <div class="kg-file-type-item">
                            <div class="kg-file-type-icon">
                                <i class="layui-icon layui-icon-picture"></i>
                            </div>
                            <div class="kg-file-type-name">图片</div>
                            <div class="kg-file-type-count">.jpg .png .gif</div>
                        </div>
                        <div class="kg-file-type-item">
                            <div class="kg-file-type-icon">
                                <i class="layui-icon layui-icon-video"></i>
                            </div>
                            <div class="kg-file-type-name">视频</div>
                            <div class="kg-file-type-count">.mp4 .avi</div>
                        </div>
                        <div class="kg-file-type-item">
                            <div class="kg-file-type-icon">
                                <i class="layui-icon layui-icon-music"></i>
                            </div>
                            <div class="kg-file-type-name">音频</div>
                            <div class="kg-file-type-count">.mp3 .wav</div>
                        </div>
                        <div class="kg-file-type-item">
                            <div class="kg-file-type-icon">
                                <i class="layui-icon layui-icon-file"></i>
                            </div>
                            <div class="kg-file-type-name">压缩包</div>
                            <div class="kg-file-type-count">.zip .rar</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 最近上传 -->
            <div class="layui-card" style="margin-top: 15px;">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-time"></i> 最近上传
                    <div class="kg-card-tools">
                        <a href="javascript:;" onclick="refreshRecentUploads()" title="刷新">
                            <i class="layui-icon layui-icon-refresh-1"></i>
                        </a>
                    </div>
                </div>
                <div class="layui-card-body" style="padding: 0;">
                    <div class="kg-recent-uploads" id="recent-uploads">
                        <!-- 通过AJAX加载最近上传的文件 -->
                        <div style="text-align: center; padding: 20px; color: #999;">
                            <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></i>
                            加载中...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 隐藏表单数据 -->
    <input type="hidden" name="course_id" value="{{ course_id|default('') }}">

    <!-- JavaScript依赖 -->
    {{ js_include('lib/jquery.min.js') }}
    {{ js_include('lib/layui/layui.js') }}
    
    <!-- Uppy.js CDN -->
    <script src="https://releases.transloadit.com/uppy/v3.3.1/uppy.min.js"></script>
    
    <!-- 自定义增强上传脚本 -->
    {{ js_include('admin/js/uppy-enhanced.js') }}

    <script>
    layui.use(['form', 'layer', 'uppyEnhanced'], function() {
        var form = layui.form;
        var layer = layui.layer;
        var uppyEnhanced = layui.uppyEnhanced;
        
        // 统计数据
        var stats = {
            totalFiles: 0,
            successCount: 0,
            failedCount: 0
        };
        
        // 初始化增强上传器
        var uploader = uppyEnhanced.render({
            target: '#uppy-dashboard',
            endpoint: '/api/upload/enhanced',
            onUploadSuccess: function(file, response) {
                stats.totalFiles++;
                stats.successCount++;
                updateStats();
                loadRecentUploads();
            },
            onUploadError: function(file, error, response) {
                stats.totalFiles++;
                stats.failedCount++;
                updateStats();
            },
            onFileAdded: function(file) {
                console.log('文件已添加:', file.name);
            },
            onComplete: function(result) {
                layer.msg('上传完成！成功: ' + result.successful.length + ', 失败: ' + result.failed.length, {
                    icon: result.failed.length > 0 ? 2 : 1,
                    time: 3000
                });
            }
        });
        
        // 更新统计数据
        function updateStats() {
            $('#total-files').text(stats.totalFiles);
            $('#upload-success').text(stats.successCount);
            $('#upload-failed').text(stats.failedCount);
        }
        
        // 加载最近上传
        function loadRecentUploads() {
            $.get('/admin/resource/recent', function(data) {
                var html = '';
                if (data.length > 0) {
                    $.each(data, function(i, item) {
                        html += '<div class="kg-recent-item">';
                        html += '<div class="kg-recent-icon">';
                        html += '<i class="layui-icon layui-icon-file"></i>';
                        html += '</div>';
                        html += '<div class="kg-recent-info">';
                        html += '<div class="kg-recent-name">' + item.name + '</div>';
                        html += '<div class="kg-recent-meta">';
                        html += '<span class="kg-recent-size">' + formatFileSize(item.size) + '</span>';
                        html += '<span class="kg-recent-time">' + item.create_time + '</span>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="kg-recent-actions">';
                        html += '<button class="kg-recent-btn" title="预览" onclick="previewFile(' + item.id + ')">';
                        html += '<i class="layui-icon layui-icon-search"></i>';
                        html += '</button>';
                        html += '<button class="kg-recent-btn" title="下载" onclick="downloadFile(' + item.id + ')">';
                        html += '<i class="layui-icon layui-icon-download-circle"></i>';
                        html += '</button>';
                        html += '</div>';
                        html += '</div>';
                    });
                } else {
                    html = '<div style="text-align: center; padding: 20px; color: #999;">暂无上传记录</div>';
                }
                $('#recent-uploads').html(html);
            }).fail(function() {
                $('#recent-uploads').html('<div style="text-align: center; padding: 20px; color: #999;">加载失败</div>');
            });
        }
        
        // 格式化文件大小
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 预览文件
        window.previewFile = function(id) {
            layer.open({
                type: 2,
                title: '文件预览',
                area: ['80%', '80%'],
                content: '/admin/resource/' + id + '/preview'
            });
        };
        
        // 下载文件
        window.downloadFile = function(id) {
            window.open('/admin/resource/' + id + '/download');
        };
        
        // 刷新最近上传
        window.refreshRecentUploads = function() {
            loadRecentUploads();
        };
        
        // 页面加载完成后初始化
        $(document).ready(function() {
            loadRecentUploads();
            updateStats();
        });
    });
    </script>
</body>
</html>

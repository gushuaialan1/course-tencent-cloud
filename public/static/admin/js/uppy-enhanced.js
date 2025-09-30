/**
 * Uppy.js 增强文件上传组件
 * 用于酷瓜云课堂资源管理系统
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

layui.define(['jquery', 'layer', 'element'], function(exports) {
    var $ = layui.jquery;
    var layer = layui.layer;
    var element = layui.element;

    /**
     * 增强上传器类
     */
    var EnhancedUploader = function(options) {
        this.options = $.extend({
            target: '#uppy-dashboard',
            endpoint: '/api/upload/enhanced',
            maxFileSize: 100 * 1024 * 1024, // 100MB
            allowedFileTypes: [
                '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx',
                '.txt', '.zip', '.rar', '.jpg', '.jpeg', '.png', '.gif',
                '.mp4', '.avi', '.mov', '.mp3', '.wav'
            ],
            locale: 'zh_CN',
            theme: 'light',
            width: '100%',
            height: 400,
            showProgressDetails: true,
            note: '支持拖拽上传，最大文件100MB',
            proudlyDisplayPoweredByUppy: false,
            onUploadSuccess: null,
            onUploadError: null,
            onFileAdded: null,
            onComplete: null
        }, options);

        this.uppy = null;
        this.init();
    };

    EnhancedUploader.prototype = {
        /**
         * 初始化上传器
         */
        init: function() {
            var self = this;
            
            // 检查Uppy是否已加载
            if (typeof Uppy === 'undefined') {
                console.error('Uppy.js未加载，请检查CDN链接');
                layer.msg('上传组件加载失败', {icon: 2});
                return;
            }

            // 创建Uppy实例
            this.uppy = new Uppy.Core({
                id: 'kg-uploader',
                autoProceed: false,
                allowMultipleUploads: true,
                restrictions: {
                    maxFileSize: this.options.maxFileSize,
                    allowedFileTypes: this.options.allowedFileTypes,
                    maxNumberOfFiles: 10
                },
                locale: this.getLocale(),
                meta: {
                    uploader: 'kg-enhanced'
                }
            });

            // 使用Dashboard插件
            this.uppy.use(Uppy.Dashboard, {
                id: 'Dashboard',
                target: this.options.target,
                inline: true,
                width: this.options.width,
                height: this.options.height,
                theme: this.options.theme,
                showProgressDetails: this.options.showProgressDetails,
                note: this.options.note,
                proudlyDisplayPoweredByUppy: this.options.proudlyDisplayPoweredByUppy,
                locale: {
                    strings: {
                        dropPasteFiles: '拖拽文件到这里或者 %{browseFiles}',
                        browseFiles: '选择文件',
                        uploadComplete: '上传完成',
                        uploadFailed: '上传失败',
                        paused: '已暂停',
                        complete: '完成',
                        filesUploadedOfTotal: {
                            0: '已上传 %{complete} / %{smart_count} 个文件',
                            1: '已上传 %{complete} / %{smart_count} 个文件'
                        },
                        dataUploadedOfTotal: '已上传 %{complete} / %{total}',
                        xFilesSelected: {
                            0: '已选择 %{smart_count} 个文件',
                            1: '已选择 %{smart_count} 个文件'
                        },
                        uploadingXFiles: {
                            0: '正在上传 %{smart_count} 个文件',
                            1: '正在上传 %{smart_count} 个文件'
                        }
                    }
                }
            });

            // 使用XHRUpload插件
            this.uppy.use(Uppy.XHRUpload, {
                id: 'XHRUpload',
                endpoint: this.options.endpoint,
                formData: true,
                fieldName: 'files[]',
                headers: {
                    'X-CSRF-Token': this.getCsrfToken()
                },
                timeout: 0,
                limit: 5
            });

            // 设置事件监听器
            this.setupEventHandlers();
        },

        /**
         * 设置事件处理器
         */
        setupEventHandlers: function() {
            var self = this;

            // 文件添加事件
            this.uppy.on('file-added', function(file) {
                console.log('文件已添加:', file.name);
                if (self.options.onFileAdded) {
                    self.options.onFileAdded(file);
                }
            });

            // 上传成功事件
            this.uppy.on('upload-success', function(file, response) {
                console.log('文件上传成功:', file.name, response.body);
                
                // 显示成功提示
                layer.msg('文件 "' + file.name + '" 上传成功', {icon: 1});
                
                if (self.options.onUploadSuccess) {
                    self.options.onUploadSuccess(file, response.body);
                }
            });

            // 上传错误事件
            this.uppy.on('upload-error', function(file, error, response) {
                console.error('文件上传失败:', file.name, error);
                
                var errorMsg = '文件 "' + file.name + '" 上传失败';
                if (response && response.body && response.body.message) {
                    errorMsg += ': ' + response.body.message;
                }
                
                layer.msg(errorMsg, {icon: 2, time: 5000});
                
                if (self.options.onUploadError) {
                    self.options.onUploadError(file, error, response);
                }
            });

            // 上传完成事件
            this.uppy.on('complete', function(result) {
                console.log('所有上传完成:', result);
                
                var successCount = result.successful.length;
                var failedCount = result.failed.length;
                
                if (successCount > 0) {
                    layer.msg('成功上传 ' + successCount + ' 个文件', {icon: 1});
                }
                
                if (failedCount > 0) {
                    layer.msg('有 ' + failedCount + ' 个文件上传失败', {icon: 2});
                }
                
                if (self.options.onComplete) {
                    self.options.onComplete(result);
                }
            });

            // 限制错误事件
            this.uppy.on('restriction-failed', function(file, error) {
                var errorMsg = '文件 "' + file.name + '" ';
                
                if (error.type === 'maxFileSize') {
                    errorMsg += '大小超过限制 (' + self.formatFileSize(self.options.maxFileSize) + ')';
                } else if (error.type === 'allowedFileTypes') {
                    errorMsg += '类型不支持';
                } else if (error.type === 'maxNumberOfFiles') {
                    errorMsg += '超过最大文件数量限制';
                } else {
                    errorMsg += '不符合上传要求';
                }
                
                layer.msg(errorMsg, {icon: 2});
            });
        },

        /**
         * 获取CSRF令牌
         */
        getCsrfToken: function() {
            return $('meta[name="csrf-token"]').attr('content') || '';
        },

        /**
         * 获取本地化配置
         */
        getLocale: function() {
            return {
                strings: {
                    addMoreFiles: '添加更多文件',
                    addingMoreFiles: '正在添加更多文件',
                    allowAccessDescription: '请允许访问您的相机，以便拍照或录制视频。',
                    allowAccessTitle: '请允许访问您的相机',
                    authenticateWith: '连接到 %{pluginName}',
                    authenticateWithTitle: '请使用 %{pluginName} 进行身份验证以选择文件',
                    back: '返回',
                    browse: '浏览',
                    browseFiles: '浏览文件',
                    cancel: '取消',
                    cancelUpload: '取消上传',
                    chooseFiles: '选择文件',
                    closeModal: '关闭弹窗',
                    companionError: '无法连接到 Companion',
                    complete: '完成',
                    connectedToInternet: '已连接到互联网',
                    copyLink: '复制链接',
                    copyLinkToClipboardFallback: '复制以下链接',
                    copyLinkToClipboardSuccess: '链接已复制到剪贴板',
                    creatingAssembly: '正在准备上传...',
                    creatingAssemblyFailed: '无法创建组装',
                    dashboardTitle: '文件上传器',
                    dashboardWindowTitle: '文件上传器窗口 (按 Escape 键关闭)',
                    dataUploadedOfTotal: '已上传 %{complete} / %{total}',
                    done: '完成',
                    dropHereOr: '拖放文件到这里或 %{browse}',
                    dropPaste: '将文件拖放到这里，粘贴或 %{browse}',
                    dropPasteFiles: '将文件拖放到这里，粘贴或 %{browseFiles}',
                    dropPasteFolders: '将文件拖放到这里，粘贴或 %{browseFolders}',
                    dropPasteImportBoth: '将文件拖放到这里，粘贴，%{browse} 或导入',
                    dropPasteImportFiles: '将文件拖放到这里，粘贴，%{browseFiles} 或导入',
                    dropPasteImportFolders: '将文件拖放到这里，粘贴，%{browseFolders} 或导入',
                    editFile: '编辑文件',
                    editing: '正在编辑 %{file}',
                    emptyFolderAdded: '空文件夹中没有添加文件',
                    encoding: '编码中...',
                    enterCorrectUrl: '输入正确的链接',
                    enterUrlToImport: '输入链接以导入文件',
                    exceedsSize: '此文件超过允许的最大大小 %{size}',
                    failedToFetch: '获取此链接失败，请检查其是否正确',
                    failedToUpload: '上传 %{file} 失败',
                    fileSource: '文件来源：%{name}',
                    filesUploadedOfTotal: {
                        0: '已上传 %{complete} / %{smart_count} 个文件',
                        1: '已上传 %{complete} / %{smart_count} 个文件'
                    },
                    filter: '筛选',
                    finishEditingFile: '完成编辑文件',
                    folderAdded: {
                        0: '从 %{folder} 添加了 %{smart_count} 个文件',
                        1: '从 %{folder} 添加了 %{smart_count} 个文件'
                    },
                    import: '导入',
                    importFrom: '从 %{name} 导入',
                    loading: '加载中...',
                    logOut: '退出登录',
                    myDevice: '我的设备',
                    noFilesFound: '您没有任何文件或文件夹',
                    noInternetConnection: '没有互联网连接',
                    pause: '暂停',
                    pauseUpload: '暂停上传',
                    paused: '已暂停',
                    poweredBy: '基于 %{uppy}',
                    processingXFiles: {
                        0: '正在处理 %{smart_count} 个文件',
                        1: '正在处理 %{smart_count} 个文件'
                    },
                    removeFile: '移除文件',
                    resetFilter: '重置筛选',
                    resume: '恢复',
                    resumeUpload: '恢复上传',
                    retry: '重试',
                    retryUpload: '重新上传',
                    saveChanges: '保存更改',
                    selectXFiles: {
                        0: '选择 %{smart_count} 个文件',
                        1: '选择 %{smart_count} 个文件'
                    },
                    smile: '微笑！',
                    startRecording: '开始录制',
                    stopRecording: '停止录制',
                    takePicture: '拍照',
                    timedOut: '上传停滞了 %{seconds} 秒，正在中止。',
                    upload: '上传',
                    uploadComplete: '上传完成',
                    uploadFailed: '上传失败',
                    uploadPaused: '上传已暂停',
                    uploadXFiles: {
                        0: '上传 %{smart_count} 个文件',
                        1: '上传 %{smart_count} 个文件'
                    },
                    uploadXNewFiles: {
                        0: '上传 +%{smart_count} 个文件',
                        1: '上传 +%{smart_count} 个文件'
                    },
                    uploading: '正在上传',
                    uploadingXFiles: {
                        0: '正在上传 %{smart_count} 个文件',
                        1: '正在上传 %{smart_count} 个文件'
                    },
                    xFilesSelected: {
                        0: '已选择 %{smart_count} 个文件',
                        1: '已选择 %{smart_count} 个文件'
                    },
                    xMoreFilesAdded: {
                        0: '又添加了 %{smart_count} 个文件',
                        1: '又添加了 %{smart_count} 个文件'
                    },
                    xTimeLeft: '剩余 %{time}',
                    youCanOnlyUploadFileTypes: '您只能上传：%{types}',
                    youCanOnlyUploadX: {
                        0: '您只能上传 %{smart_count} 个文件',
                        1: '您只能上传 %{smart_count} 个文件'
                    },
                    youHaveToAtLeastSelectX: {
                        0: '您至少需要选择 %{smart_count} 个文件',
                        1: '您至少需要选择 %{smart_count} 个文件'
                    }
                }
            };
        },

        /**
         * 格式化文件大小
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /**
         * 获取已选择的文件
         */
        getSelectedFiles: function() {
            return this.uppy ? this.uppy.getFiles() : [];
        },

        /**
         * 开始上传
         */
        upload: function() {
            if (this.uppy) {
                this.uppy.upload();
            }
        },

        /**
         * 取消上传
         */
        cancelAll: function() {
            if (this.uppy) {
                this.uppy.cancelAll();
            }
        },

        /**
         * 重置上传器
         */
        reset: function() {
            if (this.uppy) {
                this.uppy.reset();
            }
        },

        /**
         * 销毁上传器
         */
        destroy: function() {
            if (this.uppy) {
                this.uppy.close();
                this.uppy = null;
            }
        }
    };

    // 导出模块
    exports('uppyEnhanced', {
        /**
         * 创建增强上传器实例
         */
        render: function(options) {
            return new EnhancedUploader(options);
        },

        /**
         * 版本信息
         */
        version: '1.0.0'
    });
});

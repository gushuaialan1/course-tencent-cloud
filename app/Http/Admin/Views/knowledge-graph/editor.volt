<!DOCTYPE html>
<html lang="zh-CN-Hans">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="renderer" content="webkit">
    <meta name="csrf-token" content="{{ security.getToken() }}">
    <title>知识图谱编辑器 - {{ course.title }} - 管理后台</title>
    {{ icon_link('favicon.ico') }}
    {{ css_link('lib/layui/css/layui.css') }}
    {{ css_link('lib/layui/extends/kg-dropdown.css') }}
    {{ css_link('admin/css/common.css') }}
    {{ css_link('admin/css/knowledge-graph.css') }}
    <!-- Cytoscape.js CDN -->
    <script src="https://unpkg.com/cytoscape@3.26.0/dist/cytoscape.min.js"></script>
    <!-- 修复：先加载dagre基础库，再加载cytoscape扩展 -->
    <script src="https://unpkg.com/dagre@0.8.5/dist/dagre.min.js"></script>
    <script src="https://unpkg.com/cytoscape-dagre@2.5.0/cytoscape-dagre.js"></script>
</head>
<body class="kg-body">

    <!-- 导航栏 -->
    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a href="{{ url({'for':'admin.course.list'}) }}"><cite>课程管理</cite></a>
                <a href="{{ url({'for':'admin.knowledge_graph.list'}) }}"><cite>知识图谱</cite></a>
                <a><cite>{{ course.title }}</cite></a>
            </span>
        </div>
        <div class="kg-nav-right">
            <button class="layui-btn layui-btn-sm layui-btn-warm" id="btn-use-template">
                <i class="layui-icon layui-icon-template-1"></i>使用模板
            </button>
            <button class="layui-btn layui-btn-sm" id="btn-save-graph">
                <i class="layui-icon layui-icon-ok"></i>保存图谱
            </button>
            <button class="layui-btn layui-btn-sm layui-btn-normal" id="btn-save-as-template">
                <i class="layui-icon layui-icon-templeate-1"></i>保存为模板
            </button>
            <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-export-graph">
                <i class="layui-icon layui-icon-export"></i>导出
            </button>
            <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-import-graph">
                <i class="layui-icon layui-icon-upload"></i>导入
            </button>
            <a class="layui-btn layui-btn-sm layui-btn-primary" href="{{ url({'for':'admin.knowledge_graph.list'}) }}">
                <i class="layui-icon layui-icon-return"></i>返回列表
            </a>
        </div>
    </div>

    <!-- 主要内容区域 -->
    <div class="kg-graph-main">
        <!-- 左侧工具面板 -->
        <div class="kg-tool-panel">
            <!-- 节点工具箱 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-component"></i> 节点工具
                </div>
                <div class="layui-card-body">
                    <div class="kg-node-tools">
                        <!-- 概念节点 -->
                        <div class="kg-node-tool" data-node-type="concept" title="概念节点">
                            <div class="kg-node-preview kg-concept-node">
                                <i class="layui-icon layui-icon-circle"></i>
                            </div>
                            <span>概念</span>
                        </div>
                        <!-- 主题节点 -->
                        <div class="kg-node-tool" data-node-type="topic" title="主题节点">
                            <div class="kg-node-preview kg-topic-node">
                                <i class="layui-icon layui-icon-template-1"></i>
                            </div>
                            <span>主题</span>
                        </div>
                        <!-- 技能节点 -->
                        <div class="kg-node-tool" data-node-type="skill" title="技能节点">
                            <div class="kg-node-preview kg-skill-node">
                                <i class="layui-icon layui-icon-diamond"></i>
                            </div>
                            <span>技能</span>
                        </div>
                        <!-- 课程节点 -->
                        <div class="kg-node-tool" data-node-type="course" title="课程节点">
                            <div class="kg-node-preview kg-course-node">
                                <i class="layui-icon layui-icon-read"></i>
                            </div>
                            <span>课程</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 关系工具箱 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-link"></i> 关系工具
                </div>
                <div class="layui-card-body">
                    <div class="kg-relation-tools">
                        <div class="kg-relation-tool" data-relation-type="prerequisite" title="前置关系">
                            <div class="kg-relation-preview">
                                <i class="layui-icon layui-icon-right" style="color: #FF5722;"></i>
                            </div>
                            <span>前置</span>
                        </div>
                        <div class="kg-relation-tool" data-relation-type="contains" title="包含关系">
                            <div class="kg-relation-preview">
                                <i class="layui-icon layui-icon-link" style="color: #2196F3;"></i>
                            </div>
                            <span>包含</span>
                        </div>
                        <div class="kg-relation-tool" data-relation-type="related" title="相关关系">
                            <div class="kg-relation-preview">
                                <i class="layui-icon layui-icon-transfer" style="color: #4CAF50;"></i>
                            </div>
                            <span>相关</span>
                        </div>
                        <div class="kg-relation-tool" data-relation-type="suggests" title="建议关系">
                            <div class="kg-relation-preview">
                                <i class="layui-icon layui-icon-praise" style="color: #FF9800;"></i>
                            </div>
                            <span>建议</span>
                        </div>
                        <div class="kg-relation-tool" data-relation-type="extends" title="扩展关系">
                            <div class="kg-relation-preview">
                                <i class="layui-icon layui-icon-spread-left" style="color: #9C27B0;"></i>
                            </div>
                            <span>扩展</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 布局工具 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-layouts"></i> 布局算法
                </div>
                <div class="layui-card-body">
                    <div class="kg-btn-group" style="width: 100%;">
                        <button class="layui-btn layui-btn-sm" id="btn-layout-dagre" title="层次布局">
                            <i class="layui-icon layui-icon-tree"></i>
                        </button>
                        <button class="layui-btn layui-btn-sm" id="btn-layout-cose" title="力导向布局">
                            <i class="layui-icon layui-icon-spread-left"></i>
                        </button>
                        <button class="layui-btn layui-btn-sm" id="btn-layout-circle" title="圆形布局">
                            <i class="layui-icon layui-icon-radio"></i>
                        </button>
                        <button class="layui-btn layui-btn-sm" id="btn-layout-grid" title="网格布局">
                            <i class="layui-icon layui-icon-template"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 图谱统计 -->
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-chart-screen"></i> 图谱统计
                </div>
                <div class="layui-card-body">
                    <div class="kg-metric">
                        <span class="kg-metric-label">节点总数</span>
                        <span class="kg-metric-value" id="stat-nodes">{{ statistics.total }}</span>
                    </div>
                    <div class="kg-metric">
                        <span class="kg-metric-label">关系数量</span>
                        <span class="kg-metric-value" id="stat-edges">{{ relation_statistics.total|default(0) }}</span>
                    </div>
                    <div class="kg-metric">
                        <span class="kg-metric-label">概念节点</span>
                        <span class="kg-metric-value">{{ statistics.by_type.concept.count|default(0) }}</span>
                    </div>
                    <div class="kg-metric">
                        <span class="kg-metric-label">主题节点</span>
                        <span class="kg-metric-value">{{ statistics.by_type.topic.count|default(0) }}</span>
                    </div>
                    <div class="kg-metric">
                        <span class="kg-metric-label">技能节点</span>
                        <span class="kg-metric-value">{{ statistics.by_type.skill.count|default(0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 中央画布区域 -->
        <div class="kg-canvas-area">
            <!-- 工具栏 -->
            <div class="kg-canvas-toolbar">
                <div class="kg-btn-group">
                    <button class="layui-btn layui-btn-sm" id="btn-select-mode" title="选择模式">
                        <i class="layui-icon layui-icon-cursor"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm" id="btn-node-mode" title="节点模式">
                        <i class="layui-icon layui-icon-add-circle"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm" id="btn-edge-mode" title="关系模式">
                        <i class="layui-icon layui-icon-link"></i>
                    </button>
                </div>

                <div style="margin-left: auto; display: flex; gap: 10px;">
                    <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-undo" title="撤销">
                        <i class="layui-icon layui-icon-return"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-redo" title="重做">
                        <i class="layui-icon layui-icon-ok"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-fit" title="适应画布">
                        <i class="layui-icon layui-icon-screen-full"></i>
                    </button>
                    <button class="layui-btn layui-btn-sm layui-btn-primary" id="btn-center" title="居中显示">
                        <i class="layui-icon layui-icon-location"></i>
                    </button>
                </div>
            </div>

            <!-- 画布内容 -->
            <div class="kg-canvas-content">
                <div id="knowledge-graph-container"></div>
                
                <!-- 缩放控制 -->
                <div class="kg-zoom-controls">
                    <div class="kg-zoom-btn" id="btn-zoom-in" title="放大">
                        <i class="layui-icon layui-icon-addition"></i>
                    </div>
                    <div class="kg-zoom-btn" id="btn-zoom-out" title="缩小">
                        <i class="layui-icon layui-icon-subtraction"></i>
                    </div>
                </div>

                <!-- 小地图 -->
                <div class="kg-minimap" id="kg-minimap" style="display: none;">
                    <div class="kg-minimap-header">
                        <span>缩略图</span>
                        <span class="kg-minimap-close" id="btn-close-minimap">
                            <i class="layui-icon layui-icon-close"></i>
                        </span>
                    </div>
                    <div class="kg-minimap-canvas" id="minimap-canvas"></div>
                </div>

                <!-- 加载指示器 -->
                <div class="kg-loading" id="kg-loading">
                    <div class="kg-loading-spinner"></div>
                    <div>正在加载知识图谱...</div>
                </div>
            </div>
        </div>

        <!-- 右侧属性面板 -->
        <div class="kg-property-panel">
            <div class="layui-card">
                <div class="layui-card-header">
                    <i class="layui-icon layui-icon-set"></i> 属性面板
                </div>
                <div class="layui-card-body" style="padding: 0;">
                    <div class="kg-property-content" id="property-content">
                        <div class="kg-property-empty">
                            <i class="layui-icon layui-icon-circle"></i>
                            <div>选择节点或关系查看属性</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 状态栏 -->
    <div class="kg-status-bar">
        <div class="kg-status-left">
            <div class="kg-status-item">
                <div class="kg-status-indicator" id="save-status"></div>
                <span id="save-text">已保存</span>
            </div>
            <div class="kg-status-item">
                <span>缩放: </span>
                <span id="zoom-level">100%</span>
            </div>
        </div>
        <div class="kg-status-right">
            <div class="kg-status-item">
                <span>选中: </span>
                <span id="selection-count">0</span>
            </div>
            <div class="kg-status-item">
                <span>{{ course.title }}</span>
            </div>
        </div>
    </div>

    <!-- 隐藏表单数据 -->
    <input type="hidden" id="course-id" value="{{ course.id }}">
    <input type="hidden" id="api-base" value="/admin/knowledge-graph">

    <!-- JavaScript依赖 -->
    {{ js_include('lib/jquery.min.js') }}
    {{ js_include('lib/layui/layui.js') }}
    {{ js_include('admin/js/knowledge-graph.js') }}

    <script>
    layui.use(['knowledgeGraph', 'layer', 'form'], function() {
        var knowledgeGraph = layui.knowledgeGraph;
        var layer = layui.layer;
        var form = layui.form;
        
        // 获取配置参数
        var courseId = parseInt($('#course-id').val());
        var apiBase = $('#api-base').val();
        
        // 初始化知识图谱编辑器
        var graphEditor = knowledgeGraph.createEditor({
            container: '#knowledge-graph-container',
            courseId: courseId,
            apiBase: apiBase,
            readOnly: false,
            showMinimap: true,
            enableContextMenu: true,
            autoSave: true,
            autoSaveInterval: 30000
        });
        
        // 绑定工具栏事件
        $('#btn-use-template').on('click', function() {
            showTemplateSelector(courseId, graphEditor);
        });
        
        $('#btn-save-graph').on('click', function() {
            graphEditor.saveGraph();
        });
        
        // 保存为模板
        $('#btn-save-as-template').on('click', function() {
            showSaveAsTemplateDialog(courseId, graphEditor);
        });
        
        $('#btn-export-graph').on('click', function() {
            layer.open({
                type: 1,
                title: '导出图谱',
                area: ['400px', '300px'],
                content: [
                    '<div style="padding: 20px;">',
                    '<div class="layui-form">',
                    '<div class="layui-form-item">',
                    '<label class="layui-form-label">导出格式</label>',
                    '<div class="layui-input-block">',
                    '<input type="radio" name="format" value="json" title="JSON格式" checked>',
                    '<input type="radio" name="format" value="cytoscape" title="Cytoscape.js格式">',
                    '<input type="radio" name="format" value="graphml" title="GraphML格式">',
                    '</div>',
                    '</div>',
                    '</div>',
                    '</div>'
                ].join(''),
                btn: ['导出', '取消'],
                yes: function(index, layero) {
                    var format = layero.find('input[name="format"]:checked').val();
                    window.open('/admin/knowledge-graph/export/' + courseId + '/' + format);
                    layer.close(index);
                }
            });
            form.render();
        });
        
        $('#btn-import-graph').on('click', function() {
            layer.open({
                type: 1,
                title: '导入图谱',
                area: ['500px', '400px'],
                content: [
                    '<div style="padding: 20px;">',
                    '<div class="layui-form">',
                    '<div class="layui-form-item layui-form-text">',
                    '<label class="layui-form-label">图谱数据</label>',
                    '<div class="layui-input-block">',
                    '<textarea id="import-data" placeholder="请粘贴JSON格式的图谱数据..." class="layui-textarea" rows="10"></textarea>',
                    '</div>',
                    '</div>',
                    '<div class="layui-form-item">',
                    '<div class="layui-input-block">',
                    '<div class="layui-text">支持JSON和Cytoscape.js格式的图谱数据</div>',
                    '</div>',
                    '</div>',
                    '</div>',
                    '</div>'
                ].join(''),
                btn: ['导入', '取消'],
                yes: function(index, layero) {
                    var data = layero.find('#import-data').val();
                    if (!data) {
                        layer.msg('请输入图谱数据', {icon: 2});
                        return;
                    }
                    
                    try {
                        var jsonData = JSON.parse(data);
                        
                        $.ajax({
                            url: '/api/knowledge-graph/import/' + courseId,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(jsonData),
                            success: function(response) {
                                if (response.code === 0) {
                                    layer.close(index);
                                    layer.msg('导入成功', {icon: 1});
                                    location.reload();
                                } else {
                                    layer.msg(response.message || '导入失败', {icon: 2});
                                }
                            },
                            error: function(xhr) {
                                layer.msg('网络错误：' + xhr.status, {icon: 2});
                            }
                        });
                    } catch (e) {
                        layer.msg('数据格式错误：' + e.message, {icon: 2});
                    }
                }
            });
        });
        
        // 布局算法按钮
        $('#btn-layout-dagre').on('click', function() {
            graphEditor.applyLayout('dagre');
            $('.kg-btn-group .layui-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        $('#btn-layout-cose').on('click', function() {
            graphEditor.applyLayout('cose');
            $('.kg-btn-group .layui-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        $('#btn-layout-circle').on('click', function() {
            graphEditor.applyLayout('circle');
            $('.kg-btn-group .layui-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        $('#btn-layout-grid').on('click', function() {
            graphEditor.applyLayout('grid');
            $('.kg-btn-group .layui-btn').removeClass('active');
            $(this).addClass('active');
        });
        
        // 缩放控制
        $('#btn-zoom-in').on('click', function() {
            if (graphEditor.cy) {
                var zoom = graphEditor.cy.zoom();
                graphEditor.cy.zoom(zoom * 1.2);
                updateZoomLevel();
            }
        });
        
        $('#btn-zoom-out').on('click', function() {
            if (graphEditor.cy) {
                var zoom = graphEditor.cy.zoom();
                graphEditor.cy.zoom(zoom * 0.8);
                updateZoomLevel();
            }
        });
        
        $('#btn-fit').on('click', function() {
            if (graphEditor.cy) {
                graphEditor.cy.fit();
                updateZoomLevel();
            }
        });
        
        $('#btn-center').on('click', function() {
            if (graphEditor.cy) {
                graphEditor.cy.center();
            }
        });
        
        // 小地图控制
        $('#btn-close-minimap').on('click', function() {
            $('#kg-minimap').hide();
        });
        
        // 更新缩放级别显示
        function updateZoomLevel() {
            if (graphEditor.cy) {
                var zoom = Math.round(graphEditor.cy.zoom() * 100);
                $('#zoom-level').text(zoom + '%');
            }
        }
        
        // 监听属性面板更新事件
        $(document).on('kg.property.update', function(e, type, data) {
            var content = '';
            
            if (type === 'node') {
                content = buildNodePropertyForm(data);
            } else if (type === 'edge') {
                content = buildEdgePropertyForm(data);
            } else {
                content = '<div class="kg-property-empty"><i class="layui-icon layui-icon-circle"></i><div>选择节点或关系查看属性</div></div>';
            }
            
            $('#property-content').html(content);
            form.render();
        });
        
        // 构建节点属性表单
        function buildNodePropertyForm(data) {
            return [
                '<form class="layui-form kg-property-form" style="padding: 15px;">',
                '<div class="layui-form-item">',
                '<label class="layui-form-label">名称</label>',
                '<div class="layui-input-block">',
                '<input type="text" value="' + (data.label || '') + '" class="layui-input" readonly>',
                '</div>',
                '</div>',
                '<div class="layui-form-item">',
                '<label class="layui-form-label">类型</label>',
                '<div class="layui-input-block">',
                '<input type="text" value="' + (data.type || '') + '" class="layui-input" readonly>',
                '</div>',
                '</div>',
                '<div class="layui-form-item layui-form-text">',
                '<label class="layui-form-label">描述</label>',
                '<div class="layui-input-block">',
                '<textarea class="layui-textarea" readonly>' + (data.description || '') + '</textarea>',
                '</div>',
                '</div>',
                '<div class="layui-form-item">',
                '<div class="layui-input-block">',
                '<button type="button" class="layui-btn layui-btn-sm" onclick="editNodeProperty(' + data.id + ')">编辑属性</button>',
                '</div>',
                '</div>',
                '</form>'
            ].join('');
        }
        
        // 构建边属性表单
        function buildEdgePropertyForm(data) {
            return [
                '<form class="layui-form kg-property-form" style="padding: 15px;">',
                '<div class="layui-form-item">',
                '<label class="layui-form-label">关系类型</label>',
                '<div class="layui-input-block">',
                '<input type="text" value="' + (data.type || '') + '" class="layui-input" readonly>',
                '</div>',
                '</div>',
                '<div class="layui-form-item">',
                '<label class="layui-form-label">权重</label>',
                '<div class="layui-input-block">',
                '<input type="text" value="' + (data.weight || 1) + '" class="layui-input" readonly>',
                '</div>',
                '</div>',
                '<div class="layui-form-item layui-form-text">',
                '<label class="layui-form-label">描述</label>',
                '<div class="layui-input-block">',
                '<textarea class="layui-textarea" readonly>' + (data.description || '') + '</textarea>',
                '</div>',
                '</div>',
                '<div class="layui-form-item">',
                '<div class="layui-input-block">',
                '<button type="button" class="layui-btn layui-btn-sm" onclick="editEdgeProperty(' + data.id + ')">编辑关系</button>',
                '</div>',
                '</div>',
                '</form>'
            ].join('');
        }
        
        // 编辑节点属性
        window.editNodeProperty = function(nodeId) {
            layer.msg('节点编辑功能');
        };
        
        // 编辑边属性
        window.editEdgeProperty = function(edgeId) {
            layer.msg('关系编辑功能');
        };
        
        // 页面卸载时清理
        $(window).on('beforeunload', function() {
            if (graphEditor) {
                graphEditor.destroy();
            }
        });
        
        // 隐藏加载指示器
        setTimeout(function() {
            $('#kg-loading').fadeOut();
        }, 1000);
        
        // 显示模板选择器
        function showTemplateSelector(courseId, graphEditor) {
            // 获取模板列表
            $.get('{{ url({"for":"admin.knowledge_graph.templates"}) }}?format=json', function(res) {
                // 如果没有format参数支持，则直接打开模板列表页
                layer.open({
                    type: 2,
                    title: '选择知识图谱模板',
                    area: ['90%', '90%'],
                    content: '{{ url({"for":"admin.knowledge_graph.templates"}) }}',
                    btn: ['取消'],
                    yes: function(index) {
                        layer.close(index);
                    }
                });
            }).fail(function() {
                // 如果AJAX失败，显示简化的模板选择对话框
                showSimpleTemplateSelector(courseId, graphEditor);
            });
        }
        
        // 简化的模板选择器
        function showSimpleTemplateSelector(courseId, graphEditor) {
            var content = '<div style="padding: 20px;">';
            content += '<div class="layui-form">';
            content += '<div class="layui-form-item">';
            content += '<label class="layui-form-label">选择模板</label>';
            content += '<div class="layui-input-block">';
            content += '<select id="template-select" lay-filter="template-select">';
            content += '<option value="">请选择模板</option>';
            content += '</select>';
            content += '</div>';
            content += '</div>';
            content += '<div id="template-preview" style="margin-top: 20px; display: none;"></div>';
            content += '</div>';
            content += '</div>';
            
            layer.open({
                type: 1,
                title: '使用知识图谱模板',
                area: ['600px', '500px'],
                content: content,
                btn: ['应用模板', '取消'],
                success: function(layero, index) {
                    // 加载模板列表
                    loadTemplateList();
                    
                    // 监听模板选择
                    layui.form.on('select(template-select)', function(data) {
                        if (data.value) {
                            previewTemplate(data.value);
                        }
                    });
                },
                yes: function(index) {
                    var templateId = $('#template-select').val();
                    if (!templateId) {
                        layer.msg('请选择模板', {icon: 2});
                        return;
                    }
                    
                    // 应用模板
                    applyTemplate(courseId, templateId, graphEditor, index);
                }
            });
        }
        
        // 加载模板列表
        function loadTemplateList() {
            // 这里简化处理，实际应该通过API获取
            var templates = [
                {id: 1, name: '计算机科学基础', nodes: 15, relations: 18},
                {id: 2, name: '数学基础', nodes: 12, relations: 17},
                {id: 3, name: 'Web全栈开发技术栈', nodes: 20, relations: 32}
            ];
            
            var select = $('#template-select');
            templates.forEach(function(tpl) {
                select.append('<option value="' + tpl.id + '">' + tpl.name + ' (' + tpl.nodes + '节点, ' + tpl.relations + '关系)</option>');
            });
            
            layui.form.render('select');
        }
        
        // 预览模板
        function previewTemplate(templateId) {
            var url = '{{ url({"for":"admin.knowledge_graph.template_detail"}) }}'.replace(/\/0$/, '/' + templateId);
            
            $.get(url, function(res) {
                if (res.code === 0) {
                    var data = res.data;
                    var preview = '<div class="layui-card">';
                    preview += '<div class="layui-card-header">' + data.name + '</div>';
                    preview += '<div class="layui-card-body">';
                    preview += '<p>' + (data.description || '暂无描述') + '</p>';
                    preview += '<p><strong>节点数量：</strong>' + data.node_count + ' 个</p>';
                    preview += '<p><strong>关系数量：</strong>' + data.relation_count + ' 个</p>';
                    if (data.tags_array && data.tags_array.length > 0) {
                        preview += '<p><strong>标签：</strong>' + data.tags_array.join(', ') + '</p>';
                    }
                    preview += '</div>';
                    preview += '</div>';
                    
                    $('#template-preview').html(preview).show();
                }
            });
        }
        
        // 应用模板到课程
        function applyTemplate(courseId, templateId, graphEditor, layerIndex) {
            var applyUrl = '{{ url({"for":"admin.knowledge_graph.apply_template","courseId":0}) }}'.replace(/\/0$/, '/' + courseId);
            
            layer.confirm('应用模板将创建新的节点和关系，确定要继续吗？', {
                icon: 3,
                title: '确认操作'
            }, function(confirmIndex) {
                var loadingIndex = layer.load(2, {shade: [0.3, '#000']});
                
                $.post(applyUrl, {
                    template_id: templateId
                }, function(res) {
                    layer.close(loadingIndex);
                    
                    if (res.code === 0) {
                        layer.msg('应用模板成功！', {icon: 1});
                        layer.close(layerIndex);
                        layer.close(confirmIndex);
                        
                        // 刷新图谱
                        if (graphEditor && graphEditor.loadGraph) {
                            setTimeout(function() {
                                graphEditor.loadGraph();
                            }, 500);
                        } else {
                            location.reload();
                        }
                    } else {
                        layer.msg(res.msg || '应用模板失败', {icon: 2});
                    }
                }).fail(function() {
                    layer.close(loadingIndex);
                    layer.msg('网络请求失败', {icon: 2});
                });
            });
        }
        
        // 显示保存为模板对话框
        function showSaveAsTemplateDialog(courseId, graphEditor) {
            // 检查是否有节点
            var nodes = graphEditor.cy.nodes();
            if (nodes.length === 0) {
                layer.msg('图谱中没有节点，无法保存为模板', {icon: 2});
                return;
            }
            
            // 构建表单HTML
            var formHtml = '<form class="layui-form" lay-filter="template-form" style="padding: 20px;">';
            formHtml += '<div class="layui-form-item">';
            formHtml += '<label class="layui-form-label">模板名称<span style="color:red;">*</span></label>';
            formHtml += '<div class="layui-input-block">';
            formHtml += '<input type="text" name="name" lay-verify="required" placeholder="请输入模板名称" class="layui-input">';
            formHtml += '</div>';
            formHtml += '</div>';
            
            formHtml += '<div class="layui-form-item">';
            formHtml += '<label class="layui-form-label">模板分类<span style="color:red;">*</span></label>';
            formHtml += '<div class="layui-input-block">';
            formHtml += '<select name="category" lay-verify="required">';
            formHtml += '<option value="">请选择分类</option>';
            formHtml += '<option value="cs">计算机科学</option>';
            formHtml += '<option value="math">数学</option>';
            formHtml += '<option value="language">语言</option>';
            formHtml += '<option value="business">商业</option>';
            formHtml += '<option value="other">其他</option>';
            formHtml += '</select>';
            formHtml += '</div>';
            formHtml += '</div>';
            
            formHtml += '<div class="layui-form-item">';
            formHtml += '<label class="layui-form-label">难度级别</label>';
            formHtml += '<div class="layui-input-block">';
            formHtml += '<select name="difficulty_level">';
            formHtml += '<option value="beginner">初级</option>';
            formHtml += '<option value="intermediate">中级</option>';
            formHtml += '<option value="advanced">高级</option>';
            formHtml += '</select>';
            formHtml += '</div>';
            formHtml += '</div>';
            
            formHtml += '<div class="layui-form-item layui-form-text">';
            formHtml += '<label class="layui-form-label">模板描述</label>';
            formHtml += '<div class="layui-input-block">';
            formHtml += '<textarea name="description" placeholder="请输入模板描述" class="layui-textarea"></textarea>';
            formHtml += '</div>';
            formHtml += '</div>';
            
            formHtml += '<div class="layui-form-item">';
            formHtml += '<label class="layui-form-label">标签</label>';
            formHtml += '<div class="layui-input-block">';
            formHtml += '<input type="text" name="tags" placeholder="多个标签用逗号分隔" class="layui-input">';
            formHtml += '</div>';
            formHtml += '</div>';
            
            formHtml += '<div class="layui-form-item" style="background: #f5f5f5; padding: 10px; border-radius: 4px;">';
            formHtml += '<p><i class="layui-icon layui-icon-tips"></i> 图谱信息：</p>';
            formHtml += '<p>节点数量：' + nodes.length + ' 个</p>';
            formHtml += '<p>关系数量：' + graphEditor.cy.edges().length + ' 个</p>';
            formHtml += '</div>';
            
            formHtml += '</form>';
            
            // 打开对话框
            layer.open({
                type: 1,
                title: '保存为模板',
                area: ['500px', '600px'],
                content: formHtml,
                btn: ['保存', '取消'],
                success: function(layero, index) {
                    // 渲染表单
                    form.render('select');
                },
                yes: function(index, layero) {
                    // 获取表单数据
                    var formData = form.val('template-form');
                    
                    // 验证必填字段
                    if (!formData.name) {
                        layer.msg('请输入模板名称', {icon: 2});
                        return;
                    }
                    if (!formData.category) {
                        layer.msg('请选择模板分类', {icon: 2});
                        return;
                    }
                    
                    // 收集节点数据
                    var nodesData = [];
                    graphEditor.cy.nodes().forEach(function(node) {
                        var data = node.data();
                        var pos = node.position();
                        nodesData.push({
                            id: data.id,
                            name: data.label || data.name || '',
                            type: data.type || 'concept',
                            description: data.description || '',
                            position_x: Math.round(pos.x),
                            position_y: Math.round(pos.y),
                            weight: data.weight || 1.0,
                            properties: data.properties || {},
                            style_config: data.style_config || {},
                            sort_order: data.sort_order || 0
                        });
                    });
                    
                    // 收集关系数据
                    var relationsData = [];
                    graphEditor.cy.edges().forEach(function(edge) {
                        var data = edge.data();
                        relationsData.push({
                            from_node_id: data.source,
                            to_node_id: data.target,
                            relation_type: data.type || 'related',
                            weight: data.weight || 1.0,
                            description: data.description || '',
                            properties: data.properties || {},
                            style_config: data.style_config || {}
                        });
                    });
                    
                    // 准备提交数据
                    var postData = {
                        name: formData.name,
                        category: formData.category,
                        description: formData.description || '',
                        difficulty_level: formData.difficulty_level || 'beginner',
                        tags: formData.tags || '',
                        nodes: JSON.stringify(nodesData),
                        relations: JSON.stringify(relationsData)
                    };
                    
                    // 提交保存
                    var loadingIndex = layer.load(2, {shade: [0.3, '#000']});
                    var createUrl = '{{ url({"for":"admin.knowledge_graph.template_create"}) }}';
                    
                    $.post(createUrl, postData, function(res) {
                        layer.close(loadingIndex);
                        
                        if (res.code === 0) {
                            layer.msg('模板保存成功！', {icon: 1, time: 2000});
                            layer.close(index);
                        } else {
                            layer.msg(res.msg || '保存失败', {icon: 2});
                        }
                    }).fail(function() {
                        layer.close(loadingIndex);
                        layer.msg('网络请求失败', {icon: 2});
                    });
                }
            });
        }
    });
    </script>
</body>
</html>

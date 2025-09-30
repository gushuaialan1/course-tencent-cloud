/**
 * 知识图谱前端控制器
 * 基于Cytoscape.js实现的知识图谱可视化编辑器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

layui.define(['jquery', 'layer', 'form'], function(exports) {
    var $ = layui.jquery;
    var layer = layui.layer;
    var form = layui.form;

    /**
     * 知识图谱编辑器类
     */
    var KnowledgeGraphEditor = function(options) {
        this.options = $.extend({
            container: '#knowledge-graph-container',
            courseId: 0,
            apiBase: '/api/knowledge-graph',
            readOnly: false,
            showMinimap: true,
            enableContextMenu: true,
            autoSave: true,
            autoSaveInterval: 30000 // 30秒自动保存
        }, options);

        this.cy = null;
        this.graphData = null;
        this.selectedNodes = [];
        this.selectedEdges = [];
        this.isModified = false;
        this.autoSaveTimer = null;

        this.init();
    };

    KnowledgeGraphEditor.prototype = {
        /**
         * 初始化编辑器
         */
        init: function() {
            var self = this;
            
            // 检查Cytoscape.js是否加载
            if (typeof cytoscape === 'undefined') {
                layer.msg('Cytoscape.js未加载，请检查CDN链接', {icon: 2});
                return;
            }

            // 初始化图谱容器
            this.initCytoscape();
            
            // 绑定事件
            this.bindEvents();
            
            // 加载图谱数据
            this.loadGraphData();
            
            // 启动自动保存
            if (this.options.autoSave && !this.options.readOnly) {
                this.startAutoSave();
            }
        },

        /**
         * 初始化Cytoscape实例
         */
        initCytoscape: function() {
            var self = this;
            
            this.cy = cytoscape({
                container: document.querySelector(this.options.container),
                
                style: [
                    // 节点样式
                    {
                        selector: 'node',
                        style: {
                            'background-color': 'data(backgroundColor)',
                            'border-color': 'data(borderColor)',
                            'border-width': 'data(borderWidth)',
                            'color': 'data(textColor)',
                            'label': 'data(label)',
                            'text-valign': 'center',
                            'text-halign': 'center',
                            'font-size': 'data(fontSize)',
                            'font-weight': '500',
                            'width': 'data(width)',
                            'height': 'data(height)',
                            'shape': 'data(shape)',
                            'text-wrap': 'wrap',
                            'text-max-width': 80,
                            'overlay-opacity': 0,
                            'z-index': 10
                        }
                    },
                    // 选中节点样式
                    {
                        selector: 'node:selected',
                        style: {
                            'border-width': 4,
                            'border-color': '#009688',
                            'background-color': '#E0F2F1'
                        }
                    },
                    // 悬停节点样式
                    {
                        selector: 'node:active',
                        style: {
                            'overlay-opacity': 0.2,
                            'overlay-color': '#009688'
                        }
                    },
                    // 边样式
                    {
                        selector: 'edge',
                        style: {
                            'width': 'data(width)',
                            'line-color': 'data(lineColor)',
                            'target-arrow-color': 'data(arrowColor)',
                            'target-arrow-shape': 'data(arrowShape)',
                            'curve-style': 'data(curveStyle)',
                            'line-style': 'data(lineStyle)',
                            'label': 'data(label)',
                            'text-rotation': 'autorotate',
                            'font-size': '10px',
                            'text-margin-y': -10,
                            'source-endpoint': 'outside-to-node',
                            'target-endpoint': 'outside-to-node',
                            'z-index': 5
                        }
                    },
                    // 选中边样式
                    {
                        selector: 'edge:selected',
                        style: {
                            'line-color': '#009688',
                            'target-arrow-color': '#009688',
                            'width': 4
                        }
                    },
                    // 悬停边样式
                    {
                        selector: 'edge:active',
                        style: {
                            'overlay-opacity': 0.2,
                            'overlay-color': '#009688'
                        }
                    }
                ],

                layout: {
                    name: 'preset',
                    animate: true,
                    animationDuration: 500
                },

                // 交互配置
                userZoomingEnabled: true,
                userPanningEnabled: true,
                boxSelectionEnabled: true,
                selectionType: 'additive',
                
                // 性能配置
                pixelRatio: 'auto',
                motionBlur: true,
                wheelSensitivity: 0.5,
                
                minZoom: 0.3,
                maxZoom: 3
            });

            // 添加扩展插件支持
            this.setupCytoscapeExtensions();
        },

        /**
         * 设置Cytoscape扩展插件
         */
        setupCytoscapeExtensions: function() {
            // 如果有其他扩展插件，在这里初始化
            // 例如：dagre布局、cxtmenu右键菜单等
        },

        /**
         * 绑定事件处理器
         */
        bindEvents: function() {
            var self = this;

            // 节点选择事件
            this.cy.on('select', 'node', function(evt) {
                var node = evt.target;
                self.onNodeSelected(node);
            });

            // 节点取消选择事件
            this.cy.on('unselect', 'node', function(evt) {
                var node = evt.target;
                self.onNodeUnselected(node);
            });

            // 边选择事件
            this.cy.on('select', 'edge', function(evt) {
                var edge = evt.target;
                self.onEdgeSelected(edge);
            });

            // 节点双击事件
            this.cy.on('dblclick', 'node', function(evt) {
                if (!self.options.readOnly) {
                    self.editNode(evt.target);
                }
            });

            // 空白区域点击事件
            this.cy.on('click', function(evt) {
                if (evt.target === self.cy) {
                    self.clearSelection();
                }
            });

            // 节点拖拽事件
            this.cy.on('drag', 'node', function(evt) {
                if (!self.options.readOnly) {
                    self.markAsModified();
                }
            });

            // 右键菜单事件
            if (this.options.enableContextMenu && !this.options.readOnly) {
                this.setupContextMenu();
            }

            // 键盘事件
            this.bindKeyboardEvents();
        },

        /**
         * 设置右键菜单
         */
        setupContextMenu: function() {
            var self = this;

            // 节点右键菜单
            this.cy.on('cxttap', 'node', function(evt) {
                evt.stopPropagation();
                self.showNodeContextMenu(evt.target, evt.originalEvent);
            });

            // 边右键菜单
            this.cy.on('cxttap', 'edge', function(evt) {
                evt.stopPropagation();
                self.showEdgeContextMenu(evt.target, evt.originalEvent);
            });

            // 空白区域右键菜单
            this.cy.on('cxttap', function(evt) {
                if (evt.target === self.cy) {
                    self.showCanvasContextMenu(evt.originalEvent);
                }
            });
        },

        /**
         * 绑定键盘事件
         */
        bindKeyboardEvents: function() {
            var self = this;

            $(document).on('keydown.knowledge-graph', function(e) {
                if (!self.options.readOnly) {
                    // Delete键删除选中元素
                    if (e.keyCode === 46) {
                        self.deleteSelected();
                        e.preventDefault();
                    }
                    // Ctrl+S保存
                    if (e.ctrlKey && e.keyCode === 83) {
                        self.saveGraph();
                        e.preventDefault();
                    }
                    // Ctrl+Z撤销
                    if (e.ctrlKey && e.keyCode === 90) {
                        self.undo();
                        e.preventDefault();
                    }
                }
            });
        },

        /**
         * 加载图谱数据
         */
        loadGraphData: function() {
            var self = this;
            
            if (!this.options.courseId) {
                layer.msg('缺少课程ID', {icon: 2});
                return;
            }

            layer.load(1);
            
            $.get(this.options.apiBase + '/course/' + this.options.courseId)
                .done(function(response) {
                    if (response.code === 0) {
                        self.graphData = response.data.graph;
                        self.renderGraph(self.graphData);
                        layer.closeAll('loading');
                    } else {
                        layer.msg(response.message || '加载图谱数据失败', {icon: 2});
                        layer.closeAll('loading');
                    }
                })
                .fail(function(xhr) {
                    layer.msg('网络错误：' + xhr.status, {icon: 2});
                    layer.closeAll('loading');
                });
        },

        /**
         * 渲染图谱
         */
        renderGraph: function(graphData) {
            if (!graphData || !graphData.elements) {
                console.warn('图谱数据为空');
                return;
            }

            // 清空现有元素
            this.cy.elements().remove();

            // 转换数据格式
            var elements = this.convertDataFormat(graphData.elements);

            // 添加元素到图谱
            this.cy.add(elements);

            // 自动布局（如果节点没有位置信息）
            this.autoLayout();

            // 适应画布
            this.cy.fit();

            console.log('图谱渲染完成，节点数：', this.cy.nodes().length, '边数：', this.cy.edges().length);
        },

        /**
         * 转换数据格式
         */
        convertDataFormat: function(elements) {
            var converted = [];

            elements.forEach(function(element) {
                if (element.group === 'nodes') {
                    // 转换节点数据
                    var nodeData = {
                        group: 'nodes',
                        data: {
                            id: element.data.id,
                            label: element.data.label,
                            type: element.data.type,
                            description: element.data.description || '',
                            // 样式数据
                            backgroundColor: element.style['background-color'] || '#009688',
                            borderColor: element.style['border-color'] || '#00695C',
                            borderWidth: element.style['border-width'] || 2,
                            textColor: element.style.color || '#fff',
                            fontSize: element.style['font-size'] || '12px',
                            width: element.style.width || 80,
                            height: element.style.height || 40,
                            shape: element.style.shape || 'ellipse'
                        },
                        position: element.position || { x: 0, y: 0 }
                    };
                    converted.push(nodeData);
                } else if (element.group === 'edges') {
                    // 转换边数据
                    var edgeData = {
                        group: 'edges',
                        data: {
                            id: element.data.id,
                            source: element.data.source,
                            target: element.data.target,
                            type: element.data.type,
                            label: element.style.label || '',
                            // 样式数据
                            lineColor: element.style['line-color'] || '#666',
                            arrowColor: element.style['target-arrow-color'] || '#666',
                            arrowShape: element.style['target-arrow-shape'] || 'triangle',
                            curveStyle: element.style['curve-style'] || 'bezier',
                            lineStyle: element.style['line-style'] || 'solid',
                            width: element.style.width || 2
                        }
                    };
                    converted.push(edgeData);
                }
            });

            return converted;
        },

        /**
         * 自动布局
         */
        autoLayout: function() {
            // 检查是否有位置信息
            var hasPositions = this.cy.nodes().some(function(node) {
                var pos = node.position();
                return pos.x !== 0 || pos.y !== 0;
            });

            if (!hasPositions) {
                // 使用层次布局
                this.applyLayout('dagre');
            }
        },

        /**
         * 应用布局算法
         */
        applyLayout: function(layoutName, options) {
            var layoutOptions = $.extend({
                name: layoutName,
                animate: true,
                animationDuration: 1000,
                fit: true,
                padding: 30
            }, options);

            // 根据布局类型设置特定参数
            switch (layoutName) {
                case 'dagre':
                    $.extend(layoutOptions, {
                        rankDir: 'TB',
                        nodeSep: 50,
                        edgeSep: 10,
                        rankSep: 50
                    });
                    break;
                case 'cose':
                    $.extend(layoutOptions, {
                        nodeRepulsion: 400000,
                        nodeOverlap: 10,
                        idealEdgeLength: 100,
                        edgeElasticity: 100,
                        nestingFactor: 5,
                        gravity: 80,
                        numIter: 1000,
                        initialTemp: 200,
                        coolingFactor: 0.95,
                        minTemp: 1.0
                    });
                    break;
                case 'circle':
                    $.extend(layoutOptions, {
                        radius: 200
                    });
                    break;
                case 'grid':
                    $.extend(layoutOptions, {
                        rows: Math.ceil(Math.sqrt(this.cy.nodes().length)),
                        cols: Math.ceil(Math.sqrt(this.cy.nodes().length))
                    });
                    break;
            }

            var layout = this.cy.layout(layoutOptions);
            layout.run();

            // 标记为已修改
            this.markAsModified();
        },

        /**
         * 节点选择事件处理
         */
        onNodeSelected: function(node) {
            this.selectedNodes.push(node);
            this.updatePropertyPanel('node', node.data());
            console.log('选中节点:', node.data('label'));
        },

        /**
         * 节点取消选择事件处理
         */
        onNodeUnselected: function(node) {
            var index = this.selectedNodes.indexOf(node);
            if (index > -1) {
                this.selectedNodes.splice(index, 1);
            }
        },

        /**
         * 边选择事件处理
         */
        onEdgeSelected: function(edge) {
            this.selectedEdges.push(edge);
            this.updatePropertyPanel('edge', edge.data());
            console.log('选中关系:', edge.data('type'));
        },

        /**
         * 清空选择
         */
        clearSelection: function() {
            this.cy.elements().unselect();
            this.selectedNodes = [];
            this.selectedEdges = [];
            this.updatePropertyPanel(null);
        },

        /**
         * 更新属性面板
         */
        updatePropertyPanel: function(type, data) {
            // 触发自定义事件，由外部处理属性面板更新
            $(document).trigger('kg.property.update', [type, data]);
        },

        /**
         * 显示节点右键菜单
         */
        showNodeContextMenu: function(node, event) {
            var self = this;
            var menuItems = [
                {text: '编辑节点', icon: 'layui-icon-edit', action: function() { self.editNode(node); }},
                {text: '删除节点', icon: 'layui-icon-delete', action: function() { self.deleteNode(node); }},
                {text: '复制节点', icon: 'layui-icon-file', action: function() { self.copyNode(node); }},
                '---',
                {text: '查看依赖', icon: 'layui-icon-tree', action: function() { self.showDependencies(node); }},
                {text: '学习路径', icon: 'layui-icon-location', action: function() { self.showLearningPath(node); }}
            ];

            this.showContextMenu(menuItems, event);
        },

        /**
         * 显示边右键菜单
         */
        showEdgeContextMenu: function(edge, event) {
            var self = this;
            var menuItems = [
                {text: '编辑关系', icon: 'layui-icon-edit', action: function() { self.editEdge(edge); }},
                {text: '删除关系', icon: 'layui-icon-delete', action: function() { self.deleteEdge(edge); }}
            ];

            this.showContextMenu(menuItems, event);
        },

        /**
         * 显示画布右键菜单
         */
        showCanvasContextMenu: function(event) {
            var self = this;
            var menuItems = [
                {text: '添加概念节点', icon: 'layui-icon-add-circle', action: function() { self.addNode('concept', event); }},
                {text: '添加主题节点', icon: 'layui-icon-template-1', action: function() { self.addNode('topic', event); }},
                {text: '添加技能节点', icon: 'layui-icon-diamond', action: function() { self.addNode('skill', event); }},
                {text: '添加课程节点', icon: 'layui-icon-read', action: function() { self.addNode('course', event); }},
                '---',
                {text: '自动布局', icon: 'layui-icon-spread-left', action: function() { self.showLayoutMenu(); }}
            ];

            this.showContextMenu(menuItems, event);
        },

        /**
         * 显示右键菜单
         */
        showContextMenu: function(items, event) {
            // 简化实现，实际应该创建动态菜单
            console.log('显示右键菜单:', items);
        },

        /**
         * 添加节点
         */
        addNode: function(type, event) {
            var self = this;
            
            // 计算位置
            var containerPos = $(this.options.container).offset();
            var position = {
                x: event.clientX - containerPos.left,
                y: event.clientY - containerPos.top
            };

            // 转换为图谱坐标
            var renderedPos = this.cy.renderedToModelPosition(position);

            // 显示节点编辑对话框
            this.showNodeDialog(type, null, renderedPos);
        },

        /**
         * 编辑节点
         */
        editNode: function(node) {
            this.showNodeDialog(node.data('type'), node, node.position());
        },

        /**
         * 显示节点编辑对话框
         */
        showNodeDialog: function(type, node, position) {
            var self = this;
            var isEdit = !!node;
            var title = isEdit ? '编辑节点' : '创建节点';

            var content = this.buildNodeDialogContent(type, node);

            layer.open({
                type: 1,
                title: title,
                area: ['500px', '400px'],
                content: content,
                btn: ['确定', '取消'],
                yes: function(index, layero) {
                    self.saveNodeFromDialog(layero, type, node, position, index);
                }
            });
        },

        /**
         * 构建节点对话框内容
         */
        buildNodeDialogContent: function(type, node) {
            var data = node ? node.data() : {};
            var typeOptions = {
                'concept': '概念',
                'topic': '主题', 
                'skill': '技能',
                'course': '课程'
            };

            var html = '<form class="layui-form" style="padding: 20px;">';
            html += '<div class="layui-form-item">';
            html += '<label class="layui-form-label">节点名称</label>';
            html += '<div class="layui-input-block">';
            html += '<input type="text" name="name" value="' + (data.label || '') + '" placeholder="请输入节点名称" class="layui-input" lay-verify="required">';
            html += '</div>';
            html += '</div>';

            html += '<div class="layui-form-item">';
            html += '<label class="layui-form-label">节点类型</label>';
            html += '<div class="layui-input-block">';
            html += '<select name="type">';
            Object.keys(typeOptions).forEach(function(key) {
                var selected = key === type ? 'selected' : '';
                html += '<option value="' + key + '" ' + selected + '>' + typeOptions[key] + '</option>';
            });
            html += '</select>';
            html += '</div>';
            html += '</div>';

            html += '<div class="layui-form-item layui-form-text">';
            html += '<label class="layui-form-label">描述</label>';
            html += '<div class="layui-input-block">';
            html += '<textarea name="description" placeholder="请输入节点描述" class="layui-textarea">' + (data.description || '') + '</textarea>';
            html += '</div>';
            html += '</div>';

            html += '</form>';

            return html;
        },

        /**
         * 从对话框保存节点
         */
        saveNodeFromDialog: function(layero, type, node, position, layerIndex) {
            var self = this;
            var formData = {};
            
            layero.find('input, select, textarea').each(function() {
                formData[this.name] = $(this).val();
            });

            if (!formData.name) {
                layer.msg('请输入节点名称', {icon: 2});
                return;
            }

            var nodeData = {
                name: formData.name,
                type: formData.type,
                description: formData.description,
                course_id: this.options.courseId,
                position_x: position.x,
                position_y: position.y
            };

            var apiUrl = this.options.apiBase + '/nodes';
            var method = node ? 'PUT' : 'POST';
            
            if (node) {
                apiUrl += '/' + node.id();
            }

            $.ajax({
                url: apiUrl,
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(nodeData),
                success: function(response) {
                    if (response.code === 0) {
                        layer.close(layerIndex);
                        
                        if (node) {
                            // 更新现有节点
                            node.data('label', nodeData.name);
                            node.data('description', nodeData.description);
                        } else {
                            // 添加新节点
                            self.addNodeToGraph(response.data.node, position);
                        }
                        
                        self.markAsModified();
                        layer.msg('保存成功', {icon: 1});
                    } else {
                        layer.msg(response.message || '保存失败', {icon: 2});
                    }
                },
                error: function(xhr) {
                    layer.msg('网络错误：' + xhr.status, {icon: 2});
                }
            });
        },

        /**
         * 添加节点到图谱
         */
        addNodeToGraph: function(nodeData, position) {
            var element = {
                group: 'nodes',
                data: {
                    id: nodeData.id,
                    label: nodeData.name,
                    type: nodeData.type,
                    description: nodeData.description || '',
                    backgroundColor: '#009688',
                    borderColor: '#00695C',
                    borderWidth: 2,
                    textColor: '#fff',
                    fontSize: '12px',
                    width: 80,
                    height: 40,
                    shape: 'ellipse'
                },
                position: position
            };

            this.cy.add(element);
        },

        /**
         * 删除选中元素
         */
        deleteSelected: function() {
            var self = this;
            var selected = this.cy.elements(':selected');
            
            if (selected.length === 0) {
                return;
            }

            layer.confirm('确定要删除选中的 ' + selected.length + ' 个元素吗？', function(index) {
                selected.forEach(function(element) {
                    if (element.isNode()) {
                        self.deleteNode(element);
                    } else if (element.isEdge()) {
                        self.deleteEdge(element);
                    }
                });
                layer.close(index);
            });
        },

        /**
         * 删除节点
         */
        deleteNode: function(node) {
            var self = this;
            var nodeId = node.id();

            $.ajax({
                url: this.options.apiBase + '/nodes/' + nodeId,
                method: 'DELETE',
                success: function(response) {
                    if (response.code === 0) {
                        node.remove();
                        self.markAsModified();
                        layer.msg('删除成功', {icon: 1});
                    } else {
                        layer.msg(response.message || '删除失败', {icon: 2});
                    }
                },
                error: function(xhr) {
                    layer.msg('网络错误：' + xhr.status, {icon: 2});
                }
            });
        },

        /**
         * 删除边
         */
        deleteEdge: function(edge) {
            var self = this;
            var edgeId = edge.id();

            $.ajax({
                url: this.options.apiBase + '/relations/' + edgeId,
                method: 'DELETE',
                success: function(response) {
                    if (response.code === 0) {
                        edge.remove();
                        self.markAsModified();
                        layer.msg('删除成功', {icon: 1});
                    } else {
                        layer.msg(response.message || '删除失败', {icon: 2});
                    }
                },
                error: function(xhr) {
                    layer.msg('网络错误：' + xhr.status, {icon: 2});
                }
            });
        },

        /**
         * 保存图谱
         */
        saveGraph: function() {
            var self = this;
            
            if (!this.isModified) {
                layer.msg('没有需要保存的修改', {icon: 1});
                return;
            }

            // 获取所有节点位置
            var positions = [];
            this.cy.nodes().forEach(function(node) {
                positions.push({
                    id: node.id(),
                    x: node.position('x'),
                    y: node.position('y')
                });
            });

            // 保存位置信息
            $.ajax({
                url: this.options.apiBase + '/nodes/positions',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({positions: positions}),
                success: function(response) {
                    if (response.code === 0) {
                        self.isModified = false;
                        layer.msg('保存成功', {icon: 1});
                    } else {
                        layer.msg(response.message || '保存失败', {icon: 2});
                    }
                },
                error: function(xhr) {
                    layer.msg('网络错误：' + xhr.status, {icon: 2});
                }
            });
        },

        /**
         * 标记为已修改
         */
        markAsModified: function() {
            this.isModified = true;
        },

        /**
         * 开始自动保存
         */
        startAutoSave: function() {
            var self = this;
            this.autoSaveTimer = setInterval(function() {
                if (self.isModified) {
                    self.saveGraph();
                }
            }, this.options.autoSaveInterval);
        },

        /**
         * 停止自动保存
         */
        stopAutoSave: function() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
                this.autoSaveTimer = null;
            }
        },

        /**
         * 销毁编辑器
         */
        destroy: function() {
            this.stopAutoSave();
            $(document).off('keydown.knowledge-graph');
            if (this.cy) {
                this.cy.destroy();
                this.cy = null;
            }
        }
    };

    // 导出模块
    exports('knowledgeGraph', {
        /**
         * 创建知识图谱编辑器
         */
        createEditor: function(options) {
            return new KnowledgeGraphEditor(options);
        },

        /**
         * 版本信息
         */
        version: '1.0.0'
    });
});

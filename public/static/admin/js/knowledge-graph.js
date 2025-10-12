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
            apiBase: '/admin/knowledge-graph',  // 修复：使用后台API路径
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
                    // 关系源节点样式
                    {
                        selector: 'node.relation-source',
                        style: {
                            'border-width': 4,
                            'border-color': '#FF9800',
                            'border-style': 'dashed',
                            'background-color': '#FFF3E0'
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
                            'font-size': '9px',  // 减小字体
                            'font-weight': 'normal',
                            'text-margin-y': -8,
                            'text-background-color': '#fff',  // 白色背景
                            'text-background-opacity': 0.85,  // 半透明背景
                            'text-background-padding': '2px',
                            'text-border-width': 0,
                            'source-endpoint': 'outside-to-node',
                            'target-endpoint': 'outside-to-node',
                            'z-index': 1,  // 降低层级，避免遮挡节点
                            'text-wrap': 'none',
                            'text-max-width': '60px'
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
                    // 如果处于节点模式且已选择节点类型，则添加节点
                    if (self.mode === 'node' && self.currentNodeType) {
                        var position = evt.position;
                        self.addNode(self.currentNodeType, {position: position});
                    } else {
                        self.clearSelection();
                    }
                }
            });
            
            // 节点点击事件 - 用于创建关系
            this.cy.on('click', 'node', function(evt) {
                if (self.mode === 'relation' && self.currentRelationType) {
                    evt.stopPropagation();
                    
                    if (!self.relationSourceNode) {
                        // 选择起始节点
                        self.relationSourceNode = evt.target;
                        evt.target.addClass('relation-source');
                        layer.msg('已选择起始节点，请点击目标节点', {icon: 1, time: 2000});
                    } else {
                        // 选择目标节点，创建关系
                        var targetNode = evt.target;
                        if (self.relationSourceNode.id() !== targetNode.id()) {
                            self.createRelation(self.relationSourceNode, targetNode, self.currentRelationType);
                        }
                        // 清除选择
                        self.relationSourceNode.removeClass('relation-source');
                        self.relationSourceNode = null;
                        self.currentRelationType = null;
                        self.mode = 'select';
                        $('.kg-relation-tool').removeClass('active');
                    }
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
            
            // 节点工具点击事件
            this.bindNodeTools();
            
            // 关系工具点击事件  
            this.bindRelationTools();
            
            // 生成工具点击事件
            this.bindGenerateTools();
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
         * 绑定节点工具点击事件
         */
        bindNodeTools: function() {
            var self = this;
            
            $('.kg-node-tool').on('click', function() {
                var nodeType = $(this).data('node-type');
                
                // 切换选中状态
                $('.kg-node-tool').removeClass('active');
                $(this).addClass('active');
                
                // 设置当前节点类型
                self.currentNodeType = nodeType;
                
                // 切换到节点模式
                self.mode = 'node';
                
                // 显示提示
                layer.msg('请在画布上点击鼠标左键添加' + $(this).find('span').text() + '节点', {icon: 1, time: 2000});
            });
        },

        /**
         * 绑定关系工具点击事件
         */
        bindRelationTools: function() {
            var self = this;
            
            $('.kg-relation-tool').on('click', function() {
                var relationType = $(this).data('relation-type');
                
                // 切换选中状态
                $('.kg-relation-tool').removeClass('active');
                $(this).addClass('active');
                
                // 设置当前关系类型
                self.currentRelationType = relationType;
                
                // 切换到关系模式
                self.mode = 'relation';
                
                // 显示提示
                layer.msg('请依次点击起始节点和目标节点创建' + $(this).find('span').text() + '关系', {icon: 1, time: 3000});
            });
        },

        /**
         * 绑定生成工具点击事件
         */
        bindGenerateTools: function() {
            var self = this;
            
            // 从章节生成按钮
            $('#btn-generate-simple').on('click', function() {
                self.generateFromChapters();
            });
            
            // AI智能生成按钮
            $('#btn-generate-ai').on('click', function() {
                self.generateWithAI();
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
            
            // 修复：使用后台数据API路径 /admin/knowledge-graph/data/{courseId}
            $.get(this.options.apiBase + '/data/' + this.options.courseId)
                .done(function(response) {
                    console.log('加载图谱响应：', response);
                    if (response.code === 0) {
                        // 修复：API返回的数据可能在response.data或直接在response中
                        self.graphData = response.data || response;
                        console.log('图谱数据：', self.graphData);
                        console.log('元素数量：', self.graphData.elements ? self.graphData.elements.length : 0);
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
            console.log('renderGraph 被调用，graphData：', graphData);
            
            if (!graphData || !graphData.elements) {
                console.warn('图谱数据为空或缺少elements字段', graphData);
                layer.msg('图谱数据为空', {icon: 0, time: 2000});
                return;
            }

            console.log('开始渲染，元素数量：', graphData.elements.length);

            // 清空现有元素
            this.cy.elements().remove();

            // 转换数据格式
            var elements = this.convertDataFormat(graphData.elements);
            console.log('转换后的元素数量：', elements.length);

            // 添加元素到图谱
            this.cy.add(elements);

            // 自动布局（如果节点没有位置信息）
            this.autoLayout();

            // 适应画布
            this.cy.fit();

            console.log('图谱渲染完成，节点数：', this.cy.nodes().length, '边数：', this.cy.edges().length);
            layer.msg('图谱加载完成！节点数：' + this.cy.nodes().length, {icon: 1, time: 2000});
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
                            id: 'node_' + element.data.id,  // 修复：添加前缀避免ID冲突
                            dbId: element.data.id,  // 保存原始数据库ID
                            label: element.data.label,
                            name: element.data.name || element.data.label,  // 兼容字段
                            type: element.data.type,
                            description: element.data.description || '',
                            weight: element.data.weight || 1,
                            // 修复：保留资源绑定字段
                            chapter_id: element.data.chapter_id,
                            primary_resource_type: element.data.primary_resource_type,
                            primary_resource_id: element.data.primary_resource_id,
                            properties: element.data.properties || {},
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
                            id: 'edge_' + element.data.id,  // 修复：添加前缀避免ID冲突
                            dbId: element.data.id,  // 保存原始数据库ID
                            source: 'node_' + element.data.source,  // 修复：添加前缀匹配节点ID
                            target: 'node_' + element.data.target,  // 修复：添加前缀匹配节点ID
                            type: element.data.type,
                            label: element.style.label || element.data.label || '',
                            weight: element.data.weight || 1,
                            description: element.data.description || '',
                            properties: element.data.properties || {},
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
            
            // 如果节点有ID（已保存的节点），显示详情和资源
            var nodeId = node.data('id');
            if (nodeId && nodeId.indexOf('node_') !== 0) {
                // 只对已保存的节点（有数字ID）显示详情
                this.showNodeDetail(nodeId);
            }
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
            var renderedPos;
            
            // 支持直接传入位置对象 {position: {x, y}}
            if (event && event.position) {
                renderedPos = event.position;
            } else {
                // 计算位置（从鼠标事件）
                var containerPos = $(this.options.container).offset();
                var position = {
                    x: event.clientX - containerPos.left,
                    y: event.clientY - containerPos.top
                };

                // 转换为图谱坐标
                renderedPos = this.cy.renderedToModelPosition(position);
            }

            // 显示节点编辑对话框
            this.showNodeDialog(type, null, renderedPos);
        },
        
        /**
         * 创建关系
         */
        createRelation: function(sourceNode, targetNode, relationType) {
            var self = this;
            
            // 提取数据库ID（去掉node_前缀）
            var sourceId = sourceNode.data('dbId') || sourceNode.id().replace('node_', '');
            var targetId = targetNode.data('dbId') || targetNode.id().replace('node_', '');
            
            var relationData = {
                from_node_id: parseInt(sourceId),
                to_node_id: parseInt(targetId),
                relation_type: relationType,
                course_id: this.options.courseId,
                description: ''
            };
            
            // 发送到后端保存
            $.ajax({
                url: this.options.apiBase + '/relation/create',
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify(relationData),
                success: function(response) {
                    if (response.code === 0) {
                        // 添加边到图谱
                        self.cy.add({
                            group: 'edges',
                            data: {
                                id: 'edge_' + response.data.relation.id,
                                dbId: response.data.relation.id,
                                source: sourceNode.id(),
                                target: targetNode.id(),
                                label: self.getRelationLabel(relationType),
                                relationType: relationType,
                                lineColor: self.getRelationColor(relationType),
                                lineStyle: self.getRelationStyle(relationType)
                            }
                        });
                        
                        self.markAsModified();
                        layer.msg('关系创建成功', {icon: 1});
                    } else {
                        layer.msg(response.message || '关系创建失败', {icon: 2});
                    }
                },
                error: function(xhr) {
                    layer.msg('网络错误：' + xhr.status, {icon: 2});
                }
            });
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
                headers: {
                    'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                },
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
            // 修复：从 node_123 中提取数字ID
            var nodeId = node.id().replace('node_', '');

            $.ajax({
                url: this.options.apiBase + '/node/' + nodeId + '/delete',  // 修复：使用正确的路由
                method: 'POST',  // 修复：使用POST而不是DELETE
                headers: {
                    'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                },
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
            // 修复：从 edge_123 中提取数字ID
            var edgeId = edge.id().replace('edge_', '');

            $.ajax({
                url: this.options.apiBase + '/relation/' + edgeId + '/delete',  // 修复：使用正确的路由
                method: 'POST',  // 修复：使用POST而不是DELETE
                headers: {
                    'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                },
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
         * 保存图谱（完整保存：节点 + 关系 + 位置）
         */
        saveGraph: function() {
            var self = this;
            
            if (!this.isModified) {
                layer.msg('没有需要保存的修改', {icon: 1});
                return;
            }

            // 准备节点数据
            var nodes = [];
            this.cy.nodes().forEach(function(node) {
                nodes.push({
                    data: node.data(),
                    position: {
                        x: Math.round(node.position('x')),
                        y: Math.round(node.position('y'))
                    },
                    style: {
                        'background-color': node.data('backgroundColor'),
                        'border-color': node.data('borderColor'),
                        'border-width': node.data('borderWidth'),
                        'color': node.data('textColor'),
                        'font-size': node.data('fontSize'),
                        'width': node.data('width'),
                        'height': node.data('height'),
                        'shape': node.data('shape') || 'ellipse'
                    }
                });
            });

            // 准备关系数据
            var edges = [];
            this.cy.edges().forEach(function(edge) {
                edges.push({
                    data: edge.data(),
                    style: {
                        'line-color': edge.data('lineColor'),
                        'target-arrow-color': edge.data('arrowColor'),
                        'target-arrow-shape': edge.data('arrowShape'),
                        'curve-style': edge.data('curveStyle'),
                        'line-style': edge.data('lineStyle'),
                        'width': edge.data('width'),
                        'label': edge.data('label')
                    }
                });
            });

            // 显示保存提示
            var loadingIndex = layer.load(1, {
                shade: [0.3, '#000'],
                content: '正在保存图谱...'
            });

            // 完整保存图谱数据
            $.ajax({
                url: this.options.apiBase + '/save/' + this.options.courseId,
                method: 'POST',
                contentType: 'application/json',
                headers: {
                    'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify({
                    nodes: nodes,
                    edges: edges
                }),
                success: function(response) {
                    layer.close(loadingIndex);
                    
                    if (response.code === 0) {
                        self.isModified = false;
                        
                        var stats = response.data.statistics || {};
                        var message = '保存成功！';
                        if (stats.nodes_created > 0 || stats.nodes_updated > 0) {
                            message += '创建 ' + (stats.nodes_created || 0) + ' 个节点，';
                            message += '更新 ' + (stats.nodes_updated || 0) + ' 个节点';
                        }
                        
                        layer.msg(message, {icon: 1, time: 2000});
                        
                        // 如果有ID映射，更新节点ID（临时ID -> 真实ID）
                        if (stats.id_map) {
                            self.updateNodeIds(stats.id_map);
                        }
                    } else {
                        layer.msg(response.message || '保存失败', {icon: 2});
                    }
                },
                error: function(xhr) {
                    layer.close(loadingIndex);
                    
                    var errorMsg = '网络错误：' + xhr.status;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    layer.msg(errorMsg, {icon: 2});
                }
            });
        },
        
        /**
         * 更新节点ID（将临时ID替换为真实ID）
         */
        updateNodeIds: function(idMap) {
            var self = this;
            
            // 遍历ID映射，更新节点
            Object.keys(idMap).forEach(function(tempId) {
                var realId = idMap[tempId];
                var node = self.cy.getElementById(tempId);
                
                if (node.length > 0 && tempId !== realId.toString()) {
                    // 更新节点的dbId
                    node.data('dbId', realId);
                    // 注意：Cytoscape不允许直接修改节点ID，所以保留临时ID，但记录真实ID
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
         * 处理AJAX错误（包括CSRF token过期）
         */
        handleAjaxError: function(xhr, defaultMsg) {
            defaultMsg = defaultMsg || '操作失败';
            
            // 检查是否是CSRF token错误
            if (xhr.status === 400 && xhr.responseJSON) {
                var error = xhr.responseJSON;
                
                // CSRF token错误
                if (error.code === 'security.invalid_csrf_token' || 
                    error.msg === '无效的CSRF令牌' ||
                    error.errmsg === '无效的CSRF令牌') {
                    
                    layer.confirm('安全令牌已过期，需要刷新页面。是否立即刷新？', {
                        title: '提示',
                        icon: 3,
                        btn: ['刷新页面', '取消']
                    }, function() {
                        location.reload();
                    });
                    return;
                }
                
                // 其他错误
                if (error.msg || error.errmsg) {
                    layer.msg(error.msg || error.errmsg, {icon: 2});
                    return;
                }
            }
            
            // 默认错误处理
            var errorMsg = defaultMsg;
            if (xhr.status) {
                errorMsg += '（错误码：' + xhr.status + '）';
            }
            layer.msg(errorMsg, {icon: 2});
        },

        /**
         * 从章节简单生成知识图谱
         */
        generateFromChapters: function() {
            var self = this;
            
            layer.confirm('从章节生成将创建新的知识图谱，是否继续？', {
                title: '从章节生成',
                btn: ['继续', '取消'],
                icon: 3
            }, function(index) {
                layer.close(index);
                
                // 显示加载提示
                var loadingIndex = layer.load(1, {
                    shade: [0.3, '#000'],
                    content: '正在生成知识图谱...'
                });
                
                $.ajax({
                    url: self.options.apiBase + '/generate/simple',
                    method: 'POST',
                    contentType: 'application/json',
                    headers: {
                        'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: JSON.stringify({
                        course_id: self.options.courseId
                    }),
                    success: function(response) {
                        layer.close(loadingIndex);
                        
                        // 统一使用 code 和 msg 字段（与后端jsonSuccess返回格式一致）
                        if (response.code === 0 && response.data && response.data.graph) {
                            // 加载生成的图谱
                            self.loadGeneratedGraph(response.data.graph);
                            layer.msg(response.data.message || '生成成功！', {icon: 1, time: 2000});
                        } else {
                            layer.msg(response.msg || '生成失败', {icon: 2});
                        }
                    },
                    error: function(xhr) {
                        layer.close(loadingIndex);
                        self.handleAjaxError(xhr, '生成失败');
                    }
                });
            });
        },

        /**
         * 使用AI智能生成知识图谱
         */
        generateWithAI: function() {
            var self = this;
            
            layer.confirm('AI智能生成将分析课程内容并创建知识图谱，可能需要10-30秒，是否继续？', {
                title: 'AI智能生成',
                btn: ['开始生成', '取消'],
                icon: 3
            }, function(index) {
                layer.close(index);
                
                // 显示加载提示
                var loadingIndex = layer.load(1, {
                    shade: [0.5, '#000'],
                    content: 'AI正在分析课程内容...'
                });
                
                $.ajax({
                    url: self.options.apiBase + '/generate/ai',
                    method: 'POST',
                    contentType: 'application/json',
                    headers: {
                        'X-Csrf-Token': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: JSON.stringify({
                        course_id: self.options.courseId
                    }),
                    timeout: 60000, // 60秒超时
                    success: function(response) {
                        layer.close(loadingIndex);
                        
                        // 统一使用 code 和 msg 字段（与后端jsonSuccess返回格式一致）
                        if (response.code === 0 && response.data && response.data.graph) {
                            // 加载生成的图谱
                            self.loadGeneratedGraph(response.data.graph);
                            layer.msg(response.data.message || 'AI生成成功！', {icon: 1, time: 2000});
                        } else {
                            layer.msg(response.msg || 'AI生成失败', {icon: 2});
                        }
                    },
                    error: function(xhr) {
                        layer.close(loadingIndex);
                        self.handleAjaxError(xhr, 'AI生成失败');
                    }
                });
            });
        },

        /**
         * 加载生成的图谱数据
         */
        loadGeneratedGraph: function(graphData) {
            var self = this;
            
            if (!graphData || !graphData.nodes || !graphData.edges) {
                layer.msg('图谱数据格式错误', {icon: 2});
                return;
            }
            
            // 清空当前图谱
            this.cy.elements().remove();
            
            // 添加生成的节点
            graphData.nodes.forEach(function(node) {
                self.cy.add({
                    group: 'nodes',
                    data: node.data,
                    position: node.position || {x: 100, y: 100}
                });
            });
            
            // 添加生成的关系
            graphData.edges.forEach(function(edge) {
                self.cy.add({
                    group: 'edges',
                    data: edge.data
                });
            });
            
            // 应用层次布局
            this.applyLayout('dagre');
            
            // 更新统计
            this.updateStatistics();
            
            // 标记为已修改
            this.markAsModified();
            
            // 提示保存
            layer.msg('图谱已生成，请记得保存！', {icon: 1, time: 3000});
        },

        /**
         * 显示节点详情和绑定的学习资源
         */
        showNodeDetail: function(nodeId) {
            var self = this;
            
            $.ajax({
                url: this.options.apiBase + '/node/' + nodeId + '/detail',
                method: 'GET',
                success: function(response) {
                    if (response.errcode === 0 && response.data) {
                        self.renderNodeDetailPanel(response.data);
                    }
                },
                error: function(xhr) {
                    console.error('获取节点详情失败', xhr);
                }
            });
        },

        /**
         * 渲染节点详情面板
         */
        renderNodeDetailPanel: function(data) {
            var node = data.node;
            var resources = data.resources;
            
            // 构建资源列表HTML
            var resourcesHtml = '';
            
            // 课时列表
            if (resources.lessons && resources.lessons.length > 0) {
                resourcesHtml += '<div class="kg-resource-group">';
                resourcesHtml += '<h4><i class="layui-icon layui-icon-play"></i> 相关课时 (' + resources.lessons.length + ')</h4>';
                resourcesHtml += '<ul class="kg-resource-list">';
                resources.lessons.forEach(function(lesson) {
                    var modelIcon = lesson.model === 'live' ? 'layui-icon-play-video' : 'layui-icon-play';
                    resourcesHtml += '<li>';
                    resourcesHtml += '<i class="layui-icon ' + modelIcon + '"></i>';
                    resourcesHtml += '<span>' + lesson.title + '</span>';
                    resourcesHtml += '</li>';
                });
                resourcesHtml += '</ul>';
                resourcesHtml += '</div>';
            }
            
            // 作业列表
            if (resources.assignments && resources.assignments.length > 0) {
                resourcesHtml += '<div class="kg-resource-group">';
                resourcesHtml += '<h4><i class="layui-icon layui-icon-edit"></i> 相关作业 (' + resources.assignments.length + ')</h4>';
                resourcesHtml += '<ul class="kg-resource-list">';
                resources.assignments.forEach(function(assignment) {
                    resourcesHtml += '<li>';
                    resourcesHtml += '<i class="layui-icon layui-icon-edit"></i>';
                    resourcesHtml += '<span>' + assignment.title + '</span>';
                    resourcesHtml += '</li>';
                });
                resourcesHtml += '</ul>';
                resourcesHtml += '</div>';
            }
            
            // 如果没有资源
            if (!resourcesHtml) {
                resourcesHtml = '<div class="kg-no-resources">';
                resourcesHtml += '<i class="layui-icon layui-icon-tips"></i>';
                resourcesHtml += '<p>该节点暂未绑定学习资源</p>';
                resourcesHtml += '</div>';
            }
            
            // 构建完整的详情HTML
            var detailHtml = '<div class="kg-node-detail">';
            detailHtml += '<h3>' + node.name + '</h3>';
            detailHtml += '<div class="kg-node-meta">';
            detailHtml += '<span class="kg-node-type">' + this.getNodeTypeLabel(node.type) + '</span>';
            detailHtml += '</div>';
            if (node.description) {
                detailHtml += '<div class="kg-node-description">' + node.description + '</div>';
            }
            detailHtml += '<div class="kg-node-resources">';
            detailHtml += resourcesHtml;
            detailHtml += '</div>';
            detailHtml += '</div>';
            
            // 使用Layer显示详情
            layer.open({
                type: 1,
                title: '节点详情',
                area: ['500px', '600px'],
                content: detailHtml,
                btn: ['关闭'],
                yes: function(index) {
                    layer.close(index);
                }
            });
        },

        /**
         * 获取节点类型标签
         */
        getNodeTypeLabel: function(type) {
            var labels = {
                'concept': '概念',
                'skill': '技能',
                'topic': '主题',
                'course': '课程'
            };
            return labels[type] || type;
        },

        /**
         * 获取关系标签
         */
        getRelationLabel: function(relationType) {
            var labels = {
                'prerequisite': '前置',
                'contains': '包含',
                'related': '相关',
                'suggests': '建议',
                'extends': '扩展'
            };
            return labels[relationType] || relationType;
        },
        
        /**
         * 获取关系颜色
         */
        getRelationColor: function(relationType) {
            var colors = {
                'prerequisite': '#FF5722',  // 橙红色
                'contains': '#2196F3',      // 蓝色
                'related': '#4CAF50',       // 绿色
                'suggests': '#FF9800',      // 橙色
                'extends': '#9C27B0'        // 紫色
            };
            return colors[relationType] || '#999999';
        },
        
        /**
         * 获取关系线条样式
         */
        getRelationStyle: function(relationType) {
            var styles = {
                'prerequisite': 'solid',
                'contains': 'solid',
                'related': 'dashed',
                'suggests': 'dotted',
                'extends': 'solid'
            };
            return styles[relationType] || 'solid';
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

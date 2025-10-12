{% if graph_data.nodes|length > 0 %}
    <div class="knowledge-graph-container">
        <div class="graph-toolbar" style="margin-bottom: 15px; padding: 15px; background: #FAFAFA; border-radius: 2px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="color: #666;">
                    <i class="layui-icon layui-icon-chart"></i>
                    共 <strong style="color: #16BAAA;">{{ graph_data.nodes|length }}</strong> 个知识点
                </div>
                <div>
                    <button class="layui-btn layui-btn-xs layui-btn-primary" id="btn-fit-graph">
                        <i class="layui-icon layui-icon-screen-full"></i> 适应画布
                    </button>
                    <button class="layui-btn layui-btn-xs layui-btn-primary" id="btn-reset-zoom">
                        <i class="layui-icon layui-icon-refresh"></i> 重置缩放
                    </button>
                    <select id="graph-layout" class="layui-btn layui-btn-xs" style="padding: 0 10px; height: 28px;">
                        <option value="breadthfirst">层次布局</option>
                        <option value="cose">力导向布局</option>
                        <option value="circle">圆形布局</option>
                        <option value="grid">网格布局</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div id="knowledge-graph" style="width: 100%; height: 600px; background: #FFFFFF; border: 1px solid #E6E6E6; border-radius: 2px; position: relative;">
            <div class="loading-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.9); z-index: 10;">
                <div style="text-align: center;">
                    <i class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop" style="font-size: 32px; color: #16BAAA;"></i>
                    <p style="margin-top: 10px; color: #999;">加载知识图谱中...</p>
                </div>
            </div>
        </div>
        
        <!-- 节点详情面板 -->
        <div id="node-details" style="display: none; margin-top: 15px; padding: 20px; background: #FFFFFF; border: 1px solid #E6E6E6; border-radius: 2px;">
            <h4 style="margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #16BAAA;">
                <i class="layui-icon layui-icon-about"></i> 知识点详情
            </h4>
            <div id="node-details-content"></div>
        </div>
        
        <!-- 图例说明 -->
        <div class="graph-legend" style="margin-top: 15px; padding: 15px; background: #FAFAFA; border-radius: 2px; font-size: 13px;">
            <strong style="color: #333;"><i class="layui-icon layui-icon-tips"></i> 图例说明：</strong>
            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 20px;">
                <div>
                    <span style="display: inline-block; width: 12px; height: 12px; background: #16BAAA; border-radius: 50%; margin-right: 5px;"></span>
                    <span style="color: #666;">概念节点</span>
                </div>
                <div>
                    <span style="display: inline-block; width: 12px; height: 12px; background: #FFB800; border-radius: 50%; margin-right: 5px;"></span>
                    <span style="color: #666;">技能节点</span>
                </div>
                <div>
                    <span style="display: inline-block; width: 12px; height: 12px; background: #FF5722; border-radius: 50%; margin-right: 5px;"></span>
                    <span style="color: #666;">案例节点</span>
                </div>
                <div style="margin-left: 20px;">
                    <span style="color: #999;">提示：点击节点查看详情，滚轮缩放，拖拽移动</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // 图谱数据
        var graphData = {{ graph_data|json_encode|raw }};
        console.log('=== 前台知识图谱数据 ===');
        console.log('graphData:', graphData);
        console.log('nodes count:', graphData.nodes ? graphData.nodes.length : 0);
        console.log('edges count:', graphData.edges ? graphData.edges.length : 0);
    </script>
    
    {{ js_include('lib/cytoscape.min.js') }}
    
    <script>
    layui.use(['jquery', 'layer'], function() {
        var $ = layui.jquery;
        var layer = layui.layer;
        
        console.log('初始化Cytoscape...');
        
        // 初始化Cytoscape
        var cy = cytoscape({
            container: document.getElementById('knowledge-graph'),
            
            elements: graphData,
            
            style: [
                {
                    selector: 'node',
                    style: {
                        'background-color': function(ele) {
                            var type = ele.data('type');
                            if (type === 'skill') return '#FFB800';
                            if (type === 'case') return '#FF5722';
                            return '#16BAAA';
                        },
                        'label': 'data(label)',
                        'color': '#333',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '12px',
                        'width': 60,
                        'height': 60,
                        'text-wrap': 'wrap',
                        'text-max-width': '80px'
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 2,
                        'line-color': '#ccc',
                        'target-arrow-color': '#ccc',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'label': 'data(type)',
                        'font-size': '10px',
                        'color': '#999'
                    }
                },
                {
                    selector: 'node:selected',
                    style: {
                        'border-width': 3,
                        'border-color': '#1E9FFF'
                    }
                }
            ],
            
            layout: {
                name: 'breadthfirst',
                directed: true,
                padding: 30,
                spacingFactor: 1.5
            },
            
            // 只读模式 - 禁用编辑
            userZoomingEnabled: true,
            userPanningEnabled: true,
            boxSelectionEnabled: false,
            autoungrabify: true,  // 节点不可拖动
            autounselectify: false
        });
        
        console.log('Cytoscape初始化完成');
        console.log('节点数量:', cy.nodes().length);
        console.log('边数量:', cy.edges().length);
        
        // 隐藏加载提示
        $('.loading-overlay').fadeOut();
        
        // 如果没有节点，显示提示
        if (cy.nodes().length === 0) {
            console.warn('警告：图谱中没有节点！');
            layer.msg('图谱数据为空', {icon: 0});
        }
        
        // 节点点击事件 - 显示详情
        cy.on('tap', 'node', function(evt) {
            var node = evt.target;
            var data = node.data();
            
            var html = '<div style="line-height: 2;">';
            html += '<p><strong>名称：</strong>' + data.label + '</p>';
            html += '<p><strong>类型：</strong>' + getNodeTypeName(data.type) + '</p>';
            if (data.description) {
                html += '<p><strong>说明：</strong>' + data.description + '</p>';
            }
            
            // 显示前置知识
            var prerequisites = cy.edges('[target="' + data.id + '"]');
            if (prerequisites.length > 0) {
                html += '<p><strong>前置知识：</strong></p><ul style="padding-left: 20px;">';
                prerequisites.forEach(function(edge) {
                    var sourceNode = edge.source();
                    html += '<li>' + sourceNode.data('label') + '</li>';
                });
                html += '</ul>';
            }
            
            // 显示后续知识
            var followups = cy.edges('[source="' + data.id + '"]');
            if (followups.length > 0) {
                html += '<p><strong>后续学习：</strong></p><ul style="padding-left: 20px;">';
                followups.forEach(function(edge) {
                    var targetNode = edge.target();
                    html += '<li>' + targetNode.data('label') + '</li>';
                });
                html += '</ul>';
            }
            
            html += '</div>';
            
            $('#node-details-content').html(html);
            $('#node-details').slideDown();
        });
        
        // 工具栏按钮
        $('#btn-fit-graph').on('click', function() {
            cy.fit(null, 50);
        });
        
        $('#btn-reset-zoom').on('click', function() {
            cy.zoom(1);
            cy.center();
        });
        
        $('#graph-layout').on('change', function() {
            var layoutName = $(this).val();
            cy.layout({
                name: layoutName,
                directed: true,
                padding: 30,
                spacingFactor: 1.5
            }).run();
        });
        
        // 辅助函数
        function getNodeTypeName(type) {
            var types = {
                'concept': '概念',
                'skill': '技能',
                'case': '案例'
            };
            return types[type] || '其他';
        }
    });
    </script>
    
{% else %}
    <div class="no-records">
        <p><i class="layui-icon layui-icon-chart" style="font-size: 48px; color: #E6E6E6;"></i></p>
        <p>该课程暂无知识图谱</p>
    </div>
{% endif %}


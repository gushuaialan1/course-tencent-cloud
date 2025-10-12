{% extends 'templates/main.volt' %}

{% block content %}

    {# 强制转换所有变量为数组，避免 Phalcon View 的对象转换问题 #}
    <?php
    // 转换主要变量
    $templates = is_array($templates) ? $templates : (array)$templates;
    $categories = is_array($categories) ? $categories : (array)$categories;
    $difficulty_levels = is_array($difficulty_levels) ? $difficulty_levels : (array)$difficulty_levels;
    $statistics = is_array($statistics) ? $statistics : (array)$statistics;
    
    // 转换templates数组中的每个元素
    if (!empty($templates)) {
        foreach ($templates as $k => $v) {
            $templates[$k] = is_array($v) ? $v : (array)$v;
        }
    }
    ?>

    {% set list_url = url({'for':'admin.knowledge_graph.list'}) %}
    {% set create_url = url({'for':'admin.knowledge_graph.template_create'}) %}

    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a href="{{ list_url }}"><cite>知识图谱管理</cite></a>
                <a><cite>图谱模板</cite></a>
            </span>
        </div>
        <div class="kg-nav-right">
            <a class="layui-btn layui-btn-sm" href="{{ list_url }}">
                <i class="layui-icon layui-icon-return"></i>返回列表
            </a>
        </div>
    </div>

    <!-- 筛选工具栏 -->
    <div class="layui-card">
        <div class="layui-card-body">
            <form class="layui-form layui-form-pane" lay-filter="filter-form">
                <div class="layui-form-item">
                    <div class="layui-inline">
                        <label class="layui-form-label">分类筛选</label>
                        <div class="layui-input-inline" style="width: 150px;">
                            <select name="category" lay-filter="category">
                                <option value="">全部分类</option>
                                {% for key, value in categories %}
                                    <option value="{{ key }}" {% if category == key %}selected{% endif %}>{{ value }}</option>
                                {% endfor %}
                            </select>
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">难度筛选</label>
                        <div class="layui-input-inline" style="width: 150px;">
                            <select name="difficulty_level" lay-filter="difficulty">
                                <option value="">全部难度</option>
                                {% for key, value in difficulty_levels %}
                                    <option value="{{ key }}" {% if difficulty == key %}selected{% endif %}>{{ value }}</option>
                                {% endfor %}
                            </select>
                        </div>
                    </div>
                    <div class="layui-inline">
                        <label class="layui-form-label">关键词</label>
                        <div class="layui-input-inline" style="width: 200px;">
                            <input type="text" name="keyword" placeholder="搜索模板名称、描述或标签" value="{{ keyword }}" class="layui-input">
                        </div>
                    </div>
                    <div class="layui-inline">
                        <button class="layui-btn" lay-submit lay-filter="search">
                            <i class="layui-icon layui-icon-search"></i>搜索
                        </button>
                        <button type="reset" class="layui-btn layui-btn-primary">
                            <i class="layui-icon layui-icon-refresh"></i>重置
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 模板统计 -->
    {% if statistics is defined %}
    <div class="layui-card">
        <div class="layui-card-header">
            <i class="layui-icon layui-icon-chart"></i> 模板统计
        </div>
        <div class="layui-card-body">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md3">
                    <div class="kg-stat-item">
                        <div class="kg-stat-value">{{ statistics.total }}</div>
                        <div class="kg-stat-label">总模板数</div>
                    </div>
                </div>
                <div class="layui-col-md3">
                    <div class="kg-stat-item">
                        <div class="kg-stat-value">{{ statistics.system_count }}</div>
                        <div class="kg-stat-label">系统模板</div>
                    </div>
                </div>
                {% if statistics.by_category is defined %}
                    {% for category, stat in statistics.by_category %}
                        {% if loop.index <= 2 %}
                        <div class="layui-col-md3">
                            <div class="kg-stat-item">
                                <div class="kg-stat-value">{{ stat.count }}</div>
                                <div class="kg-stat-label">{{ stat.label }}</div>
                            </div>
                        </div>
                        {% endif %}
                    {% endfor %}
                {% endif %}
            </div>
        </div>
    </div>
    {% endif %}

    <!-- 模板列表 -->
    <div class="layui-row layui-col-space15">
        {% if templates is empty %}
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-body center">
                        <i class="layui-icon layui-icon-template-1" style="font-size: 64px; color: #ccc;"></i>
                        <p style="margin-top: 20px; color: #999;">暂无模板数据</p>
                    </div>
                </div>
            </div>
        {% else %}
            {% for template in templates %}
                <div class="layui-col-md6">
                    <div class="layui-card kg-template-card" data-template-id="{{ template.id }}">
                        <div class="layui-card-header">
                            <div class="kg-template-header">
                                <div class="kg-template-title">
                                    <strong>{{ template.name }}</strong>
                                    {% if template.is_system %}
                                        <span class="layui-badge layui-bg-orange">系统</span>
                                    {% endif %}
                                </div>
                                <div class="kg-template-meta">
                                    {% if categories[template.category] is defined %}
                                        <span class="layui-badge layui-bg-cyan">{{ categories[template.category] }}</span>
                                    {% endif %}
                                    {% if difficulty_levels[template.difficulty_level] is defined %}
                                        <span class="layui-badge">{{ difficulty_levels[template.difficulty_level] }}</span>
                                    {% endif %}
                                </div>
                            </div>
                        </div>
                        <div class="layui-card-body">
                            <div class="kg-template-desc">
                                {{ template.description|default('暂无描述') }}
                            </div>
                            <div class="kg-template-stats">
                                <span><i class="layui-icon layui-icon-circle"></i> {{ template.node_count }} 节点</span>
                                <span><i class="layui-icon layui-icon-link"></i> {{ template.relation_count }} 关系</span>
                                <span><i class="layui-icon layui-icon-praise"></i> {{ template.usage_count }} 次使用</span>
                            </div>
                            {% if template.tags %}
                                <div class="kg-template-tags">
                                    {% set tags = template.tags|split(',') %}
                                    {% for tag in tags %}
                                        {% if tag %}
                                            <span class="kg-tag">{{ tag }}</span>
                                        {% endif %}
                                    {% endfor %}
                                </div>
                            {% endif %}
                        </div>
                        <div class="layui-card-footer">
                            <div class="layui-btn-group" style="width: 100%;">
                                <button class="layui-btn layui-btn-sm layui-btn-normal" onclick="previewTemplate({{ template.id }})" style="flex: 1;">
                                    <i class="layui-icon layui-icon-find-fill"></i>预览
                                </button>
                                <button class="layui-btn layui-btn-sm layui-btn-warm" onclick="selectCourseAndApply({{ template.id }})" style="flex: 1;">
                                    <i class="layui-icon layui-icon-add-circle"></i>应用到课程
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            {% endfor %}
        {% endif %}
    </div>

    <!-- 分页 -->
    {% if total > 0 %}
        <div class="kg-page-wrap">
            {{ partial('partials/pager', ['url_path': url({'for':'admin.knowledge_graph.templates'}), 'pager_total': total, 'pager_page': page, 'pager_limit': limit]) }}
        </div>
    {% endif %}

    <style>
        .kg-template-card {
            transition: all 0.3s;
            border: 1px solid #e6e6e6;
        }
        .kg-template-card:hover {
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .kg-template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kg-template-title {
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .kg-template-meta {
            display: flex;
            gap: 5px;
        }
        .kg-template-desc {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            min-height: 50px;
        }
        .kg-template-stats {
            display: flex;
            gap: 15px;
            color: #999;
            font-size: 12px;
            margin-bottom: 10px;
            padding: 10px 0;
            border-top: 1px solid #f0f0f0;
        }
        .kg-template-stats i {
            margin-right: 3px;
        }
        .kg-template-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        .kg-tag {
            display: inline-block;
            padding: 2px 8px;
            background: #f5f5f5;
            border-radius: 3px;
            font-size: 12px;
            color: #666;
        }
        .layui-card-footer {
            padding: 10px 15px;
            background: #fafafa;
            border-top: 1px solid #e6e6e6;
        }
        .kg-stat-item {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 5px;
        }
        .kg-stat-value {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .kg-stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>

    <script>
        layui.use(['layer', 'form'], function(){
            var layer = layui.layer;
            var form = layui.form;

            // 表单筛选
            form.on('select(category)', function(data){
                submitFilter();
            });

            form.on('select(difficulty)', function(data){
                submitFilter();
            });

            form.on('submit(search)', function(data){
                submitFilter();
                return false;
            });

            function submitFilter() {
                var params = $('.layui-form').serialize();
                window.location.href = '{{ url({"for":"admin.knowledge_graph.templates"}) }}?' + params;
            }

            // 预览模板
            window.previewTemplate = function(templateId) {
                var url = '{{ url({"for":"admin.knowledge_graph.template_detail"}) }}'.replace(/\/0$/, '/' + templateId);
                
                $.get(url, function(res) {
                    if (res.code === 0) {
                        var data = res.data;
                        var content = '<div style="padding: 20px;">';
                        content += '<h3>' + data.name + '</h3>';
                        content += '<p style="color: #666; margin: 10px 0;">' + (data.description || '暂无描述') + '</p>';
                        content += '<div style="margin: 15px 0;"><strong>节点数量：</strong>' + data.node_count + ' 个</div>';
                        content += '<div style="margin: 15px 0;"><strong>关系数量：</strong>' + data.relation_count + ' 个</div>';
                        
                        if (data.tags_array && data.tags_array.length > 0) {
                            content += '<div style="margin: 15px 0;"><strong>标签：</strong>';
                            data.tags_array.forEach(function(tag) {
                                content += '<span class="kg-tag">' + tag + '</span> ';
                            });
                            content += '</div>';
                        }
                        
                        // 显示节点类型统计
                        if (data.nodes && data.nodes.length > 0) {
                            var typeCount = {};
                            data.nodes.forEach(function(node) {
                                typeCount[node.type] = (typeCount[node.type] || 0) + 1;
                            });
                            content += '<div style="margin: 15px 0;"><strong>节点类型分布：</strong><br/>';
                            for (var type in typeCount) {
                                content += '<span style="margin-right: 15px;">' + type + ': ' + typeCount[type] + '</span>';
                            }
                            content += '</div>';
                        }
                        
                        content += '</div>';
                        
                        layer.open({
                            type: 1,
                            title: '模板详情',
                            area: ['600px', '500px'],
                            content: content,
                            btn: ['关闭'],
                            yes: function(index) {
                                layer.close(index);
                            }
                        });
                    } else {
                        layer.msg(res.msg || '获取模板详情失败', {icon: 2});
                    }
                }).fail(function() {
                    layer.msg('网络请求失败', {icon: 2});
                });
            };

            // 选择课程并应用模板
            window.selectCourseAndApply = function(templateId) {
                layer.open({
                    type: 2,
                    title: '选择要应用模板的课程',
                    area: ['800px', '600px'],
                    content: '{{ url({"for":"admin.course.list"}) }}?select_mode=1&template_id=' + templateId,
                    btn: ['取消'],
                    yes: function(index) {
                        layer.close(index);
                    }
                });
            };
        });
    </script>

{% endblock %}


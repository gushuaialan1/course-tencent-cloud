{% extends 'templates/main.volt' %}

{% block content %}

    {% set list_url = url({'for':'admin.knowledge_graph.list'}) %}
    {% set save_url = url({'for':'admin.knowledge_graph.ai_config_save'}) %}
    {% set test_url = url({'for':'admin.knowledge_graph.ai_config_test'}) %}

    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a href="{{ list_url }}"><cite>知识图谱管理</cite></a>
                <a><cite>AI配置</cite></a>
            </span>
        </div>
        <div class="kg-nav-right">
            <a class="layui-btn layui-btn-sm" href="{{ list_url }}">
                <i class="layui-icon layui-icon-return"></i>返回列表
            </a>
        </div>
    </div>

    <div class="layui-container" style="padding: 20px;">
        <div class="layui-row layui-col-space15">
            <!-- 左侧配置表单 -->
            <div class="layui-col-md8">
                <div class="layui-card">
                    <div class="layui-card-header">
                        <i class="layui-icon layui-icon-set"></i> AI服务配置
                    </div>
                    <div class="layui-card-body">
                        <form class="layui-form" lay-filter="ai-config-form">
                            <!-- AI服务提供商 -->
                            <div class="layui-form-item">
                                <label class="layui-form-label">服务提供商</label>
                                <div class="layui-input-block">
                                    <select name="provider" lay-filter="provider" lay-verify="required">
                                        {% for key, value in providers %}
                                            <option value="{{ key }}" {% if ai_config.provider == key %}selected{% endif %}>{{ value }}</option>
                                        {% endfor %}
                                    </select>
                                    <div class="layui-form-mid layui-word-aux">选择AI服务商或关闭AI功能</div>
                                </div>
                            </div>

                            <!-- DeepSeek配置 -->
                            <div id="deepseek-config" style="display: none;">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">API Key</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="api_key" placeholder="请输入DeepSeek API Key" class="layui-input" value="{{ configs['ai_api_key'] }}">
                                        <div class="layui-form-mid layui-word-aux">
                                            <a href="https://platform.deepseek.com" target="_blank">获取API Key</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">模型选择</label>
                                    <div class="layui-input-block">
                                        <select name="model" lay-filter="model">
                                            {% for key, value in deepseek_models %}
                                                <option value="{{ key }}" {% if ai_config.model == key %}selected{% endif %}>{{ value }}</option>
                                            {% endfor %}
                                        </select>
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">API地址</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="base_url" placeholder="https://api.deepseek.com" class="layui-input" value="{{ ai_config.base_url }}">
                                        <div class="layui-form-mid layui-word-aux">默认使用官方地址，一般无需修改</div>
                                    </div>
                                </div>
                            </div>

                            <!-- 硅基流动配置 -->
                            <div id="siliconflow-config" style="display: none;">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">API Key</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="api_key" placeholder="请输入硅基流动 API Key" class="layui-input" value="{{ configs['ai_api_key'] }}">
                                        <div class="layui-form-mid layui-word-aux">
                                            <a href="https://siliconflow.cn" target="_blank">获取API Key</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">模型选择</label>
                                    <div class="layui-input-block">
                                        <select name="model" lay-filter="model">
                                            {% for key, value in siliconflow_models %}
                                                <option value="{{ key }}" {% if ai_config.model == key %}selected{% endif %}>{{ value }}</option>
                                            {% endfor %}
                                        </select>
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">API地址</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="base_url" placeholder="https://api.siliconflow.cn" class="layui-input" value="{{ ai_config.base_url }}">
                                        <div class="layui-form-mid layui-word-aux">默认使用官方地址，一般无需修改</div>
                                    </div>
                                </div>
                            </div>

                            <!-- 生成方式 -->
                            <div class="layui-form-item">
                                <label class="layui-form-label">生成方式</label>
                                <div class="layui-input-block">
                                    <select name="generation_mode">
                                        {% for key, value in generation_modes %}
                                            <option value="{{ key }}" {% if ai_config.generation_mode == key %}selected{% endif %}>{{ value }}</option>
                                        {% endfor %}
                                    </select>
                                    <div class="layui-form-mid layui-word-aux">选择图谱生成方式</div>
                                </div>
                            </div>

                            <!-- 高级选项 -->
                            <div class="layui-form-item">
                                <label class="layui-form-label"></label>
                                <div class="layui-input-block">
                                    <button type="button" class="layui-btn layui-btn-sm layui-btn-primary" id="toggle-advanced">
                                        <i class="layui-icon layui-icon-down"></i>高级选项
                                    </button>
                                </div>
                            </div>

                            <div id="advanced-options" style="display: none;">
                                <div class="layui-form-item">
                                    <label class="layui-form-label">超时时间（秒）</label>
                                    <div class="layui-input-inline" style="width: 150px;">
                                        <input type="number" name="timeout" value="{{ ai_config.timeout }}" class="layui-input">
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">API请求超时时间</div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">最大令牌数</label>
                                    <div class="layui-input-inline" style="width: 150px;">
                                        <input type="number" name="max_tokens" value="{{ ai_config.max_tokens }}" class="layui-input">
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">生成内容的最大长度</div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">温度参数</label>
                                    <div class="layui-input-inline" style="width: 150px;">
                                        <input type="text" name="temperature" value="{{ ai_config.temperature }}" class="layui-input">
                                    </div>
                                    <div class="layui-form-mid layui-word-aux">0-1之间，值越高越随机</div>
                                </div>
                            </div>

                            <!-- 操作按钮 -->
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button type="button" class="layui-btn" id="btn-test-connection">
                                        <i class="layui-icon layui-icon-ok-circle"></i>测试连接
                                    </button>
                                    <button type="button" class="layui-btn layui-btn-normal" lay-submit lay-filter="save">
                                        <i class="layui-icon layui-icon-ok"></i>保存配置
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 右侧帮助信息 -->
            <div class="layui-col-md4">
                <!-- 提供商信息 -->
                <div id="provider-info-panel">
                    {% for key, info in provider_info %}
                        <div class="layui-card provider-info-card" id="provider-info-{{ key }}" style="display: none;">
                            <div class="layui-card-header">
                                <i class="layui-icon layui-icon-tips"></i> {{ info.name }}
                            </div>
                            <div class="layui-card-body">
                                <p><strong>简介：</strong>{{ info.description }}</p>
                                <p><strong>定价：</strong>{{ info.pricing }}</p>
                                <p><strong>特点：</strong></p>
                                <ul>
                                    {% for feature in info.features %}
                                        <li>{{ feature }}</li>
                                    {% endfor %}
                                </ul>
                                <p>
                                    <a href="{{ info.website }}" target="_blank" class="layui-btn layui-btn-xs">
                                        <i class="layui-icon layui-icon-website"></i>官网
                                    </a>
                                    <a href="{{ info.doc_url }}" target="_blank" class="layui-btn layui-btn-xs layui-btn-primary">
                                        <i class="layui-icon layui-icon-read"></i>文档
                                    </a>
                                </p>
                            </div>
                        </div>
                    {% endfor %}
                </div>

                <!-- 使用说明 -->
                <div class="layui-card">
                    <div class="layui-card-header">
                        <i class="layui-icon layui-icon-help"></i> 使用说明
                    </div>
                    <div class="layui-card-body">
                        <ol style="padding-left: 20px;">
                            <li>选择AI服务提供商</li>
                            <li>输入对应的API Key</li>
                            <li>选择要使用的模型</li>
                            <li>点击"测试连接"验证配置</li>
                            <li>保存配置后即可使用AI生成功能</li>
                        </ol>
                        <hr>
                        <p><strong>提示：</strong></p>
                        <ul style="padding-left: 20px; margin-top: 10px;">
                            <li>如不需要AI功能，选择"关闭AI功能"即可</li>
                            <li>API Key将加密存储，保证安全</li>
                            <li>建议优先使用DeepSeek，性价比高</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    layui.use(['form', 'layer'], function() {
        var form = layui.form;
        var layer = layui.layer;
        var $ = layui.jquery;

        var currentProvider = '{{ ai_config.provider }}';

        // 显示对应的配置面板
        function showProviderConfig(provider) {
            $('#deepseek-config').hide();
            $('#siliconflow-config').hide();
            $('.provider-info-card').hide();

            if (provider === 'deepseek') {
                $('#deepseek-config').show();
                $('#provider-info-deepseek').show();
            } else if (provider === 'siliconflow') {
                $('#siliconflow-config').show();
                $('#provider-info-siliconflow').show();
            }

            form.render();
        }

        // 初始化显示
        showProviderConfig(currentProvider);

        // 监听提供商切换
        form.on('select(provider)', function(data) {
            currentProvider = data.value;
            showProviderConfig(data.value);
        });

        // 切换高级选项
        $('#toggle-advanced').on('click', function() {
            var $advanced = $('#advanced-options');
            var $icon = $(this).find('i');
            
            if ($advanced.is(':visible')) {
                $advanced.slideUp();
                $icon.removeClass('layui-icon-up').addClass('layui-icon-down');
            } else {
                $advanced.slideDown();
                $icon.removeClass('layui-icon-down').addClass('layui-icon-up');
            }
        });

        // 测试连接
        $('#btn-test-connection').on('click', function() {
            var formData = form.val('ai-config-form');
            
            if (formData.provider === 'disabled') {
                layer.msg('请先选择AI服务提供商', {icon: 2});
                return;
            }

            if (!formData.api_key || /^\*+$/.test(formData.api_key)) {
                layer.msg('请输入API Key', {icon: 2});
                return;
            }

            if (!formData.model) {
                layer.msg('请选择模型', {icon: 2});
                return;
            }

            var loadingIndex = layer.load(2, {shade: [0.3, '#000']});

            $.post('{{ test_url }}', {
                provider: formData.provider,
                api_key: formData.api_key,
                model: formData.model,
                base_url: formData.base_url
            }, function(res) {
                layer.close(loadingIndex);
                
                if (res.code === 0) {
                    layer.alert('连接测试成功！<br><br>响应时间：' + res.data.duration + 'ms<br>测试响应：' + res.data.response, {
                        icon: 1,
                        title: '测试成功'
                    });
                } else {
                    layer.alert(res.msg || '连接测试失败', {
                        icon: 2,
                        title: '测试失败'
                    });
                }
            }).fail(function() {
                layer.close(loadingIndex);
                layer.msg('网络请求失败', {icon: 2});
            });
        });

        // 保存配置
        form.on('submit(save)', function(data) {
            var loadingIndex = layer.load(2, {shade: [0.3, '#000']});

            $.post('{{ save_url }}', data.field, function(res) {
                layer.close(loadingIndex);
                
                if (res.code === 0) {
                    layer.msg('保存成功', {icon: 1});
                } else {
                    layer.msg(res.msg || '保存失败', {icon: 2});
                }
            }).fail(function() {
                layer.close(loadingIndex);
                layer.msg('网络请求失败', {icon: 2});
            });

            return false;
        });
    });
    </script>

    <style>
    .provider-info-card ul {
        padding-left: 20px;
        margin-top: 10px;
    }
    .provider-info-card li {
        margin: 5px 0;
    }
    </style>

{% endblock %}


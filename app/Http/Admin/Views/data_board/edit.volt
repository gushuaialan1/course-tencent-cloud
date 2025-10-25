{% extends 'templates/main.volt' %}

{% block content %}

    <div class="kg-nav">
        <div class="kg-nav-left">
            <span class="layui-breadcrumb">
                <a href="{{ url({'for':'admin.data_board.list'}) }}"><cite>数据看板</cite></a>
                <a><cite>编辑统计项</cite></a>
            </span>
        </div>
    </div>

    <form class="layui-form kg-form" method="POST" action="{{ url({'for':'admin.data_board.update'}) }}">
        <input type="hidden" name="id" value="{{ stat.id }}">
        
        <fieldset class="layui-elem-field layui-field-title">
            <legend>基本信息</legend>
        </fieldset>

        <div class="layui-form-item">
            <label class="layui-form-label">统计项KEY</label>
            <div class="layui-input-block">
                <input type="text" class="layui-input layui-disabled" value="{{ stat.stat_key }}" disabled>
                <div class="layui-form-mid layui-word-aux">系统内部标识，不可修改</div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">统计项名称</label>
            <div class="layui-input-block">
                <input type="text" name="stat_name" class="layui-input" value="{{ stat.stat_name }}" lay-verify="required">
            </div>
        </div>

        <fieldset class="layui-elem-field layui-field-title">
            <legend>数据设置</legend>
        </fieldset>

        <div class="layui-form-item">
            <label class="layui-form-label">真实数据</label>
            <div class="layui-input-block">
                <input type="text" class="layui-input layui-disabled" value="{{ stat.real_value|number_format }}" disabled>
                <div class="layui-form-mid layui-word-aux">系统自动统计，点击"刷新"按钮更新</div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">虚拟增量</label>
            <div class="layui-input-block">
                <input type="number" name="virtual_value" class="layui-input" value="{{ stat.virtual_value }}" min="0">
                <div class="layui-form-mid layui-word-aux">在真实数据基础上增加的数值</div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">最终显示值</label>
            <div class="layui-input-block">
                <input type="text" class="layui-input layui-disabled" value="{{ stat.display_value|number_format }}" disabled>
                <div class="layui-form-mid layui-word-aux">真实数据 + 虚拟增量 = {{ stat.real_value|number_format }} + {{ stat.virtual_value|number_format }} = {{ stat.display_value|number_format }}</div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">单位</label>
            <div class="layui-input-block">
                <input type="text" name="unit" class="layui-input" value="{{ stat.unit }}" placeholder="如：门、人、次、万">
            </div>
        </div>

        <fieldset class="layui-elem-field layui-field-title">
            <legend>显示设置</legend>
        </fieldset>

        <div class="layui-form-item">
            <label class="layui-form-label">图标</label>
            <div class="layui-input-block">
                <input type="text" name="icon" class="layui-input" value="{{ stat.icon }}" placeholder="layui图标类名，如：layui-icon-read">
                <div class="layui-form-mid layui-word-aux">
                    参考：<a href="https://layui.dev/docs/2/icon/" target="_blank">Layui图标库</a>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">颜色</label>
            <div class="layui-input-block">
                <select name="color" lay-verify="required">
                    <option value="blue" {% if stat.color == 'blue' %}selected{% endif %}>蓝色</option>
                    <option value="green" {% if stat.color == 'green' %}selected{% endif %}>绿色</option>
                    <option value="orange" {% if stat.color == 'orange' %}selected{% endif %}>橙色</option>
                    <option value="red" {% if stat.color == 'red' %}selected{% endif %}>红色</option>
                    <option value="cyan" {% if stat.color == 'cyan' %}selected{% endif %}>青色</option>
                    <option value="purple" {% if stat.color == 'purple' %}selected{% endif %}>紫色</option>
                </select>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">排序权重</label>
            <div class="layui-input-block">
                <input type="number" name="sort_order" class="layui-input" value="{{ stat.sort_order }}" min="0">
                <div class="layui-form-mid layui-word-aux">数值越小越靠前</div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">显示状态</label>
            <div class="layui-input-block">
                <input type="radio" name="is_visible" value="1" title="显示" {% if stat.is_visible == 1 %}checked{% endif %}>
                <input type="radio" name="is_visible" value="0" title="隐藏" {% if stat.is_visible == 0 %}checked{% endif %}>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">统计项描述</label>
            <div class="layui-input-block">
                <textarea name="description" class="layui-textarea" placeholder="简要说明该统计项的含义">{{ stat.description }}</textarea>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label"></label>
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="true" lay-filter="go">保存修改</button>
                <button type="button" class="kg-back layui-btn layui-btn-primary">返回</button>
            </div>
        </div>
    </form>

{% endblock %}

{% block include_js %}
    {{ js_include('admin/js/data_board.edit.js') }}
{% endblock %}


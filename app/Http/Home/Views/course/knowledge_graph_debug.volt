<!-- 调试视图：显示知识图谱原始数据 -->
<div style="padding: 20px; background: #f5f5f5;">
    <h3>知识图谱数据调试</h3>
    
    <div style="margin: 20px 0;">
        <strong>graph_data 变量类型：</strong>
        <pre>{{ dump(graph_data) }}</pre>
    </div>
    
    <div style="margin: 20px 0;">
        <strong>graph_data 是否存在：</strong>
        {{ graph_data is defined ? 'YES' : 'NO' }}
    </div>
    
    <div style="margin: 20px 0;">
        <strong>graph_data.nodes 是否存在：</strong>
        {{ graph_data.nodes is defined ? 'YES' : 'NO' }}
    </div>
    
    <div style="margin: 20px 0;">
        <strong>nodes 数量：</strong>
        {{ graph_data.nodes ? graph_data.nodes|length : 'N/A' }}
    </div>
    
    <div style="margin: 20px 0;">
        <strong>edges 数量：</strong>
        {{ graph_data.edges ? graph_data.edges|length : 'N/A' }}
    </div>
    
    <div style="margin: 20px 0;">
        <strong>node_count：</strong>
        {{ node_count }}
    </div>
    
    <div style="margin: 20px 0;">
        <strong>edge_count：</strong>
        {{ edge_count }}
    </div>
    
    <div style="margin: 20px 0;">
        <strong>原始 graph_data JSON：</strong>
        <pre style="max-height: 300px; overflow: auto; background: white; padding: 10px; border: 1px solid #ddd;">{{ graph_data|json_encode }}</pre>
    </div>
</div>

<script>
    console.log('=== 调试视图加载 ===');
    console.log('graph_data:', {{ graph_data|json_encode|raw }});
</script>


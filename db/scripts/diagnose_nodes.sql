-- 诊断知识图谱节点数据
-- 查看课程9002（Python数据分析实战）的节点详情

-- 1. 查看节点数量和资源绑定情况
SELECT 
    '节点资源绑定情况' as info,
    COUNT(*) as total,
    SUM(CASE WHEN primary_resource_type IS NOT NULL AND primary_resource_id IS NOT NULL THEN 1 ELSE 0 END) as with_binding,
    SUM(CASE WHEN primary_resource_type IS NULL OR primary_resource_id IS NULL THEN 1 ELSE 0 END) as without_binding
FROM kg_knowledge_node
WHERE course_id = 9002;

-- 2. 查看具体的节点列表（按名称分组）
SELECT 
    name,
    type,
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY id) as node_ids,
    MAX(primary_resource_type) as resource_type,
    MAX(primary_resource_id) as resource_id
FROM kg_knowledge_node
WHERE course_id = 9002
GROUP BY name, type
HAVING COUNT(*) > 1
ORDER BY count DESC
LIMIT 20;

-- 3. 查看没有资源绑定的节点
SELECT 
    id,
    name,
    type,
    chapter_id,
    primary_resource_type,
    primary_resource_id,
    create_time
FROM kg_knowledge_node
WHERE course_id = 9002
  AND (primary_resource_type IS NULL OR primary_resource_id IS NULL)
ORDER BY name, id
LIMIT 30;

-- 4. 查看有资源绑定的节点（示例）
SELECT 
    id,
    name,
    type,
    chapter_id,
    primary_resource_type,
    primary_resource_id
FROM kg_knowledge_node
WHERE course_id = 9002
  AND primary_resource_type IS NOT NULL
  AND primary_resource_id IS NOT NULL
ORDER BY name, id
LIMIT 30;


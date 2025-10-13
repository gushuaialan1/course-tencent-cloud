-- 诊断知识图谱数据
-- 检查课程1的知识图谱数据

-- 1. 检查节点数据
SELECT '=== 节点数据 ===' as info;
SELECT 
    id,
    course_id,
    name,
    type,
    status,
    position_x,
    position_y
FROM kg_knowledge_node
WHERE course_id = 1 
    AND status = 'published'
ORDER BY id
LIMIT 5;

-- 2. 检查关系数据
SELECT '=== 关系数据 ===' as info;
SELECT 
    r.id,
    r.from_node_id,
    r.to_node_id,
    r.relation_type,
    r.status,
    n1.name as from_node_name,
    n2.name as to_node_name
FROM kg_knowledge_relation r
INNER JOIN kg_knowledge_node n1 ON r.from_node_id = n1.id
INNER JOIN kg_knowledge_node n2 ON r.to_node_id = n2.id
WHERE n1.course_id = 1 
    AND r.status = 'active'
LIMIT 5;

-- 3. 检查节点和关系是否匹配
SELECT '=== 验证数据完整性 ===' as info;
SELECT 
    '节点总数' as item,
    COUNT(*) as count
FROM kg_knowledge_node
WHERE course_id = 1 AND status = 'published'

UNION ALL

SELECT 
    '关系总数' as item,
    COUNT(*) as count
FROM kg_knowledge_relation r
INNER JOIN kg_knowledge_node n1 ON r.from_node_id = n1.id
WHERE n1.course_id = 1 AND r.status = 'active'

UNION ALL

SELECT 
    '关系引用了不存在的节点' as item,
    COUNT(*) as count
FROM kg_knowledge_relation r
LEFT JOIN kg_knowledge_node n1 ON r.from_node_id = n1.id
LEFT JOIN kg_knowledge_node n2 ON r.to_node_id = n2.id
WHERE (n1.course_id = 1 OR n2.course_id = 1)
    AND r.status = 'active'
    AND (n1.id IS NULL OR n2.id IS NULL OR n1.status != 'published' OR n2.status != 'published');


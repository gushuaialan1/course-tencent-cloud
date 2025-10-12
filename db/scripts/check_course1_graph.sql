-- 检查课程1的知识图谱数据

-- 1. 检查课程1的基本信息
SELECT 
    '课程1基本信息' as info,
    id, 
    title, 
    published,
    deleted
FROM kg_course 
WHERE id = 1;

-- 2. 检查课程1的知识图谱节点
SELECT 
    '课程1知识图谱节点' as info,
    COUNT(*) as total_nodes,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_nodes,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_nodes
FROM kg_knowledge_node
WHERE course_id = 1;

-- 3. 显示课程1的具体节点
SELECT 
    '课程1节点详情' as info,
    id,
    name,
    type,
    status,
    primary_resource_type,
    primary_resource_id,
    position_x,
    position_y
FROM kg_knowledge_node
WHERE course_id = 1
ORDER BY id
LIMIT 20;

-- 4. 检查课程1的知识图谱关系（通过节点关联）
SELECT 
    '课程1知识图谱关系' as info,
    COUNT(*) as total_relations,
    SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) as active_relations
FROM kg_knowledge_relation r
INNER JOIN kg_knowledge_node n ON r.from_node_id = n.id
WHERE n.course_id = 1;

-- 5. 显示课程1的具体关系
SELECT 
    '课程1关系详情' as info,
    r.id,
    r.from_node_id,
    r.to_node_id,
    r.relation_type,
    r.status,
    n1.name as from_node_name,
    n2.name as to_node_name
FROM kg_knowledge_relation r
INNER JOIN kg_knowledge_node n1 ON r.from_node_id = n1.id
LEFT JOIN kg_knowledge_node n2 ON r.to_node_id = n2.id
WHERE n1.course_id = 1
ORDER BY r.id
LIMIT 20;

-- 6. 检查课程1的章节数据
SELECT 
    '课程1章节数据' as info,
    COUNT(*) as total_chapters,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_chapters,
    SUM(CASE WHEN deleted = 0 THEN 1 ELSE 0 END) as active_chapters
FROM kg_chapter
WHERE course_id = 1;

-- 7. 显示课程1的章节列表
SELECT 
    '课程1章节列表' as info,
    id,
    title,
    parent_id,
    published,
    deleted,
    priority
FROM kg_chapter
WHERE course_id = 1
ORDER BY parent_id, priority
LIMIT 15;


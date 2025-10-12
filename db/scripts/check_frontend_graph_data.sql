-- 检查前台知识图谱数据
-- 用于诊断前台为什么没有显示图谱

-- 1. 查看所有课程的知识图谱节点统计
SELECT 
    '所有课程的图谱节点统计' as info,
    n.course_id,
    c.title as course_title,
    COUNT(n.id) as total_nodes,
    SUM(CASE WHEN n.status = 'published' THEN 1 ELSE 0 END) as published_nodes,
    SUM(CASE WHEN n.status = 'draft' THEN 1 ELSE 0 END) as draft_nodes,
    SUM(CASE WHEN n.type = 'concept' THEN 1 ELSE 0 END) as concept_nodes,
    SUM(CASE WHEN n.type = 'skill' THEN 1 ELSE 0 END) as skill_nodes
FROM kg_knowledge_node n
LEFT JOIN kg_course c ON n.course_id = c.id
GROUP BY n.course_id, c.title
ORDER BY n.course_id;

-- 2. 查看所有课程的关系统计
SELECT 
    '所有课程的图谱关系统计' as info,
    n.course_id,
    c.title as course_title,
    COUNT(DISTINCT r.id) as total_relations,
    SUM(CASE WHEN r.status = 'active' THEN 1 ELSE 0 END) as active_relations
FROM kg_knowledge_node n
LEFT JOIN kg_knowledge_relation r ON (r.from_node_id = n.id OR r.to_node_id = n.id)
LEFT JOIN kg_course c ON n.course_id = c.id
GROUP BY n.course_id, c.title
ORDER BY n.course_id;

-- 3. 专门检查测试课程的数据（假设课程ID为1或9002）
SELECT 
    '课程1的图谱详情' as info,
    n.id,
    n.name,
    n.type,
    n.status,
    n.position_x,
    n.position_y,
    n.primary_resource_type,
    n.primary_resource_id
FROM kg_knowledge_node n
WHERE n.course_id = 1
ORDER BY n.id
LIMIT 20;

-- 4. 课程9002的图谱详情
SELECT 
    '课程9002的图谱详情' as info,
    n.id,
    n.name,
    n.type,
    n.status,
    n.position_x,
    n.position_y,
    n.primary_resource_type,
    n.primary_resource_id
FROM kg_knowledge_node n
WHERE n.course_id = 9002
ORDER BY n.id
LIMIT 20;

-- 5. 查看前台可访问的课程列表（已发布的课程）
SELECT 
    '前台可访问课程' as info,
    c.id,
    c.title,
    c.published,
    (SELECT COUNT(*) FROM kg_knowledge_node WHERE course_id = c.id AND status = 'published') as published_nodes
FROM kg_course c
WHERE c.published = 1
  AND c.deleted = 0
ORDER BY c.id
LIMIT 10;

-- 6. 推荐：如果测试课程没有published节点，执行修复
-- UPDATE kg_knowledge_node SET status = 'published' WHERE course_id = 1 AND status = 'draft';
-- UPDATE kg_knowledge_node SET status = 'published' WHERE course_id = 9002 AND status = 'draft';


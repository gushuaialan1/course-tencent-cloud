-- 检查课程节点统计问题

-- 1. 检查课程1的节点统计（直接查询）
SELECT 
    '直接统计' as method,
    COUNT(*) as total_nodes
FROM kg_knowledge_node 
WHERE course_id = 1;

-- 2. 按状态分组统计
SELECT 
    '按状态统计' as method,
    status,
    COUNT(*) as count
FROM kg_knowledge_node 
WHERE course_id = 1
GROUP BY status;

-- 3. 检查课程基本信息
SELECT 
    '课程基本信息' as info,
    id,
    title,
    published,
    deleted,
    lesson_count,
    resource_count
FROM kg_course 
WHERE id = 1;

-- 4. 检查是否有其他课程有节点
SELECT 
    '所有课程节点统计' as info,
    course_id,
    COUNT(*) as node_count
FROM kg_knowledge_node
GROUP BY course_id
ORDER BY course_id;


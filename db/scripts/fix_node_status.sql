-- 修复知识图谱节点和关系的状态值
-- 问题：代码中使用了数字状态（1），但模型定义的是字符串状态（'published', 'active'）
-- 导致查询时找不到节点

-- 查看当前状态分布
SELECT '节点状态分布（修复前）' as info, status, COUNT(*) as count
FROM kg_knowledge_node
GROUP BY status;

SELECT '关系状态分布（修复前）' as info, status, COUNT(*) as count
FROM kg_knowledge_relation
GROUP BY status;

-- 修复节点状态：将数字 1 改为 'published'
UPDATE kg_knowledge_node
SET status = 'published'
WHERE status = '1';

-- 修复关系状态：将数字 1 改为 'active'
UPDATE kg_knowledge_relation
SET status = 'active'
WHERE status = '1';

-- 查看修复后的状态分布
SELECT '节点状态分布（修复后）' as info, status, COUNT(*) as count
FROM kg_knowledge_node
GROUP BY status;

SELECT '关系状态分布（修复后）' as info, status, COUNT(*) as count
FROM kg_knowledge_relation
GROUP BY status;

-- 验证：查看课程9002的节点数量
SELECT 
    '课程9002节点统计' as info,
    COUNT(*) as total_nodes,
    SUM(CASE WHEN type = 'concept' THEN 1 ELSE 0 END) as concept_nodes,
    SUM(CASE WHEN type = 'skill' THEN 1 ELSE 0 END) as skill_nodes,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_nodes
FROM kg_knowledge_node
WHERE course_id = 9002;


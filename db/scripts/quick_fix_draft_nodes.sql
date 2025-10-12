-- 快速修复：将所有 draft 状态的节点改为 published
-- 原因：模型公共属性的默认值覆盖了代码中的赋值

-- 查看修复前的状态
SELECT '修复前节点状态分布' as info, status, COUNT(*) as count
FROM kg_knowledge_node
GROUP BY status;

SELECT '修复前关系状态分布' as info, status, COUNT(*) as count
FROM kg_knowledge_relation
GROUP BY status;

-- 修复节点状态
-- 将所有 draft 状态改为 published（这些节点都是用户已保存的，应该是 published）
UPDATE kg_knowledge_node
SET status = 'published'
WHERE status = 'draft';

-- 修复关系状态（如果有需要）
-- 关系默认应该是 active
UPDATE kg_knowledge_relation
SET status = 'active'
WHERE status IS NULL OR status = '';

-- 查看修复后的状态
SELECT '修复后节点状态分布' as info, status, COUNT(*) as count
FROM kg_knowledge_node
GROUP BY status;

SELECT '修复后关系状态分布' as info, status, COUNT(*) as count
FROM kg_knowledge_relation
GROUP BY status;

-- 验证：查看课程9002的节点
SELECT 
    '课程9002节点统计' as info,
    COUNT(*) as total_nodes,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_nodes,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_nodes
FROM kg_knowledge_node
WHERE course_id = 9002;


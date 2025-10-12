-- 清理重复的知识图谱节点
-- 问题：由于之前的bug，每次保存都会创建新节点，导致大量重复数据
-- 解决：保留每个资源绑定的最新节点，删除旧的重复节点

-- 备份表（可选，建议先备份）
-- CREATE TABLE kg_knowledge_node_backup AS SELECT * FROM kg_knowledge_node;
-- CREATE TABLE kg_knowledge_relation_backup AS SELECT * FROM kg_knowledge_relation;

-- 第一步：找出重复的节点（相同的course_id + primary_resource_type + primary_resource_id）
-- 保留每组中ID最大的（最新的），删除其他的

-- 创建临时表存储要保留的节点ID
CREATE TEMPORARY TABLE IF NOT EXISTS nodes_to_keep AS
SELECT MAX(id) as id
FROM kg_knowledge_node
WHERE primary_resource_type IS NOT NULL 
  AND primary_resource_id IS NOT NULL
GROUP BY course_id, primary_resource_type, primary_resource_id;

-- 第二步：删除相关的关系（源节点或目标节点是要删除的节点）
DELETE FROM kg_knowledge_relation
WHERE from_node_id NOT IN (SELECT id FROM nodes_to_keep)
   OR to_node_id NOT IN (SELECT id FROM nodes_to_keep);

-- 第三步：删除重复的节点（保留nodes_to_keep中的节点）
DELETE FROM kg_knowledge_node
WHERE primary_resource_type IS NOT NULL 
  AND primary_resource_id IS NOT NULL
  AND id NOT IN (SELECT id FROM nodes_to_keep);

-- 清理临时表
DROP TEMPORARY TABLE IF EXISTS nodes_to_keep;

-- 查看清理结果
SELECT 
    course_id,
    COUNT(*) as total_nodes,
    SUM(CASE WHEN type = 'concept' THEN 1 ELSE 0 END) as concept_nodes,
    SUM(CASE WHEN type = 'skill' THEN 1 ELSE 0 END) as skill_nodes
FROM kg_knowledge_node
GROUP BY course_id
ORDER BY course_id;

-- 查看关系数量
SELECT 
    COUNT(*) as total_relations,
    relation_type,
    COUNT(*) as count
FROM kg_knowledge_relation
GROUP BY relation_type;


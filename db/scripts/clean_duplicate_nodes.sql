-- 清理重复的知识图谱节点
-- 问题：由于之前的bug，每次保存都会创建新节点，导致大量重复数据
-- 解决：保留每个资源绑定的最新节点，删除旧的重复节点

-- 备份表（可选，建议先备份）
-- CREATE TABLE kg_knowledge_node_backup AS SELECT * FROM kg_knowledge_node;
-- CREATE TABLE kg_knowledge_relation_backup AS SELECT * FROM kg_knowledge_relation;

-- 第一步：找出重复的节点（相同的course_id + primary_resource_type + primary_resource_id）
-- 保留每组中ID最大的（最新的），删除其他的

-- 创建普通表存储要保留的节点ID（使用普通表避免临时表多次引用问题）
DROP TABLE IF EXISTS nodes_to_keep_temp;
CREATE TABLE nodes_to_keep_temp AS
SELECT MAX(id) as id
FROM kg_knowledge_node
WHERE primary_resource_type IS NOT NULL 
  AND primary_resource_id IS NOT NULL
GROUP BY course_id, primary_resource_type, primary_resource_id;

-- 添加索引以提高查询性能
ALTER TABLE nodes_to_keep_temp ADD INDEX idx_id (id);

-- 第二步：删除相关的关系（源节点或目标节点是要删除的节点）
-- 先找出要删除的节点ID
DELETE FROM kg_knowledge_relation
WHERE from_node_id IN (
    SELECT id FROM kg_knowledge_node
    WHERE primary_resource_type IS NOT NULL 
      AND primary_resource_id IS NOT NULL
      AND id NOT IN (SELECT id FROM nodes_to_keep_temp)
)
OR to_node_id IN (
    SELECT id FROM kg_knowledge_node
    WHERE primary_resource_type IS NOT NULL 
      AND primary_resource_id IS NOT NULL
      AND id NOT IN (SELECT id FROM nodes_to_keep_temp)
);

-- 第三步：删除重复的节点（保留nodes_to_keep_temp中的节点）
DELETE FROM kg_knowledge_node
WHERE primary_resource_type IS NOT NULL 
  AND primary_resource_id IS NOT NULL
  AND id NOT IN (SELECT id FROM nodes_to_keep_temp);

-- 清理临时表
DROP TABLE IF EXISTS nodes_to_keep_temp;

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


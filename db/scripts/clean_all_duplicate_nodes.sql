-- 彻底清理重复的知识图谱节点
-- 问题：之前的脚本只清理了有资源绑定的节点，但大部分节点可能没有设置这些字段
-- 解决：基于课程ID + 节点名称 + 类型来去重

-- 备份表（强烈建议执行前先备份）
-- CREATE TABLE kg_knowledge_node_backup AS SELECT * FROM kg_knowledge_node WHERE course_id = 9002;
-- CREATE TABLE kg_knowledge_relation_backup AS SELECT * FROM kg_knowledge_relation;

-- 第一步：为课程9002创建去重临时表
-- 基于 course_id + name + type，保留ID最大的（最新的）
DROP TABLE IF EXISTS nodes_to_keep_9002;
CREATE TABLE nodes_to_keep_9002 AS
SELECT MAX(id) as id
FROM kg_knowledge_node
WHERE course_id = 9002
GROUP BY course_id, name, type;

-- 添加索引
ALTER TABLE nodes_to_keep_9002 ADD INDEX idx_id (id);

-- 第二步：查看将要删除的节点数量
SELECT 
    '将要删除的节点统计' as info,
    COUNT(*) as nodes_to_delete,
    type,
    COUNT(DISTINCT name) as unique_names
FROM kg_knowledge_node
WHERE course_id = 9002
  AND id NOT IN (SELECT id FROM nodes_to_keep_9002)
GROUP BY type;

-- 第三步：删除指向重复节点的关系
DELETE FROM kg_knowledge_relation
WHERE from_node_id IN (
    SELECT id FROM kg_knowledge_node
    WHERE course_id = 9002
      AND id NOT IN (SELECT id FROM nodes_to_keep_9002)
)
OR to_node_id IN (
    SELECT id FROM kg_knowledge_node
    WHERE course_id = 9002
      AND id NOT IN (SELECT id FROM nodes_to_keep_9002)
);

-- 第四步：删除重复的节点
DELETE FROM kg_knowledge_node
WHERE course_id = 9002
  AND id NOT IN (SELECT id FROM nodes_to_keep_9002);

-- 清理临时表
DROP TABLE IF EXISTS nodes_to_keep_9002;

-- 查看清理结果
SELECT 
    '课程9002清理后的结果' as info,
    COUNT(*) as total_nodes,
    SUM(CASE WHEN type = 'concept' THEN 1 ELSE 0 END) as concept_nodes,
    SUM(CASE WHEN type = 'skill' THEN 1 ELSE 0 END) as skill_nodes
FROM kg_knowledge_node
WHERE course_id = 9002;

-- 查看所有课程的节点统计
SELECT 
    course_id,
    COUNT(*) as total_nodes,
    SUM(CASE WHEN type = 'concept' THEN 1 ELSE 0 END) as concept_nodes,
    SUM(CASE WHEN type = 'skill' THEN 1 ELSE 0 END) as skill_nodes
FROM kg_knowledge_node
GROUP BY course_id
ORDER BY course_id;

-- 查看关系统计
SELECT 
    COUNT(*) as total_relations,
    relation_type,
    COUNT(*) as count
FROM kg_knowledge_relation
GROUP BY relation_type;


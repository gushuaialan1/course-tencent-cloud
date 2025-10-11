-- 知识图谱系统预设模板数据
-- 创建日期: 2025-10-11
-- 版本: 1.0

-- 清空现有系统模板
DELETE FROM kg_knowledge_graph_template WHERE is_system = 1;

-- 模板1: Web开发基础（18节点，25关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    'Web开发基础',
    'cs',
    '适用于前端开发、Web开发基础课程。包含HTML、CSS、JavaScript等核心知识点，采用层次布局。',
    '',
    '[
        {"id": 1, "name": "Web开发基础", "type": "topic", "description": "Web开发的基础知识体系", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "HTML", "type": "concept", "description": "超文本标记语言", "position_x": -200, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "CSS", "type": "concept", "description": "层叠样式表", "position_x": 0, "position_y": 100, "weight": 1.2},
        {"id": 4, "name": "JavaScript", "type": "concept", "description": "浏览器脚本语言", "position_x": 200, "position_y": 100, "weight": 1.3},
        {"id": 5, "name": "HTML标签", "type": "skill", "description": "常用HTML标签使用", "position_x": -300, "position_y": 200, "weight": 1.0},
        {"id": 6, "name": "HTML表单", "type": "skill", "description": "表单元素和提交", "position_x": -100, "position_y": 200, "weight": 1.0},
        {"id": 7, "name": "CSS选择器", "type": "skill", "description": "CSS选择器语法", "position_x": -50, "position_y": 200, "weight": 1.0},
        {"id": 8, "name": "CSS布局", "type": "skill", "description": "Flexbox和Grid布局", "position_x": 50, "position_y": 200, "weight": 1.1},
        {"id": 9, "name": "响应式设计", "type": "skill", "description": "移动端适配", "position_x": 0, "position_y": 300, "weight": 1.0},
        {"id": 10, "name": "JavaScript基础", "type": "skill", "description": "变量、类型、函数", "position_x": 150, "position_y": 200, "weight": 1.0},
        {"id": 11, "name": "DOM操作", "type": "skill", "description": "文档对象模型操作", "position_x": 250, "position_y": 200, "weight": 1.1},
        {"id": 12, "name": "事件处理", "type": "skill", "description": "事件监听和处理", "position_x": 300, "position_y": 300, "weight": 1.0},
        {"id": 13, "name": "AJAX", "type": "concept", "description": "异步请求", "position_x": 200, "position_y": 400, "weight": 1.0},
        {"id": 14, "name": "HTTP协议", "type": "concept", "description": "超文本传输协议", "position_x": -200, "position_y": 0, "weight": 1.0},
        {"id": 15, "name": "前端框架", "type": "topic", "description": "Vue/React等", "position_x": 400, "position_y": 200, "weight": 1.0},
        {"id": 16, "name": "浏览器工具", "type": "skill", "description": "DevTools调试", "position_x": 100, "position_y": 400, "weight": 1.0},
        {"id": 17, "name": "版本控制", "type": "skill", "description": "Git使用", "position_x": 0, "position_y": 500, "weight": 1.0},
        {"id": 18, "name": "Web安全", "type": "concept", "description": "XSS、CSRF防护", "position_x": -100, "position_y": 400, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 4, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 7, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 8, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 8, "to_node_id": 9, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 10, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 11, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 10, "to_node_id": 12, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 11, "to_node_id": 12, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 13, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 14, "to_node_id": 1, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 15, "relation_type": "suggests", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 3, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 4, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 11, "to_node_id": 16, "relation_type": "suggests", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 17, "relation_type": "suggests", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 18, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 18, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 13, "to_node_id": 18, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 7, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 10, "to_node_id": 11, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 12, "to_node_id": 13, "relation_type": "prerequisite", "weight": 1.0}
    ]',
    18, 25, 'beginner', 'Web,前端,HTML,CSS,JavaScript',
    1, 1, 0, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板2: 数据结构与算法（22节点，28关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '数据结构与算法',
    'cs',
    '适用于数据结构、算法课程。包含数组、链表、树、图等核心数据结构和常用算法。',
    '',
    '[
        {"id": 1, "name": "数据结构与算法", "type": "topic", "description": "计算机科学基础", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "线性结构", "type": "topic", "description": "顺序存储的数据结构", "position_x": -200, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "树形结构", "type": "topic", "description": "层次关系的数据结构", "position_x": 200, "position_y": 100, "weight": 1.2},
        {"id": 4, "name": "数组", "type": "concept", "description": "连续内存存储", "position_x": -300, "position_y": 200, "weight": 1.0},
        {"id": 5, "name": "链表", "type": "concept", "description": "节点链接存储", "position_x": -200, "position_y": 200, "weight": 1.0},
        {"id": 6, "name": "栈", "type": "concept", "description": "后进先出", "position_x": -100, "position_y": 200, "weight": 1.0},
        {"id": 7, "name": "队列", "type": "concept", "description": "先进先出", "position_x": -100, "position_y": 300, "weight": 1.0},
        {"id": 8, "name": "二叉树", "type": "concept", "description": "每个节点最多两个子节点", "position_x": 200, "position_y": 200, "weight": 1.0},
        {"id": 9, "name": "二叉搜索树", "type": "concept", "description": "有序二叉树", "position_x": 150, "position_y": 300, "weight": 1.0},
        {"id": 10, "name": "平衡树", "type": "concept", "description": "AVL、红黑树", "position_x": 250, "position_y": 300, "weight": 1.0},
        {"id": 11, "name": "堆", "type": "concept", "description": "完全二叉树", "position_x": 300, "position_y": 200, "weight": 1.0},
        {"id": 12, "name": "图", "type": "topic", "description": "节点和边的集合", "position_x": 0, "position_y": 100, "weight": 1.2},
        {"id": 13, "name": "图的存储", "type": "skill", "description": "邻接矩阵、邻接表", "position_x": 0, "position_y": 200, "weight": 1.0},
        {"id": 14, "name": "图的遍历", "type": "skill", "description": "DFS、BFS", "position_x": 50, "position_y": 300, "weight": 1.0},
        {"id": 15, "name": "排序算法", "type": "topic", "description": "数据排序方法", "position_x": -200, "position_y": 400, "weight": 1.2},
        {"id": 16, "name": "冒泡排序", "type": "skill", "description": "简单排序", "position_x": -300, "position_y": 500, "weight": 1.0},
        {"id": 17, "name": "快速排序", "type": "skill", "description": "分治排序", "position_x": -200, "position_y": 500, "weight": 1.0},
        {"id": 18, "name": "归并排序", "type": "skill", "description": "合并排序", "position_x": -100, "position_y": 500, "weight": 1.0},
        {"id": 19, "name": "查找算法", "type": "topic", "description": "数据查找方法", "position_x": 200, "position_y": 400, "weight": 1.2},
        {"id": 20, "name": "二分查找", "type": "skill", "description": "有序查找", "position_x": 150, "position_y": 500, "weight": 1.0},
        {"id": 21, "name": "哈希表", "type": "concept", "description": "键值对存储", "position_x": 250, "position_y": 500, "weight": 1.0},
        {"id": 22, "name": "动态规划", "type": "concept", "description": "最优子结构", "position_x": 0, "position_y": 600, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 12, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 4, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 7, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 8, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 11, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 8, "to_node_id": 9, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 9, "to_node_id": 10, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 12, "to_node_id": 13, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 13, "to_node_id": 14, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 15, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 15, "to_node_id": 16, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 15, "to_node_id": 17, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 15, "to_node_id": 18, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 15, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 19, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 19, "to_node_id": 20, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 19, "to_node_id": 21, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 20, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 6, "to_node_id": 14, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 7, "to_node_id": 14, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 22, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 15, "to_node_id": 22, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 19, "to_node_id": 22, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 11, "to_node_id": 15, "relation_type": "related", "weight": 1.0}
    ]',
    22, 28, 'intermediate', '数据结构,算法,编程,计算机',
    1, 1, 0, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板3: 数据库原理（16节点，20关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '数据库原理',
    'cs',
    '适用于数据库原理、SQL课程。包含关系模型、SQL、事务、索引等核心概念。',
    '',
    '[
        {"id": 1, "name": "数据库系统", "type": "topic", "description": "数据库管理系统", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "关系模型", "type": "concept", "description": "表格数据模型", "position_x": -200, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "SQL语言", "type": "concept", "description": "结构化查询语言", "position_x": 0, "position_y": 100, "weight": 1.2},
        {"id": 4, "name": "事务管理", "type": "concept", "description": "ACID特性", "position_x": 200, "position_y": 100, "weight": 1.2},
        {"id": 5, "name": "表和字段", "type": "skill", "description": "数据表设计", "position_x": -300, "position_y": 200, "weight": 1.0},
        {"id": 6, "name": "主键外键", "type": "skill", "description": "表关系设计", "position_x": -200, "position_y": 200, "weight": 1.0},
        {"id": 7, "name": "SELECT查询", "type": "skill", "description": "数据查询", "position_x": -100, "position_y": 200, "weight": 1.0},
        {"id": 8, "name": "JOIN连接", "type": "skill", "description": "多表查询", "position_x": 0, "position_y": 200, "weight": 1.0},
        {"id": 9, "name": "INSERT/UPDATE", "type": "skill", "description": "数据操作", "position_x": 100, "position_y": 200, "weight": 1.0},
        {"id": 10, "name": "事务隔离", "type": "skill", "description": "隔离级别", "position_x": 200, "position_y": 200, "weight": 1.0},
        {"id": 11, "name": "索引优化", "type": "concept", "description": "查询优化", "position_x": -100, "position_y": 300, "weight": 1.0},
        {"id": 12, "name": "范式理论", "type": "concept", "description": "数据库设计范式", "position_x": -300, "position_y": 300, "weight": 1.0},
        {"id": 13, "name": "存储引擎", "type": "concept", "description": "InnoDB、MyISAM", "position_x": 300, "position_y": 200, "weight": 1.0},
        {"id": 14, "name": "性能调优", "type": "skill", "description": "查询优化技巧", "position_x": 0, "position_y": 400, "weight": 1.0},
        {"id": 15, "name": "备份恢复", "type": "skill", "description": "数据安全", "position_x": 200, "position_y": 300, "weight": 1.0},
        {"id": 16, "name": "分布式数据库", "type": "concept", "description": "分库分表", "position_x": 100, "position_y": 400, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 4, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 12, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 7, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 8, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 9, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 10, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 7, "to_node_id": 8, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 11, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 13, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 11, "to_node_id": 14, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 8, "to_node_id": 14, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 15, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 16, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 6, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 6, "to_node_id": 7, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 10, "to_node_id": 14, "relation_type": "related", "weight": 1.0}
    ]',
    16, 20, 'intermediate', '数据库,SQL,MySQL,关系模型',
    1, 1, 0, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板4: 线性代数（12节点，15关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '线性代数',
    'math',
    '适用于线性代数课程。包含矩阵、向量、行列式、特征值等核心概念。',
    '',
    '[
        {"id": 1, "name": "线性代数", "type": "topic", "description": "向量空间理论", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "矩阵", "type": "concept", "description": "矩阵基础", "position_x": -150, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "向量", "type": "concept", "description": "向量运算", "position_x": 150, "position_y": 100, "weight": 1.2},
        {"id": 4, "name": "矩阵运算", "type": "skill", "description": "加减乘除", "position_x": -250, "position_y": 200, "weight": 1.0},
        {"id": 5, "name": "行列式", "type": "concept", "description": "矩阵的行列式", "position_x": -150, "position_y": 200, "weight": 1.0},
        {"id": 6, "name": "逆矩阵", "type": "concept", "description": "矩阵求逆", "position_x": -50, "position_y": 200, "weight": 1.0},
        {"id": 7, "name": "向量空间", "type": "concept", "description": "线性空间", "position_x": 150, "position_y": 200, "weight": 1.0},
        {"id": 8, "name": "线性相关", "type": "skill", "description": "向量线性关系", "position_x": 250, "position_y": 200, "weight": 1.0},
        {"id": 9, "name": "特征值", "type": "concept", "description": "特征值和特征向量", "position_x": -100, "position_y": 300, "weight": 1.0},
        {"id": 10, "name": "对角化", "type": "skill", "description": "矩阵对角化", "position_x": 0, "position_y": 300, "weight": 1.0},
        {"id": 11, "name": "线性变换", "type": "concept", "description": "空间变换", "position_x": 100, "position_y": 300, "weight": 1.0},
        {"id": 12, "name": "正交矩阵", "type": "concept", "description": "正交变换", "position_x": 200, "position_y": 300, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 4, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 7, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 8, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 5, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 6, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 9, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 9, "to_node_id": 10, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 11, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 11, "to_node_id": 12, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 7, "to_node_id": 11, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 6, "to_node_id": 10, "relation_type": "related", "weight": 1.0}
    ]',
    12, 15, 'intermediate', '线性代数,矩阵,向量,数学',
    1, 1, 0, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板5: 计算机网络（20节点，24关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '计算机网络',
    'cs',
    '适用于计算机网络课程。包含OSI模型、TCP/IP、路由、交换等核心概念。',
    '',
    '[
        {"id": 1, "name": "计算机网络", "type": "topic", "description": "网络通信基础", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "OSI模型", "type": "concept", "description": "七层网络模型", "position_x": -200, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "TCP/IP协议", "type": "concept", "description": "互联网协议族", "position_x": 200, "position_y": 100, "weight": 1.2},
        {"id": 4, "name": "物理层", "type": "concept", "description": "比特传输", "position_x": -300, "position_y": 200, "weight": 1.0},
        {"id": 5, "name": "数据链路层", "type": "concept", "description": "帧传输", "position_x": -300, "position_y": 280, "weight": 1.0},
        {"id": 6, "name": "网络层", "type": "concept", "description": "IP路由", "position_x": -300, "position_y": 360, "weight": 1.0},
        {"id": 7, "name": "传输层", "type": "concept", "description": "端到端通信", "position_x": -300, "position_y": 440, "weight": 1.0},
        {"id": 8, "name": "应用层", "type": "concept", "description": "应用协议", "position_x": -300, "position_y": 520, "weight": 1.0},
        {"id": 9, "name": "IP协议", "type": "skill", "description": "网际协议", "position_x": 150, "position_y": 200, "weight": 1.0},
        {"id": 10, "name": "TCP协议", "type": "skill", "description": "传输控制协议", "position_x": 250, "position_y": 200, "weight": 1.0},
        {"id": 11, "name": "UDP协议", "type": "skill", "description": "用户数据报协议", "position_x": 250, "position_y": 280, "weight": 1.0},
        {"id": 12, "name": "HTTP协议", "type": "skill", "description": "超文本传输协议", "position_x": 250, "position_y": 360, "weight": 1.0},
        {"id": 13, "name": "DNS", "type": "skill", "description": "域名解析", "position_x": 350, "position_y": 200, "weight": 1.0},
        {"id": 14, "name": "路由算法", "type": "skill", "description": "路径选择", "position_x": 0, "position_y": 300, "weight": 1.0},
        {"id": 15, "name": "交换技术", "type": "skill", "description": "数据交换", "position_x": -100, "position_y": 300, "weight": 1.0},
        {"id": 16, "name": "子网划分", "type": "skill", "description": "IP地址分配", "position_x": 50, "position_y": 200, "weight": 1.0},
        {"id": 17, "name": "网络安全", "type": "concept", "description": "加密和认证", "position_x": 100, "position_y": 400, "weight": 1.0},
        {"id": 18, "name": "防火墙", "type": "skill", "description": "安全防护", "position_x": 0, "position_y": 500, "weight": 1.0},
        {"id": 19, "name": "VPN", "type": "skill", "description": "虚拟专用网", "position_x": 100, "position_y": 500, "weight": 1.0},
        {"id": 20, "name": "无线网络", "type": "concept", "description": "WiFi技术", "position_x": -100, "position_y": 500, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 4, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 7, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 8, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 9, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 10, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 11, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 12, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 13, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 6, "to_node_id": 9, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 7, "to_node_id": 10, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 7, "to_node_id": 11, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 8, "to_node_id": 12, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 6, "to_node_id": 14, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 15, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 9, "to_node_id": 16, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 17, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 17, "to_node_id": 18, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 17, "to_node_id": 19, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 20, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 20, "relation_type": "related", "weight": 1.0}
    ]',
    20, 24, 'intermediate', '计算机网络,TCP/IP,协议,路由',
    1, 1, 0, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板6: 操作系统（18节点，22关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '操作系统',
    'cs',
    '适用于操作系统课程。包含进程、线程、内存管理、文件系统等核心概念。',
    '',
    '[
        {"id": 1, "name": "操作系统", "type": "topic", "description": "计算机系统软件", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "进程管理", "type": "topic", "description": "进程调度", "position_x": -200, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "内存管理", "type": "topic", "description": "内存分配", "position_x": 0, "position_y": 100, "weight": 1.2},
        {"id": 4, "name": "文件系统", "type": "topic", "description": "文件管理", "position_x": 200, "position_y": 100, "weight": 1.2},
        {"id": 5, "name": "进程", "type": "concept", "description": "程序执行实例", "position_x": -300, "position_y": 200, "weight": 1.0},
        {"id": 6, "name": "线程", "type": "concept", "description": "轻量级进程", "position_x": -200, "position_y": 200, "weight": 1.0},
        {"id": 7, "name": "进程调度", "type": "skill", "description": "CPU调度算法", "position_x": -250, "position_y": 300, "weight": 1.0},
        {"id": 8, "name": "进程同步", "type": "skill", "description": "互斥与同步", "position_x": -150, "position_y": 300, "weight": 1.0},
        {"id": 9, "name": "死锁", "type": "concept", "description": "资源死锁", "position_x": -200, "position_y": 400, "weight": 1.0},
        {"id": 10, "name": "虚拟内存", "type": "concept", "description": "内存虚拟化", "position_x": -50, "position_y": 200, "weight": 1.0},
        {"id": 11, "name": "分页", "type": "skill", "description": "页式管理", "position_x": 50, "position_y": 200, "weight": 1.0},
        {"id": 12, "name": "分段", "type": "skill", "description": "段式管理", "position_x": 0, "position_y": 300, "weight": 1.0},
        {"id": 13, "name": "页面置换", "type": "skill", "description": "置换算法", "position_x": 50, "position_y": 300, "weight": 1.0},
        {"id": 14, "name": "文件组织", "type": "skill", "description": "文件结构", "position_x": 200, "position_y": 200, "weight": 1.0},
        {"id": 15, "name": "目录结构", "type": "skill", "description": "目录管理", "position_x": 300, "position_y": 200, "weight": 1.0},
        {"id": 16, "name": "磁盘调度", "type": "skill", "description": "I/O调度", "position_x": 250, "position_y": 300, "weight": 1.0},
        {"id": 17, "name": "设备管理", "type": "concept", "description": "I/O设备", "position_x": 100, "position_y": 400, "weight": 1.0},
        {"id": 18, "name": "中断机制", "type": "concept", "description": "中断处理", "position_x": -100, "position_y": 500, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 4, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 7, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 8, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 8, "to_node_id": 9, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 10, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 11, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 12, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 10, "to_node_id": 13, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 14, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 15, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 4, "to_node_id": 16, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 17, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 17, "to_node_id": 16, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 18, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 6, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 11, "to_node_id": 12, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 7, "to_node_id": 18, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 8, "to_node_id": 18, "relation_type": "related", "weight": 1.0}
    ]',
    18, 22, 'advanced', '操作系统,进程,内存,文件系统',
    1, 1, 0, 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板7: 微积分基础（15节点，18关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '微积分基础',
    'math',
    '适用于高等数学、微积分课程。包含函数、极限、导数、积分等核心概念。',
    '',
    '[
        {"id": 1, "name": "微积分", "type": "topic", "description": "高等数学基础", "position_x": 0, "position_y": 0, "weight": 1.5},
        {"id": 2, "name": "函数", "type": "concept", "description": "函数基础", "position_x": 0, "position_y": 100, "weight": 1.2},
        {"id": 3, "name": "极限", "type": "concept", "description": "极限理论", "position_x": -150, "position_y": 200, "weight": 1.2},
        {"id": 4, "name": "连续性", "type": "concept", "description": "函数连续", "position_x": 150, "position_y": 200, "weight": 1.0},
        {"id": 5, "name": "导数", "type": "concept", "description": "微分学", "position_x": -150, "position_y": 300, "weight": 1.2},
        {"id": 6, "name": "求导法则", "type": "skill", "description": "导数运算", "position_x": -250, "position_y": 400, "weight": 1.0},
        {"id": 7, "name": "微分", "type": "concept", "description": "函数微分", "position_x": -50, "position_y": 400, "weight": 1.0},
        {"id": 8, "name": "导数应用", "type": "skill", "description": "极值、曲率", "position_x": -150, "position_y": 500, "weight": 1.0},
        {"id": 9, "name": "不定积分", "type": "concept", "description": "反导数", "position_x": 150, "position_y": 300, "weight": 1.2},
        {"id": 10, "name": "定积分", "type": "concept", "description": "积分计算", "position_x": 150, "position_y": 400, "weight": 1.2},
        {"id": 11, "name": "积分法则", "type": "skill", "description": "积分运算", "position_x": 250, "position_y": 400, "weight": 1.0},
        {"id": 12, "name": "积分应用", "type": "skill", "description": "面积、体积", "position_x": 150, "position_y": 500, "weight": 1.0},
        {"id": 13, "name": "级数", "type": "concept", "description": "无穷级数", "position_x": 0, "position_y": 600, "weight": 1.0},
        {"id": 14, "name": "泰勒展开", "type": "skill", "description": "函数展开", "position_x": -100, "position_y": 700, "weight": 1.0},
        {"id": 15, "name": "微分方程", "type": "concept", "description": "常微分方程", "position_x": 100, "position_y": 700, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 3, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 2, "to_node_id": 4, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 4, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 3, "to_node_id": 5, "relation_type": "prerequisite", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 5, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 6, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 7, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 8, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 9, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 9, "to_node_id": 10, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 10, "to_node_id": 11, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 10, "to_node_id": 12, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 9, "relation_type": "related", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 13, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 13, "to_node_id": 14, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 15, "relation_type": "extends", "weight": 1.0},
        {"from_node_id": 5, "to_node_id": 15, "relation_type": "related", "weight": 1.0}
    ]',
    15, 18, 'intermediate', '微积分,导数,积分,数学',
    1, 1, 0, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 模板8: 空白模板（3节点，2关系）
INSERT INTO kg_knowledge_graph_template (
    name, category, description, preview_image, node_data, relation_data,
    node_count, relation_count, difficulty_level, tags, is_system, is_active,
    usage_count, sort_order, created_by, create_time, update_time
) VALUES (
    '空白模板',
    'other',
    '适用于任意课程，从零开始构建知识图谱。包含3个示例节点供参考。',
    '',
    '[
        {"id": 1, "name": "核心概念", "type": "topic", "description": "主要知识点", "position_x": 0, "position_y": 0, "weight": 1.0},
        {"id": 2, "name": "基础知识", "type": "concept", "description": "基础概念", "position_x": -100, "position_y": 100, "weight": 1.0},
        {"id": 3, "name": "实践技能", "type": "skill", "description": "动手实践", "position_x": 100, "position_y": 100, "weight": 1.0}
    ]',
    '[
        {"from_node_id": 1, "to_node_id": 2, "relation_type": "contains", "weight": 1.0},
        {"from_node_id": 1, "to_node_id": 3, "relation_type": "contains", "weight": 1.0}
    ]',
    3, 2, 'beginner', '空白,自定义,通用',
    1, 1, 0, 999, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 查询插入结果
SELECT id, name, category, node_count, relation_count, difficulty_level 
FROM kg_knowledge_graph_template 
WHERE is_system = 1 
ORDER BY sort_order;


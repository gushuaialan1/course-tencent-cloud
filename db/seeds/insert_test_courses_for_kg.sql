-- =============================================
-- 知识图谱测试数据 - 课程与章节
-- =============================================
-- 说明：创建3个测试课程，每个课程包含章节和课时，用于测试知识图谱生成功能
-- 使用方法：在数据库中执行此SQL文件
-- =============================================

-- 清理可能存在的测试数据（course_id >= 9000的为测试数据）
DELETE FROM kg_chapter WHERE course_id >= 9000;
DELETE FROM kg_course WHERE id >= 9000;

-- =============================================
-- 测试课程1：Web前端开发入门
-- =============================================
INSERT INTO kg_course (
    id, category_id, teacher_id, title, cover, summary, details,
    model, level, attrs, market_price, vip_price, study_expiry,
    refund_expiry, published, deleted, create_time, update_time
) VALUES (
    9001, 1, 1, 'Web前端开发入门',
    '/static/images/course/web-dev.jpg',
    '从零开始学习Web前端开发，掌握HTML、CSS、JavaScript核心技术',
    '<p>本课程适合零基础学员，系统学习Web前端开发技术栈，包括HTML5、CSS3、JavaScript ES6+、前端工程化等内容。</p>',
    1, 1, '{"duration":0}', 199.00, 99.00, 365,
    7, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 课程1 - 章节（逐条插入以便正确获取ID）
-- 第1章：HTML基础
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9001, 0, 'HTML基础', '学习HTML标签和页面结构', 1, 1, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter1_id = LAST_INSERT_ID();

-- 第2章：CSS样式
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9001, 0, 'CSS样式与布局', '掌握CSS选择器和常用布局方式', 2, 1, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter2_id = LAST_INSERT_ID();

-- 第3章：JavaScript基础
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9001, 0, 'JavaScript基础', '学习JavaScript语法和DOM操作', 3, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter3_id = LAST_INSERT_ID();

-- 第4章：JavaScript进阶
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9001, 0, 'JavaScript进阶', '深入学习ES6+新特性和异步编程', 4, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter4_id = LAST_INSERT_ID();

-- 第5章：前端工程化
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9001, 0, '前端工程化', '学习Webpack、npm、Git等工具', 5, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter5_id = LAST_INSERT_ID();

-- 课程1 - 课时（子章节）
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
-- HTML基础 - 课时
(9001, @chapter1_id, 'HTML文档结构', '学习HTML5文档结构和语义化标签', 1, 1, 1, '{"duration":1200}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter1_id, '常用HTML标签', '学习表单、列表、表格等常用标签', 2, 1, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter1_id, 'HTML5新特性', '了解HTML5的新增标签和API', 3, 1, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- CSS样式 - 课时
(9001, @chapter2_id, 'CSS选择器', '掌握各种CSS选择器的使用', 1, 1, 1, '{"duration":1200}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter2_id, '盒模型与定位', '理解盒模型和元素定位', 2, 1, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter2_id, 'Flexbox布局', '学习Flexbox弹性布局', 3, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter2_id, 'Grid网格布局', '学习Grid网格布局系统', 4, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- JavaScript基础 - 课时
(9001, @chapter3_id, '变量与数据类型', '学习JavaScript基本语法', 1, 0, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter3_id, '函数与作用域', '理解函数和作用域机制', 2, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter3_id, 'DOM操作', '学习DOM节点操作和事件处理', 3, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- JavaScript进阶 - 课时
(9001, @chapter4_id, 'ES6箭头函数', '学习箭头函数和this指向', 1, 0, 1, '{"duration":1200}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter4_id, 'Promise与async', '掌握异步编程的各种方式', 2, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9001, @chapter4_id, '模块化开发', '学习ES6模块化语法', 3, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- =============================================
-- 测试课程2：Python数据分析
-- =============================================
INSERT INTO kg_course (
    id, category_id, teacher_id, title, cover, summary, details,
    model, level, attrs, market_price, vip_price, study_expiry,
    refund_expiry, published, deleted, create_time, update_time
) VALUES (
    9002, 2, 1, 'Python数据分析实战',
    '/static/images/course/python-data.jpg',
    '使用Python进行数据分析，掌握NumPy、Pandas、Matplotlib等核心库',
    '<p>本课程面向数据分析初学者，系统讲解Python数据分析的核心技术，包括数据处理、数据可视化、统计分析等内容。</p>',
    1, 2, '{"duration":0}', 299.00, 149.00, 365,
    7, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 课程2 - 章节（逐条插入以便正确获取ID）
-- 第1章：Python基础回顾
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9002, 0, 'Python基础回顾', '回顾Python基础语法', 1, 1, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter1_id = LAST_INSERT_ID();

-- 第2章：NumPy数值计算
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9002, 0, 'NumPy数值计算', '学习NumPy数组操作和数值计算', 2, 1, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter2_id = LAST_INSERT_ID();

-- 第3章：Pandas数据处理
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9002, 0, 'Pandas数据处理', '掌握Pandas数据分析核心功能', 3, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter3_id = LAST_INSERT_ID();

-- 第4章：数据可视化
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9002, 0, '数据可视化', '使用Matplotlib和Seaborn绘制图表', 4, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter4_id = LAST_INSERT_ID();

-- 第5章：实战项目
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9002, 0, '综合实战项目', '完成真实的数据分析项目', 5, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter5_id = LAST_INSERT_ID();

-- 课程2 - 课时
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
-- Python基础 - 课时
(9002, @chapter1_id, '列表与字典', '复习列表和字典的使用', 1, 1, 1, '{"duration":1200}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter1_id, '函数与类', '回顾函数和面向对象编程', 2, 1, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- NumPy - 课时
(9002, @chapter2_id, 'NumPy数组创建', '学习ndarray数组的创建方法', 1, 1, 1, '{"duration":1200}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter2_id, '数组运算', '掌握数组的各种运算操作', 2, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter2_id, '索引与切片', '学习数组的索引和切片技巧', 3, 0, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- Pandas - 课时
(9002, @chapter3_id, 'Series与DataFrame', '理解Pandas的核心数据结构', 1, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter3_id, '数据读取与写入', '学习读取CSV、Excel等文件', 2, 0, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter3_id, '数据清洗', '处理缺失值和重复数据', 3, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter3_id, '数据分组聚合', '使用groupby进行数据聚合', 4, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 可视化 - 课时
(9002, @chapter4_id, 'Matplotlib基础', '学习Matplotlib绘图基础', 1, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9002, @chapter4_id, 'Seaborn高级图表', '绘制各种统计图表', 2, 0, 1, '{"duration":2100}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- =============================================
-- 测试课程3：数据库设计与优化
-- =============================================
INSERT INTO kg_course (
    id, category_id, teacher_id, title, cover, summary, details,
    model, level, attrs, market_price, vip_price, study_expiry,
    refund_expiry, published, deleted, create_time, update_time
) VALUES (
    9003, 3, 1, '数据库设计与优化',
    '/static/images/course/database.jpg',
    '深入学习MySQL数据库设计、查询优化和性能调优',
    '<p>本课程深入讲解数据库设计原理、SQL查询优化、索引设计、事务处理等核心知识，适合有一定数据库基础的学员。</p>',
    1, 3, '{"duration":0}', 399.00, 199.00, 365,
    7, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
);

-- 课程3 - 章节（逐条插入以便正确获取ID）
-- 第1章：数据库设计基础
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9003, 0, '数据库设计基础', '学习数据库设计的基本原则', 1, 1, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter1_id = LAST_INSERT_ID();

-- 第2章：SQL查询
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9003, 0, 'SQL查询语句', '掌握各种SQL查询技巧', 2, 1, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter2_id = LAST_INSERT_ID();

-- 第3章：索引设计
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9003, 0, '索引设计与优化', '深入理解索引原理和优化方法', 3, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter3_id = LAST_INSERT_ID();

-- 第4章：事务与锁
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9003, 0, '事务与锁机制', '学习事务处理和并发控制', 4, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter4_id = LAST_INSERT_ID();

-- 第5章：性能优化
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9003, 0, '性能监控与调优', '掌握数据库性能优化技巧', 5, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter5_id = LAST_INSERT_ID();

-- 第6章：高可用架构
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
(9003, 0, '高可用架构设计', '学习主从复制和读写分离', 6, 0, 1, '{"duration":0}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @chapter6_id = LAST_INSERT_ID();

-- 课程3 - 课时
INSERT INTO kg_chapter (course_id, parent_id, title, summary, priority, free, model, attrs, published, deleted, create_time, update_time) VALUES
-- 数据库设计 - 课时
(9003, @chapter1_id, 'ER图设计', '学习实体关系图的绘制', 1, 1, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter1_id, '范式理论', '理解数据库范式化设计', 2, 1, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter1_id, '表结构设计', '设计合理的表结构', 3, 1, 1, '{"duration":2100}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- SQL查询 - 课时
(9003, @chapter2_id, '基础查询', '学习SELECT、WHERE等基本语句', 1, 1, 1, '{"duration":1200}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter2_id, '多表连接', '掌握JOIN的各种用法', 2, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter2_id, '子查询', '学习子查询和派生表', 3, 0, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter2_id, '聚合与分组', '使用GROUP BY和聚合函数', 4, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 索引设计 - 课时
(9003, @chapter3_id, '索引原理', '理解B+树索引结构', 1, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter3_id, '索引创建策略', '选择合适的索引类型', 2, 0, 1, '{"duration":2100}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter3_id, '索引失效场景', '避免索引失效的常见错误', 3, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 事务与锁 - 课时
(9003, @chapter4_id, 'ACID特性', '理解事务的四大特性', 1, 0, 1, '{"duration":1500}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter4_id, '隔离级别', '掌握四种事务隔离级别', 2, 0, 1, '{"duration":1800}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter4_id, '锁机制', '理解行锁、表锁、间隙锁', 3, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),

-- 性能优化 - 课时
(9003, @chapter5_id, 'EXPLAIN分析', '使用EXPLAIN分析查询性能', 1, 0, 1, '{"duration":2400}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9003, @chapter5_id, '慢查询优化', '定位和优化慢查询', 2, 0, 1, '{"duration":2700}', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());


-- =============================================
-- 插入成功提示
-- =============================================
SELECT '测试数据创建完成！' AS message;
SELECT CONCAT('课程ID: 9001, 9002, 9003') AS course_info;
SELECT CONCAT('课程1: ', COUNT(*), '个章节') AS chapter_count FROM kg_chapter WHERE course_id = 9001;
SELECT CONCAT('课程2: ', COUNT(*), '个章节') AS chapter_count FROM kg_chapter WHERE course_id = 9002;
SELECT CONCAT('课程3: ', COUNT(*), '个章节') AS chapter_count FROM kg_chapter WHERE course_id = 9003;

-- =============================================
-- 使用说明
-- =============================================
-- 1. 访问知识图谱编辑器：/admin/knowledge-graph/editor/9001
-- 2. 访问知识图谱编辑器：/admin/knowledge-graph/editor/9002
-- 3. 访问知识图谱编辑器：/admin/knowledge-graph/editor/9003
-- 4. 点击"从章节生成"按钮即可生成知识图谱
-- =============================================


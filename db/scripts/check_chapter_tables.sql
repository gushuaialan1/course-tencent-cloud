-- 检查章节相关的表
-- 诊断为什么知识图谱系统找不到课程章节

-- 1. 查看所有章节相关的表
SHOW TABLES LIKE '%chapter%';

-- 2. 查看 kg_chapter 表的数据（知识图谱系统使用的表）
SELECT 
    'kg_chapter表数据' as info,
    id,
    course_id,
    title,
    parent_id,
    published
FROM kg_chapter
WHERE course_id = 1
LIMIT 10;

-- 3. 如果存在其他章节表（如 course_chapter），查看其数据
-- SELECT 
--     '原项目章节表' as info,
--     id,
--     course_id,
--     title,
--     parent_id,
--     published
-- FROM course_chapter
-- WHERE course_id = 1
-- LIMIT 10;

-- 4. 查看课程ID=1的章节数据在kg_chapter中是否存在
SELECT 
    '课程1在kg_chapter中的章节统计' as info,
    course_id,
    COUNT(*) as total,
    SUM(CASE WHEN parent_id = 0 THEN 1 ELSE 0 END) as top_level,
    SUM(CASE WHEN parent_id > 0 THEN 1 ELSE 0 END) as sub_chapters,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_count
FROM kg_chapter
WHERE course_id = 1
GROUP BY course_id;

-- 5. 查看所有课程在kg_chapter中的章节统计
SELECT 
    '所有课程在kg_chapter中的章节统计' as info,
    course_id,
    COUNT(*) as total_chapters
FROM kg_chapter
GROUP BY course_id
ORDER BY course_id;


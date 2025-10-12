-- 将测试课程的章节设为已发布
-- 这样知识图谱生成器就能找到这些章节了

-- 备份当前状态（可选）
-- CREATE TABLE kg_chapter_backup_20251012 AS 
-- SELECT * FROM kg_chapter WHERE course_id IN (1, 9001);

-- 查看修复前的状态
SELECT 
    'Course 1 - 修复前' as info,
    course_id,
    COUNT(*) as total,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished_count
FROM kg_chapter
WHERE course_id = 1
GROUP BY course_id;

-- 修复：将课程1的所有未删除章节设为已发布
UPDATE kg_chapter 
SET published = 1 
WHERE course_id = 1 
  AND deleted = 0;

-- 查看修复后的状态
SELECT 
    'Course 1 - 修复后' as info,
    course_id,
    COUNT(*) as total,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished_count
FROM kg_chapter
WHERE course_id = 1
GROUP BY course_id;

-- 显示详细章节列表
SELECT 
    'Course 1 章节列表' as info,
    id,
    title,
    parent_id,
    published,
    priority
FROM kg_chapter 
WHERE course_id = 1 
  AND deleted = 0
ORDER BY parent_id, priority;


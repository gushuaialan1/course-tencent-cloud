-- ============================================
-- 章节发布状态修复脚本（可选）
-- ============================================
-- 
-- 说明：
-- 1. 原项目章节管理没有发布控制界面
-- 2. 知识图谱生成器已修改为不过滤 published 字段
-- 3. 此脚本用于将现有章节统一设为已发布状态（可选操作）
--
-- 注意：执行前请备份数据！
-- ============================================

-- 查看修复前的状态
SELECT 
    '修复前统计' as info,
    COUNT(*) as total_chapters,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished_count,
    SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) as deleted_count
FROM kg_chapter;

-- 查看各课程的章节状态
SELECT 
    '各课程章节状态' as info,
    course_id,
    COUNT(*) as total,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished,
    SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) as deleted
FROM kg_chapter
GROUP BY course_id
ORDER BY course_id;

-- 修复：将所有未删除的章节设为已发布
-- 可以按需调整 WHERE 条件，比如只修复特定课程
UPDATE kg_chapter 
SET published = 1 
WHERE deleted = 0;

-- 查看修复后的状态
SELECT 
    '修复后统计' as info,
    COUNT(*) as total_chapters,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published_count,
    SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished_count,
    SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) as deleted_count
FROM kg_chapter;

-- 查看修复后各课程的章节状态
SELECT 
    '修复后各课程章节状态' as info,
    course_id,
    COUNT(*) as total,
    SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END) as published,
    SUM(CASE WHEN published = 0 THEN 1 ELSE 0 END) as unpublished,
    SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) as deleted
FROM kg_chapter
GROUP BY course_id
ORDER BY course_id;

-- 显示课程1的详细章节列表（验证）
SELECT 
    '课程1章节详情' as info,
    id,
    parent_id,
    title,
    published,
    deleted,
    priority
FROM kg_chapter 
WHERE course_id = 1
ORDER BY parent_id, priority;


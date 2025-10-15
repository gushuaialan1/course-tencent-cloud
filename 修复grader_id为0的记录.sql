-- 修复 kg_assignment_submission 表中 grader_id = 0 的记录
-- 将 grader_id = 0 更新为 NULL，避免外键约束错误
-- 执行时间：2025-10-15

-- 1. 查看需要修复的记录数量
SELECT COUNT(*) as need_fix_count 
FROM kg_assignment_submission 
WHERE grader_id = 0 
  AND delete_time = 0;

-- 2. 查看这些记录的详情（可选，用于确认）
SELECT 
    id,
    assignment_id,
    user_id,
    status,
    grade_status,
    grader_id,
    create_time,
    update_time
FROM kg_assignment_submission 
WHERE grader_id = 0 
  AND delete_time = 0
LIMIT 20;

-- 3. 执行修复：将 grader_id = 0 更新为 NULL
UPDATE kg_assignment_submission 
SET grader_id = NULL,
    update_time = UNIX_TIMESTAMP()
WHERE grader_id = 0 
  AND delete_time = 0;

-- 4. 验证修复结果
SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN grader_id IS NULL THEN 1 ELSE 0 END) as null_count,
    SUM(CASE WHEN grader_id = 0 THEN 1 ELSE 0 END) as zero_count,
    SUM(CASE WHEN grader_id > 0 THEN 1 ELSE 0 END) as valid_grader_count
FROM kg_assignment_submission 
WHERE delete_time = 0;

-- 5. 查看各状态下的 grader_id 分布
SELECT 
    status,
    grade_status,
    COUNT(*) as count,
    SUM(CASE WHEN grader_id IS NULL THEN 1 ELSE 0 END) as null_grader,
    SUM(CASE WHEN grader_id > 0 THEN 1 ELSE 0 END) as has_grader
FROM kg_assignment_submission 
WHERE delete_time = 0
GROUP BY status, grade_status
ORDER BY status, grade_status;


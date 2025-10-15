-- 选择性删除作业提交记录

-- 方式1: 删除指定用户的所有提交
-- DELETE FROM kg_assignment_submission 
-- WHERE user_id = 你的测试用户ID;

-- 方式2: 删除指定作业的所有提交
-- DELETE FROM kg_assignment_submission 
-- WHERE assignment_id = 作业ID;

-- 方式3: 删除指定时间之后的所有提交
-- DELETE FROM kg_assignment_submission 
-- WHERE submit_time > UNIX_TIMESTAMP('2025-10-15 00:00:00');

-- 方式4: 只删除草稿状态的提交
DELETE FROM kg_assignment_submission 
WHERE status = 'draft' AND delete_time = 0;

-- 查看删除后的结果
SELECT 
    status,
    grade_status,
    COUNT(*) as count
FROM kg_assignment_submission
WHERE delete_time = 0
GROUP BY status, grade_status;


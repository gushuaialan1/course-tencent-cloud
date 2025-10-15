-- 重新评分已提交的作业
-- 此脚本将已评分的作业重置为"待评分"状态，然后可以通过后台批量自动评分

-- 开始事务
START TRANSACTION;

-- 1. 查看需要重新评分的提交（预览）
SELECT 
    s.id,
    a.title,
    s.user_id,
    s.status,
    s.grade_status,
    s.score,
    a.grade_mode
FROM kg_assignment_submission s
JOIN kg_assignment a ON s.assignment_id = a.id
WHERE s.delete_time = 0
  AND s.status = 'graded'
  AND a.delete_time = 0
  AND a.grade_mode IN ('auto', 'mixed'); -- 只重新评分自动/混合模式的

-- 2. 重置为待评分状态（将已评分的改为已提交状态）
UPDATE kg_assignment_submission s
JOIN kg_assignment a ON s.assignment_id = a.id
SET 
    s.status = 'submitted',
    s.grade_status = 'pending',
    s.score = NULL,
    s.grade_time = 0,
    s.grade_details = '[]'
WHERE s.delete_time = 0
  AND s.status = 'graded'
  AND a.delete_time = 0
  AND a.grade_mode IN ('auto', 'mixed');

-- 3. 查看重置后的结果
SELECT 
    status,
    grade_status,
    COUNT(*) as count
FROM kg_assignment_submission
WHERE delete_time = 0
GROUP BY status, grade_status;

-- 提交事务
COMMIT;

-- 说明：重置后，可以在后台使用"批量自动评分"功能重新评分


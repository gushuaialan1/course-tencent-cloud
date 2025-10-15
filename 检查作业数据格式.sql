-- 检查作业数据格式SQL

-- 1. 查看所有作业的题目数据格式（检查correct_answer格式）
SELECT 
    id,
    title,
    grade_mode,
    JSON_EXTRACT(content, '$[0].type') as first_question_type,
    JSON_EXTRACT(content, '$[0].correct_answer') as first_correct_answer,
    JSON_EXTRACT(content, '$[0].multiple') as is_multiple,
    content
FROM kg_assignment 
WHERE delete_time = 0 
  AND status = 'published'
ORDER BY id DESC 
LIMIT 10;

-- 2. 查看学生提交的答案格式
SELECT 
    s.id,
    s.assignment_id,
    s.user_id,
    s.status,
    s.grade_status,
    s.score,
    JSON_EXTRACT(s.content, '$') as student_answers,
    s.submit_time,
    s.grade_time
FROM kg_assignment_submission s
WHERE s.delete_time = 0
ORDER BY s.id DESC
LIMIT 10;

-- 3. 检查是否有已评分但可能错误的提交（需要重新评分）
SELECT 
    s.id,
    s.assignment_id,
    a.title as assignment_title,
    s.user_id,
    s.status,
    s.grade_status,
    s.score,
    a.max_score,
    s.grade_time,
    a.grade_mode
FROM kg_assignment_submission s
JOIN kg_assignment a ON s.assignment_id = a.id
WHERE s.delete_time = 0
  AND s.status = 'graded'
  AND a.delete_time = 0
ORDER BY s.grade_time DESC
LIMIT 20;

-- 4. 统计各种状态的提交
SELECT 
    status,
    grade_status,
    COUNT(*) as count
FROM kg_assignment_submission
WHERE delete_time = 0
GROUP BY status, grade_status;


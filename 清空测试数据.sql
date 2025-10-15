-- ⚠️ 警告：此脚本会删除所有作业和提交数据，仅用于测试环境！
-- 生产环境请勿使用！

-- 开始事务
START TRANSACTION;

-- 1. 删除所有作业提交记录
DELETE FROM kg_assignment_submission;

-- 2. 删除所有作业
DELETE FROM kg_assignment;

-- 3. 重置自增ID（可选）
ALTER TABLE kg_assignment_submission AUTO_INCREMENT = 1;
ALTER TABLE kg_assignment AUTO_INCREMENT = 1;

-- 确认删除结果
SELECT '作业提交记录' as table_name, COUNT(*) as count FROM kg_assignment_submission
UNION ALL
SELECT '作业记录', COUNT(*) FROM kg_assignment;

-- 提交事务（执行后数据才真正删除）
COMMIT;

-- 如果要回滚，执行：ROLLBACK;


# 作业模块数据迁移指南

## ⚠️ 重要提醒

**在执行迁移前，务必完整备份数据库！**

```bash
# 备份数据库
mysqldump -u用户名 -p 数据库名 > backup_$(date +%Y%m%d_%H%M%S).sql
```

## 迁移步骤

### 1. 备份数据库

```bash
mysqldump -u用户名 -p kg_classroom > backup_before_migration.sql
```

### 2. 执行迁移脚本

迁移脚本使用Phinx工具执行。按照以下顺序执行：

#### 步骤1：迁移题目数据

```bash
cd /path/to/course-tencent-cloud
php vendor/bin/phinx migrate -t 20241015000001
```

此步骤将：
- 将题目类型从 `type: "choice" + multiple: true/false` 转换为 `type: "single_choice"/"multiple_choice"`
- 确保所有题目包裹在 `questions` 数组中
- 标准化选项格式为对象数组

#### 步骤2：迁移答案数据

```bash
php vendor/bin/phinx migrate -t 20241015000002
```

此步骤将：
- 将答案数据包裹到 `answers` 键中
- 保持单选题答案为字符串，多选题答案为数组

#### 步骤3：迁移状态数据

```bash
php vendor/bin/phinx migrate -t 20241015000003
```

此步骤将：
- 将双状态字段（`status` + `grade_status`）合并为单一 `status` 字段
- 设置 `grade_status` 为 NULL（保留字段用于兼容，但不再使用）

### 3. 验证迁移结果

#### 验证题目数据

```sql
-- 检查题目格式
SELECT id, title, JSON_EXTRACT(content, '$.questions') as questions 
FROM kg_assignment 
LIMIT 5;

-- 检查题目类型
SELECT id, 
       JSON_EXTRACT(content, '$.questions[0].type') as first_question_type 
FROM kg_assignment 
WHERE delete_time = 0;
```

#### 验证答案数据

```sql
-- 检查答案格式
SELECT id, JSON_EXTRACT(content, '$.answers') as answers 
FROM kg_assignment_submission 
LIMIT 5;
```

#### 验证状态数据

```sql
-- 检查状态分布
SELECT status, COUNT(*) as count 
FROM kg_assignment_submission 
WHERE delete_time = 0 
GROUP BY status;
```

### 4. 回滚方案

如果迁移失败，从备份恢复：

```bash
mysql -u用户名 -p 数据库名 < backup_before_migration.sql
```

## 常见问题

### Q1: 迁移脚本执行失败怎么办？

A: 
1. 检查错误日志
2. 确认数据库连接正常
3. 从备份恢复数据库
4. 联系开发团队

### Q2: 如何验证迁移是否成功？

A:
1. 检查迁移脚本输出的统计信息
2. 执行上述SQL验证查询
3. 在测试环境完整测试功能

### Q3: 迁移需要多长时间？

A: 取决于数据量：
- 100个作业 + 1000个提交：约1-2分钟
- 1000个作业 + 10000个提交：约5-10分钟

### Q4: 迁移过程中可以访问系统吗？

A: **不可以**。迁移期间建议：
1. 设置维护模式
2. 停止所有服务
3. 执行迁移
4. 验证完成后恢复服务

## 版本信息

- **迁移版本**: 2.0
- **创建时间**: 2025-10-15
- **兼容版本**: 酷瓜云课堂 v1.x

## 联系支持

如有问题，请查看项目文档或联系开发团队。


# 数据库迁移与备份脚本使用指南

## 📋 脚本清单

| 脚本文件 | 功能说明 |
|---------|---------|
| `backup_database.sh` | 备份数据库 |
| `restore_database.sh` | 恢复数据库 |
| `run_assignment_migrations.sh` | 执行作业模块数据迁移 |
| `check_migration_status.sh` | 检查迁移状态 |

---

## 🗄️ 数据库配置

当前配置（从 `config/config.php` 读取）：

```
主机: localhost
端口: 3306
数据库: kugua_mooc
用户名: kugua_user
密码: 67GEyZ56k2n6HwsB
字符集: utf8mb4
```

---

## 📖 使用说明

### 1️⃣ 备份数据库

```bash
cd /path/to/course-tencent-cloud/db/scripts
bash backup_database.sh
```

**功能**：
- 自动备份完整数据库
- 压缩为 `.gz` 格式节省空间
- 自动清理7天前的旧备份

**备份位置**：`storage/backup/kugua_mooc_backup_YYYYMMDD_HHMMSS.sql.gz`

---

### 2️⃣ 检查迁移状态

在执行迁移前，先检查当前数据状态：

```bash
cd /path/to/course-tencent-cloud/db/scripts
bash check_migration_status.sh
```

**输出信息**：
- 作业总数 + 新旧格式分布
- 提交总数 + 新旧格式分布
- 状态字段使用情况
- 评分详情格式

---

### 3️⃣ 执行作业模块迁移

⚠️ **执行前必读**：
- 必须先执行备份！
- 建议在低峰期执行
- 整个过程需要3-10分钟（取决于数据量）

```bash
cd /path/to/course-tencent-cloud/db/scripts
bash run_assignment_migrations.sh
```

**执行流程**：

```
步骤 1/5: 备份数据库
         ↓
步骤 2/5: 检查当前数据（显示统计）
         ↓
步骤 3/5: 迁移作业题目格式 (questions)
         ↓
步骤 4/5: 迁移学生答案格式 (answers)
         ↓
步骤 5/5: 迁移提交状态字段 (status)
         ↓
       验证结果
```

**迁移内容**：

| 迁移项 | 旧格式 | 新格式 |
|-------|-------|-------|
| 作业题目 | `content: [{...}, {...}]` | `content: {questions: [{...}]}` |
| 学生答案 | `content: {"q1": "A", ...}` | `content: {answers: {"q1": "A"}}` |
| 提交状态 | `status + grade_status` | 简化为单个 `status` |
| 评分详情 | 自由格式 | `{grading: {...}, summary: {...}}` |

---

### 4️⃣ 恢复数据库（回滚）

如果迁移失败或需要回滚：

```bash
cd /path/to/course-tencent-cloud/db/scripts

# 查看可用备份
bash restore_database.sh

# 恢复指定备份
bash restore_database.sh /path/to/backup_file.sql.gz
```

**示例**：
```bash
bash restore_database.sh ../../storage/backup/kugua_mooc_backup_20250115_120000.sql.gz
```

---

## ⚡ 快速上手（完整流程）

```bash
# 1. 进入脚本目录
cd /path/to/course-tencent-cloud/db/scripts

# 2. 检查当前状态
bash check_migration_status.sh

# 3. 执行迁移（包含自动备份）
bash run_assignment_migrations.sh

# 4. 再次检查确认
bash check_migration_status.sh
```

---

## 🛡️ 安全建议

1. **生产环境迁移前**：
   - ✅ 在测试环境完整验证
   - ✅ 选择低峰期（凌晨2-6点）
   - ✅ 通知用户维护时间
   - ✅ 准备回滚预案

2. **备份策略**：
   - ✅ 迁移前手动备份
   - ✅ 保留至少3天的备份
   - ✅ 测试备份恢复流程

3. **权限检查**：
   ```bash
   # 确保MySQL用户有足够权限
   mysql -h localhost -u kugua_user -p67GEyZ56k2n6HwsB -e "SHOW GRANTS;"
   ```

---

## 📊 迁移预计时间

| 数据量 | 预计耗时 |
|-------|---------|
| < 100条作业 | 1-2分钟 |
| 100-1000条作业 | 3-5分钟 |
| > 1000条作业 | 5-10分钟 |

---

## ❓ 常见问题

### Q1: 迁移失败怎么办？
```bash
# 立即恢复备份
bash restore_database.sh [最新备份文件]
```

### Q2: 如何只迁移部分数据？
```bash
# 编辑迁移PHP文件，添加WHERE条件
# 例如：WHERE id > 100 AND id < 200
```

### Q3: 迁移后旧代码还能用吗？
**部分兼容**：
- ✅ 读取操作向后兼容（Model层已处理）
- ⚠️  直接写入旧格式会导致验证失败
- 建议：迁移后使用新Service层

### Q4: 如何验证迁移结果？
```bash
# 方法1：使用检查脚本
bash check_migration_status.sh

# 方法2：手动查询
mysql -h localhost -u kugua_user -p -D kugua_mooc -e "
    SELECT id, title, 
           JSON_EXTRACT(content, '$.questions[0].title') as first_question
    FROM kg_assignment 
    LIMIT 3;
"
```

---

## 🔧 故障排除

### 错误1: 连接数据库失败
```
ERROR 1045 (28000): Access denied
```
**解决**：检查 `config/config.php` 中的数据库密码

### 错误2: JSON格式无效
```
Invalid JSON in content field
```
**解决**：查看迁移日志，找到问题记录的ID，手动修复

### 错误3: 权限不足
```
ERROR 1227 (42000): Access denied; you need SUPER privilege
```
**解决**：使用root用户执行或授予相应权限

---

## 📞 技术支持

如遇到问题，请提供：
1. 错误日志截图
2. `check_migration_status.sh` 的输出
3. 数据量统计（作业数、提交数）

---

**最后更新**: 2025-01-15  
**适用版本**: 作业模块重构 v2.0


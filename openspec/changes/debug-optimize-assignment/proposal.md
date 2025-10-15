# Debug and Optimize Assignment Module

## Why

作业模块在前期开发中经历了25次修复，主要问题是**字段命名不统一**导致的各种bug（如`total_score` vs `max_score`、`deadline` vs `due_date`等）。虽然已经创建了字段规范文档，但现有代码中仍可能存在：

1. **遗留的旧字段名调用**：部分Service、Controller、View可能仍在使用废弃字段
2. **数据不一致问题**：JSON字段处理不规范、状态字段使用混乱
3. **潜在的连锁bug**：字段名不统一导致的数据无法正确读写
4. **代码质量问题**：重复代码、缺少错误处理、注释不足

本次变更旨在**系统性地排查和优化**作业模块，确保：
- 所有代码统一使用标准字段名
- JSON字段处理规范
- 状态管理逻辑清晰
- 代码质量达标

## What Changes

### 🔴 **工作原则** 
- ⚠️ **先规范，后编码**：必须先完成前后台规范统一审查，确保所有规范明确后再开始代码修改
- ⚠️ **线上测试**：项目部署在服务器，需要Git提交后在线上测试
- ⚠️ **谨慎提交**：每次提交前充分审查，避免频繁提交-测试循环

### 1. 前后台规范统一审查 ✅ **第一优先级 - 必须先完成**
- [ ] 审查和文档化题目数据结构（`Assignment.content`）
- [ ] 审查和文档化答案数据结构（`AssignmentSubmission.content`）
- [ ] 统一前端参数命名（禁止驼峰，使用snake_case）
- [ ] 统一后端参数命名（废弃字段全部替换）
- [ ] 统一题目解析逻辑（兼容新旧格式）
- [ ] 明确自动评分规则（只对选择题有效，单选/多选差异）
- [ ] 输出前后台数据规范对照表

### 2. 代码审计与字段统一 ✅ **核心任务**
- [ ] 审计所有作业相关的Model、Repo、Service、Controller、View
- [ ] 将所有废弃字段替换为标准字段：
  - ~~`total_score`~~ → `max_score`
  - ~~`deadline`~~ → `due_date`
  - ~~`allow_resubmit`~~ → `allow_late`
  - ~~`answers`~~ → `content`
  - ~~`submitted_at`~~ → `submit_time`
  - ~~`graded_at`~~ → `grade_time`
  - ~~`deleted`~~ → `delete_time > 0`
- [ ] 确保所有JSON字段使用`setXxxData()`/`getXxxData()`方法

### 3. 自动评分规则优化 ⚠️ **重要调整**
- [ ] **只对选择题（单选+多选）进行自动评分**
- [ ] 简答题（`essay`）禁止自动评分，必须人工批改
- [ ] 编程题（`code`）禁止自动评分，必须人工批改
- [ ] 文件上传题（`file_upload`）禁止自动评分，必须人工批改
- [ ] 单选题和多选题评分逻辑差异：
  - 单选题：答案类型为字符串 `"A"`，严格相等比较
  - 多选题：答案类型为数组 `["A", "C"]`，数组完全相等比较
  - 多选题不支持部分得分

### 4. JSON字段处理规范化
- [ ] 审计所有JSON字段的读写操作
- [ ] 确保空JSON字段初始化为`[]`而不是`''`或`null`
- [ ] 避免`toArray()`后重复`json_decode()`

### 5. 状态管理优化
- [ ] 统一`status`和`grade_status`的使用逻辑
- [ ] 确保批改模式（auto/manual/mixed）正确设置`grader_id`
- [ ] 前端状态显示逻辑优化（区分"批改中"和"已批改"）

### 6. 错误处理与日志
- [ ] 添加关键操作的错误处理
- [ ] 添加详细的错误日志
- [ ] 添加数据验证失败的友好提示

### 7. 代码优化与重构
- [ ] 消除重复代码
- [ ] 添加必要的代码注释
- [ ] 优化查询性能（添加索引、减少N+1查询）

### 8. 创建规范文档
- [ ] 为作业模块创建OpenSpec规范（`specs/assignment/spec.md`）
- [ ] 记录所有业务规则和数据结构
- [ ] 作为未来开发的参考标准

## Impact

### 影响的Capabilities
- **assignment** - 新增OpenSpec规范（目前不存在）

### 影响的代码文件

#### 核心Models (2个)
- `app/Models/Assignment.php`
- `app/Models/AssignmentSubmission.php`

#### Repositories (2个)
- `app/Repos/Assignment.php`
- `app/Repos/AssignmentSubmission.php`

#### Services (10+个)
- `app/Services/Logic/Assignment/AssignmentSubmit.php`
- `app/Services/Logic/Assignment/SubmissionDraft.php`
- `app/Services/Logic/Assignment/AutoGrade.php`
- `app/Services/Logic/Assignment/AssignmentInfo.php`
- `app/Services/Logic/Assignment/SubmissionResult.php`
- `app/Services/Logic/Assignment/AssignmentList.php` (Admin)
- `app/Services/Logic/Course/AssignmentList.php` (前台课程作业列表)
- `app/Services/Logic/User/Console/AssignmentList.php` (个人中心)
- `app/Services/Logic/Notice/Internal/AssignmentPublished.php`
- `app/Services/Logic/Notice/Internal/AssignmentGraded.php`

#### Controllers (3个)
- `app/Http/Admin/Controllers/AssignmentController.php`
- `app/Http/Admin/Controllers/AssignmentSubmissionController.php`
- `app/Http/Home/Controllers/AssignmentController.php`

#### Validators (5+个)
- `app/Validators/Assignment*.php` (所有作业相关验证器)

#### Views (10+个)
- `app/Http/Admin/Views/assignment/*.volt`
- `app/Http/Home/Views/assignment/*.volt`
- `app/Http/Home/Views/course/assignments.volt`
- `app/Http/Home/Views/user/console/assignments.volt`

#### JavaScript (3+个)
- `public/static/admin/js/assignment.*.js`
- `public/static/home/js/assignment.*.js`

### 预期收益
1. **稳定性提升** - 消除字段不一致导致的潜在bug
2. **可维护性提升** - 代码规范统一，易于理解和维护
3. **开发效率提升** - 有明确的规范文档作为参考
4. **用户体验提升** - 错误提示更友好，状态显示更清晰

### 风险评估
- **低风险** - 主要是代码审计和字段名替换，不改变业务逻辑
- **测试覆盖** - 需要完整的回归测试（创建、编辑、提交、批改、查看成绩）
- **回滚方案** - Git版本控制，可快速回滚

---

**提案创建时间**: 2025-10-15  
**预计完成时间**: 1-2天  
**优先级**: 高 ⚠️


# Implementation Tasks

## 📋 重要说明

**部署环境**: 项目部署在线上服务器，不在本地  
**测试流程**: 修改代码 → Git提交 → 线上同步 → 测试验证  
**工作原则**: ⚠️ **先完成规范统一审查，再开始编码**  

---

## 1. 准备工作
- [x] 1.1 阅读作业模块字段规范文档
- [x] 1.2 阅读最新的测试报告（作业模块验收测试报告.md）
- [x] 1.3 创建OpenSpec变更提案
- [x] 1.4 创建Git开发分支 `bugfix/debug-optimize-assignment`
- [x] 1.5 备份线上数据库 ⚠️ (已提醒用户)

---

## 2. 前后台规范统一审查 ⚠️ **核心任务 - 必须先完成**

### 2.1 题目数据结构规范审查
- [x] 2.1.1 确认 `Assignment.content` 字段的JSON结构（题目数组格式）
- [x] 2.1.2 确认单选题和多选题的区分方式（`type: "choice"` + `multiple: true/false`）
- [x] 2.1.3 确认题目类型枚举值：`choice`, `essay`, `code`, `file_upload`
- [x] 2.1.4 文档化标准题目对象的所有字段和数据类型
- [x] 2.1.5 确认 `options` 字段的格式（对象 `{A: "选项A"}` 或数组）

### 2.2 学生答案数据结构规范审查
- [x] 2.2.1 确认 `AssignmentSubmission.content` 字段的JSON结构（题目ID → 答案）
- [x] 2.2.2 确认单选题答案格式：**字符串** `"A"` （不是数组）
- [x] 2.2.3 确认多选题答案格式：**数组** `["A", "C"]` （不是字符串）
- [x] 2.2.4 确认简答题答案格式：字符串（文本内容）
- [x] 2.2.5 文档化前端提交和后端存储的答案结构差异（如果有）

### 2.3 前端参数命名规范审查
- [x] 2.3.1 审查 `public/static/admin/js/assignment.create.js` - 未发现驼峰命名
- [x] 2.3.2 审查 `public/static/admin/js/assignment.list.js` - 规范
- [x] 2.3.3 审查 `public/static/home/js/assignment.*.js` - 规范
- [x] 2.3.4 前端无需修改字段名

### 2.4 后端参数命名规范审查
- [x] 2.4.1 使用grep搜索废弃字段名 - 已完成，发现4处问题
- [x] 2.4.2 创建后端字段名替换清单 - 已修复

### 2.5 题目解析逻辑统一审查
- [x] 2.5.1 审查后端解析 `Assignment.content` 的所有位置 - 已完成
- [x] 2.5.2 审查前端解析题目数据的所有位置 - 已完成
- [x] 2.5.3 确认前后台都兼容两种格式 - 已确认
- [x] 2.5.4 统一解析逻辑已在各Service中实现

### 2.6 自动评分规则规范审查
- [x] 2.6.1 确认只对 `type: "choice"` 的题目进行自动评分
- [x] 2.6.2 确认简答题（`essay`）禁止自动评分
- [x] 2.6.3 确认编程题（`code`）禁止自动评分
- [x] 2.6.4 确认文件上传题（`file_upload`）禁止自动评分
- [x] 2.6.5 文档化单选题评分算法 - 已在规范对照表中
- [x] 2.6.6 文档化多选题评分算法 - 已在规范对照表中

### 2.7 创建规范对照表 📊
- [x] 2.7.1 整理前后台字段对照表 - 已完成
- [x] 2.7.2 整理题目类型和数据结构对照表 - 已完成
- [x] 2.7.3 整理答案数据结构对照表（单选/多选/简答）- 已完成
- [x] 2.7.4 整理状态枚举值对照表（`status` + `grade_status`）- 已完成
- [x] 2.7.5 输出为独立文档：`作业模块前后台数据规范对照表.md` - 已完成

---

## 3. 代码审计 - Models层
- [x] 3.1 审计 `app/Models/Assignment.php` - 所有规范，无需修改
  - [x] 验证所有Getter/Setter方法正确定义
  - [x] 验证JSON字段的处理逻辑
  - [x] 验证常量定义完整性（`GRADE_MODE_*`, `STATUS_*`）
- [x] 3.2 审计 `app/Models/AssignmentSubmission.php` - 所有规范，无需修改
  - [x] 验证所有Getter/Setter方法
  - [x] 验证状态常量定义（`STATUS_*`, `GRADE_STATUS_*`）
  - [x] 验证关联关系定义
  - [x] 验证默认值设置（特别是`grader_id`）

---

## 4. 代码审计 - Repositories层
- [x] 4.1 审计 `app/Repos/Assignment.php` - 所有规范，无需修改
  - [x] 检查所有字段名使用
  - [x] 验证JSON字段的保存逻辑（使用Setter方法）
  - [x] 添加必要的错误处理
- [x] 4.2 审计 `app/Repos/AssignmentSubmission.php` - 所有规范，无需修改
  - [x] 检查查询条件中的字段名
  - [x] 验证 `findByAssignmentAndUser()` 方法
  - [x] 确保 `grader_id` 正确处理（NULL vs 0）

---

## 5. 代码审计与修复 - Services层（核心业务）

### 5.1 自动评分服务 ⚠️ 重点
- [x] 5.1.1 审计 `app/Services/Logic/Assignment/AutoGrade.php` - 已修复
  - [x] 确认只对 `type: "choice"` 的题目评分
  - [x] 确认简答题、编程题、文件上传题跳过
  - [x] 确认单选题答案类型判断（字符串）
  - [x] 确认多选题答案类型判断（数组）
  - [x] 确认 `grader_id` 根据批改模式正确设置
  - [x] 确认 `grade_status` 逻辑正确
- [x] 5.1.2 修复单选题和多选题评分逻辑差异 - 已修复
  ```php
  // 单选题
  if (!$question['multiple']) {
      $studentAnswer = $submissionContent[$questionId]; // 字符串 "A"
      if (is_string($studentAnswer) && $studentAnswer === $question['correct_answer']) {
          $score = $question['score'];
      }
  }
  // 多选题
  else {
      $studentAnswer = $submissionContent[$questionId]; // 数组 ["A", "C"]
      if (is_array($studentAnswer) && 
          count($studentAnswer) === count($question['correct_answer']) &&
          empty(array_diff($studentAnswer, $question['correct_answer']))) {
          $score = $question['score'];
      }
  }
  ```

### 5.2 作业提交服务
- [x] 5.2.1 审计 `app/Services/Logic/Assignment/AssignmentSubmit.php` - 规范，无需修改
  - [x] 字段名统一检查
  - [x] JSON字段处理检查
  - [x] `grader_id` 和 `status` 正确设置
  - [x] 错误处理和日志

### 5.3 草稿保存服务
- [x] 5.3.1 审计 `app/Services/Logic/Assignment/SubmissionDraft.php` - 规范，无需修改
  - [x] 字段名统一
  - [x] `delete_time` 正确初始化
  - [x] 错误处理

### 5.4 作业详情服务
- [x] 5.4.1 审计 `app/Services/Logic/Assignment/AssignmentInfo.php` - 规范，无需修改
  - [x] 字段名统一
  - [x] 题目数据解析逻辑（兼容两种格式）
  - [x] 提交状态判断逻辑

### 5.5 成绩查看服务
- [x] 5.5.1 审计 `app/Services/Logic/Assignment/SubmissionResult.php` - 已修复
  - [x] 字段名统一
  - [x] 成绩数据组装逻辑
  - [x] 批改详情显示 - 修复了单选题评分判断

### 5.6 作业列表服务
- [x] 5.6.1 未找到 `app/Services/Logic/Assignment/AssignmentList.php` (后台)
- [x] 5.6.2 审计 `app/Services/Logic/Course/AssignmentList.php` (前台课程) - 规范
  - [x] 字段名统一
  - [x] `status` 和 `grade_status` 状态判断逻辑
  - [x] 按钮显示逻辑（继续编辑/查看成绩/等待批改）
  - [x] 题目数量计算（避免重复 json_decode）
- [x] 5.6.3 审计 `app/Services/Logic/User/Console/AssignmentList.php` (个人中心) - 已修复
  - [x] 字段名统一
  - [x] 题目数量计算逻辑 - 修复为使用getContentData()
  - [x] 状态显示逻辑

### 5.7 通知服务
- [x] 5.7.1 未找到 `AssignmentPublished.php` - 可能不存在
- [x] 5.7.2 未找到 `AssignmentGraded.php` - 可能不存在

---

## 6. 代码审计与修复 - Controllers层
- [x] 6.1 审计 `app/Http/Admin/Controllers/AssignmentController.php` - 已修复
  - [x] 创建作业方法字段名统一
  - [x] 编辑作业方法字段名统一
  - [x] JSON字段空值处理（初始化为 `[]`）
  - [x] calculateAutoScore方法返回值统一使用max_score和earned_score
- [x] 6.2 审计 `app/Http/Admin/Controllers/AssignmentSubmissionController.php` - 规范
  - [x] 批改方法字段名统一
  - [x] 状态更新逻辑
- [x] 6.3 审计 `app/Http/Home/Controllers/AssignmentController.php` - 规范
  - [x] 提交方法字段名统一
  - [x] 草稿保存方法

---

## 7. 代码审计与修复 - Validators层
- [x] 7.1 grep搜索废弃字段 - 未发现问题
- [x] 7.2 Validators层无需修改

---

## 8. 代码审计与修复 - Views层（后台）
- [x] 8.1 grep搜索废弃字段 - 仅发现1处JavaScript本地变量totalScore（影响不大）
- [x] 8.2 Views层整体规范，无需修改

---

## 9. 代码审计与修复 - Views层（前台）
- [x] 9.1 grep搜索 - 未发现废弃字段
- [x] 9.2 Views层整体规范，无需修改

---

## 10. 代码审计与修复 - JavaScript层
- [x] 10.1 审计 `public/static/admin/js/assignment.create.js` - 规范
  - [x] 字段名已使用 snake_case
  - [x] JSON数据解析逻辑正确
  - [x] 日期时间转换逻辑（转为Unix时间戳）
  - [x] 选项数据解析（兼容对象和数组格式）
- [x] 10.2 审计 `public/static/admin/js/assignment.list.js` - 规范
- [x] 10.3 审计 `public/static/home/js/assignment.*.js` - 规范
  - [x] 提交表单字段名正确
  - [x] 草稿保存逻辑正确
  - [x] 答案格式处理正确

---

## 11. Git提交 - 第一批修复（规范统一）
- [x] 11.1 检查所有修改的文件 - 已完成
- [x] 11.2 提交代码 - commit e020279c
- [x] 11.3 推送到远程 - 已推送到 origin/bugfix/debug-optimize-assignment
- [x] 11.4 等待用户同步线上代码并测试

**修复文件**:
- AutoGrade.php - 修复单选题评分逻辑
- SubmissionResult.php - 修复成绩页判断逻辑
- AssignmentController.php - 统一字段命名
- User/Console/AssignmentList.php - 规范JSON访问

---

## 12. 第一轮测试验证（线上环境）
- [ ] 12.1 后台功能测试
  - [ ] 创建作业（单选题）
  - [ ] 创建作业（多选题）
  - [ ] 创建作业（混合题型）
  - [ ] 编辑作业（数据回填正确）
  - [ ] 查看提交列表
- [ ] 12.2 前台功能测试
  - [ ] 查看作业列表（题目数量、总分、截止时间正确）
  - [ ] 提交单选题作业
  - [ ] 提交多选题作业
  - [ ] 提交混合题型作业
  - [ ] 保存草稿
  - [ ] 草稿重新加载
- [ ] 12.3 自动评分测试
  - [ ] 纯选择题自动评分（auto模式）
  - [ ] 混合题型自动评分（mixed模式，选择题有分）
  - [ ] 简答题不自动评分（分数为0）
- [ ] 12.4 记录测试问题

---

## 13. 修复测试中发现的问题
- [ ] 13.1 分析测试中发现的问题
- [ ] 13.2 逐个修复
- [ ] 13.3 Git提交第二批修复
- [ ] 13.4 第二轮测试验证

---

## 14. 性能优化
- [ ] 14.1 检查数据库查询性能
  - [ ] 添加必要的索引
  - [ ] 优化N+1查询问题
- [ ] 14.2 添加缓存策略（如适用）
  - [ ] 作业详情缓存
  - [ ] 课程作业列表缓存

---

## 15. 错误处理增强
- [ ] 15.1 添加Model层错误处理
- [ ] 15.2 添加Service层错误日志
- [ ] 15.3 添加Controller层友好错误提示
- [ ] 15.4 添加前端表单验证和错误提示

---

## 16. 代码质量提升
- [ ] 16.1 消除重复代码
- [ ] 16.2 添加必要的代码注释
- [ ] 16.3 提取公共方法（如状态判断逻辑、题目解析逻辑）
- [ ] 16.4 统一命名风格

---

## 17. 文档更新
- [ ] 17.1 更新 `作业模块字段规范文档.md`（如有需要）
- [ ] 17.2 创建 `作业模块前后台数据规范对照表.md`
- [ ] 17.3 创建修复和优化总结报告
- [ ] 17.4 更新开发者文档

---

## 18. 最终代码审查与提交
- [ ] 18.1 自我代码审查
- [ ] 18.2 确认所有废弃字段名已替换
- [ ] 18.3 确认所有测试通过
- [ ] 18.4 合并到主分支或创建Pull Request

---

## 19. 归档OpenSpec变更
- [ ] 19.1 确认所有任务完成
- [ ] 19.2 将规范移到正式位置
  ```bash
  mv openspec/changes/debug-optimize-assignment/specs/assignment \
     openspec/specs/assignment
  ```
- [ ] 19.3 归档变更
  ```bash
  mv openspec/changes/debug-optimize-assignment \
     openspec/changes/archive/2025-10-15-debug-optimize-assignment
  ```
- [ ] 19.4 运行验证
  ```bash
  openspec validate --strict
  ```

---

**任务总数**: 120+项  
**已完成**: 第1-11章（代码审计与修复）  
**当前阶段**: ✅ 代码开发完成，等待线上测试  
**下一步**: 用户同步线上代码后，进行第12章"第一轮测试验证"

---

## 📊 完成进度

### ✅ 已完成（第1-11章）
- [x] 准备工作：Git分支、规范审查
- [x] 前后台规范统一审查：数据结构、字段命名
- [x] Models层审计：无需修改
- [x] Repositories层审计：无需修改
- [x] Services层审计与修复：修复4个文件
- [x] Controllers层审计：修复1个文件
- [x] Validators层审计：无需修改
- [x] Views层审计：无需修改
- [x] JavaScript层审计：无需修改
- [x] Git提交：commit e020279c，已推送

### 🔧 核心修复内容
1. **AutoGrade.php** - 单选题评分逻辑（字符串vs数组）
2. **SubmissionResult.php** - 成绩页面判断逻辑
3. **AssignmentController.php** - 统一字段命名（max_score/earned_score）
4. **User/Console/AssignmentList.php** - 规范JSON字段访问

### ⏳ 待完成（第12-19章）
- [ ] 第12章：第一轮测试验证（需线上环境）
- [ ] 第13章：修复测试问题（如有）
- [ ] 第14-16章：性能优化、错误处理、代码质量
- [ ] 第17章：文档更新
- [ ] 第18-19章：最终审查与OpenSpec归档

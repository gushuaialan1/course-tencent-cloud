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
- [ ] 1.4 创建Git开发分支 `bugfix/debug-optimize-assignment`
- [ ] 1.5 备份线上数据库 ⚠️

---

## 2. 前后台规范统一审查 ⚠️ **核心任务 - 必须先完成**

### 2.1 题目数据结构规范审查
- [ ] 2.1.1 确认 `Assignment.content` 字段的JSON结构（题目数组格式）
- [ ] 2.1.2 确认单选题和多选题的区分方式（`type: "choice"` + `multiple: true/false`）
- [ ] 2.1.3 确认题目类型枚举值：`choice`, `essay`, `code`, `file_upload`
- [ ] 2.1.4 文档化标准题目对象的所有字段和数据类型
- [ ] 2.1.5 确认 `options` 字段的格式（对象 `{A: "选项A"}` 或数组）

### 2.2 学生答案数据结构规范审查
- [ ] 2.2.1 确认 `AssignmentSubmission.content` 字段的JSON结构（题目ID → 答案）
- [ ] 2.2.2 确认单选题答案格式：**字符串** `"A"` （不是数组）
- [ ] 2.2.3 确认多选题答案格式：**数组** `["A", "C"]` （不是字符串）
- [ ] 2.2.4 确认简答题答案格式：字符串（文本内容）
- [ ] 2.2.5 文档化前端提交和后端存储的答案结构差异（如果有）

### 2.3 前端参数命名规范审查
- [ ] 2.3.1 审查 `public/static/admin/js/assignment.create.js`
  - [ ] 检查所有字段名是否使用 snake_case
  - [ ] 列出所有需要修改的变量名
- [ ] 2.3.2 审查 `public/static/admin/js/assignment.list.js`
- [ ] 2.3.3 审查 `public/static/home/js/assignment.*.js`
- [ ] 2.3.4 创建前端字段名修改清单（文件 + 行号 + 旧名 → 新名）

### 2.4 后端参数命名规范审查
- [ ] 2.4.1 使用grep搜索废弃字段名
  ```bash
  cd /course-tencent-cloud
  grep -rn "total_score" app/Services/Logic/Assignment/
  grep -rn "deadline" app/Services/Logic/Assignment/
  grep -rn "allow_resubmit" app/Services/Logic/Assignment/
  grep -rn "answers" app/Services/Logic/Assignment/
  grep -rn "submitted_at" app/Services/Logic/Assignment/
  grep -rn "graded_at" app/Services/Logic/Assignment/
  ```
- [ ] 2.4.2 创建后端字段名替换清单（文件 + 行号 + 旧字段 → 新字段）

### 2.5 题目解析逻辑统一审查
- [ ] 2.5.1 审查后端解析 `Assignment.content` 的所有位置
  - `AssignmentInfo.php`
  - `Course/AssignmentList.php`
  - `User/Console/AssignmentList.php`
  - `AutoGrade.php`
- [ ] 2.5.2 审查前端解析题目数据的所有位置
  - `assignment.create.js` 编辑模式数据回填
  - `assignment.show.js` 答题页面渲染
- [ ] 2.5.3 确认前后台都兼容两种格式：
  - 新格式：`[{题目对象}, {题目对象}, ...]`
  - 旧格式：`{questions: [{题目对象}, ...]}`
- [ ] 2.5.4 统一解析逻辑，提取为公共方法（避免重复代码）

### 2.6 自动评分规则规范审查
- [ ] 2.6.1 确认只对 `type: "choice"` 的题目进行自动评分
- [ ] 2.6.2 确认简答题（`essay`）禁止自动评分
- [ ] 2.6.3 确认编程题（`code`）禁止自动评分
- [ ] 2.6.4 确认文件上传题（`file_upload`）禁止自动评分
- [ ] 2.6.5 文档化单选题评分算法：
  - 学生答案类型：字符串（如 `"A"`）
  - 比较方式：严格相等 `===`
  - 得分：完全匹配得满分，否则0分
- [ ] 2.6.6 文档化多选题评分算法：
  - 学生答案类型：数组（如 `["A", "C"]`）
  - 比较方式：数组完全相等（包括顺序）
  - 得分：完全匹配得满分，否则0分（不支持部分得分）

### 2.7 创建规范对照表 📊
- [ ] 2.7.1 整理前后台字段对照表
  | 数据库字段 | PHP使用 | JS使用 | Volt使用 | 数据类型 | 说明 |
  |-----------|---------|--------|----------|---------|------|
  | max_score | $assignment->max_score | assignment.max_score | assignment.max_score | decimal | 总分 |
  | due_date | $assignment->due_date | assignment.due_date | assignment.due_date | int | Unix时间戳 |
  | ... | ... | ... | ... | ... | ... |
- [ ] 2.7.2 整理题目类型和数据结构对照表
- [ ] 2.7.3 整理答案数据结构对照表（单选/多选/简答）
- [ ] 2.7.4 整理状态枚举值对照表（`status` + `grade_status`）
- [ ] 2.7.5 输出为独立文档：`作业模块前后台数据规范对照表.md`

---

## 3. 代码审计 - Models层
- [ ] 3.1 审计 `app/Models/Assignment.php`
  - [ ] 验证所有Getter/Setter方法正确定义
  - [ ] 验证JSON字段的处理逻辑
  - [ ] 验证常量定义完整性（`GRADE_MODE_*`, `STATUS_*`）
- [ ] 3.2 审计 `app/Models/AssignmentSubmission.php`
  - [ ] 验证所有Getter/Setter方法
  - [ ] 验证状态常量定义（`STATUS_*`, `GRADE_STATUS_*`）
  - [ ] 验证关联关系定义
  - [ ] 验证默认值设置（特别是`grader_id`）

---

## 4. 代码审计 - Repositories层
- [ ] 4.1 审计 `app/Repos/Assignment.php`
  - [ ] 检查所有字段名使用
  - [ ] 验证JSON字段的保存逻辑（使用Setter方法）
  - [ ] 添加必要的错误处理
- [ ] 4.2 审计 `app/Repos/AssignmentSubmission.php`
  - [ ] 检查查询条件中的字段名
  - [ ] 验证 `findByAssignmentAndUser()` 方法
  - [ ] 确保 `grader_id` 正确处理（NULL vs 0）

---

## 5. 代码审计与修复 - Services层（核心业务）

### 5.1 自动评分服务 ⚠️ 重点
- [ ] 5.1.1 审计 `app/Services/Logic/Assignment/AutoGrade.php`
  - [ ] 确认只对 `type: "choice"` 的题目评分
  - [ ] 确认简答题、编程题、文件上传题跳过
  - [ ] 确认单选题答案类型判断（字符串）
  - [ ] 确认多选题答案类型判断（数组）
  - [ ] 确认 `grader_id` 根据批改模式正确设置
  - [ ] 确认 `grade_status` 逻辑正确
- [ ] 5.1.2 修复单选题和多选题评分逻辑差异
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
- [ ] 5.2.1 审计 `app/Services/Logic/Assignment/AssignmentSubmit.php`
  - [ ] 字段名统一检查
  - [ ] JSON字段处理检查
  - [ ] `grader_id` 和 `status` 正确设置
  - [ ] 错误处理和日志

### 5.3 草稿保存服务
- [ ] 5.3.1 审计 `app/Services/Logic/Assignment/SubmissionDraft.php`
  - [ ] 字段名统一
  - [ ] `delete_time` 正确初始化
  - [ ] 错误处理

### 5.4 作业详情服务
- [ ] 5.4.1 审计 `app/Services/Logic/Assignment/AssignmentInfo.php`
  - [ ] 字段名统一
  - [ ] 题目数据解析逻辑（兼容两种格式）
  - [ ] 提交状态判断逻辑

### 5.5 成绩查看服务
- [ ] 5.5.1 审计 `app/Services/Logic/Assignment/SubmissionResult.php`
  - [ ] 字段名统一
  - [ ] 成绩数据组装逻辑
  - [ ] 批改详情显示

### 5.6 作业列表服务
- [ ] 5.6.1 审计 `app/Services/Logic/Assignment/AssignmentList.php` (后台)
  - [ ] 字段名统一
  - [ ] 列表查询逻辑
- [ ] 5.6.2 审计 `app/Services/Logic/Course/AssignmentList.php` (前台课程)
  - [ ] 字段名统一
  - [ ] `status` 和 `grade_status` 状态判断逻辑
  - [ ] 按钮显示逻辑（继续编辑/查看成绩/等待批改）
  - [ ] 题目数量计算（避免重复 json_decode）
- [ ] 5.6.3 审计 `app/Services/Logic/User/Console/AssignmentList.php` (个人中心)
  - [ ] 字段名统一
  - [ ] 题目数量计算逻辑
  - [ ] 状态显示逻辑

### 5.7 通知服务
- [ ] 5.7.1 审计 `app/Services/Logic/Notice/Internal/AssignmentPublished.php`
  - [ ] 字段名统一
- [ ] 5.7.2 审计 `app/Services/Logic/Notice/Internal/AssignmentGraded.php`
  - [ ] 字段名统一

---

## 6. 代码审计与修复 - Controllers层
- [ ] 6.1 审计 `app/Http/Admin/Controllers/AssignmentController.php`
  - [ ] 创建作业方法字段名统一
  - [ ] 编辑作业方法字段名统一
  - [ ] JSON字段空值处理（初始化为 `[]`）
- [ ] 6.2 审计 `app/Http/Admin/Controllers/AssignmentSubmissionController.php`
  - [ ] 批改方法字段名统一
  - [ ] 状态更新逻辑
- [ ] 6.3 审计 `app/Http/Home/Controllers/AssignmentController.php`
  - [ ] 提交方法字段名统一
  - [ ] 草稿保存方法

---

## 7. 代码审计与修复 - Validators层
- [ ] 7.1 列出所有作业相关Validator
  ```bash
  ls app/Validators/Assignment*.php
  ```
- [ ] 7.2 逐个审计每个Validator
  - [ ] 字段名统一
  - [ ] 验证规则正确性
  - [ ] 错误消息友好性

---

## 8. 代码审计与修复 - Views层（后台）
- [ ] 8.1 审计 `app/Http/Admin/Views/assignment/list.volt`
  - [ ] 字段名统一
  - [ ] 状态显示逻辑
- [ ] 8.2 审计 `app/Http/Admin/Views/assignment/create.volt`
  - [ ] 字段名统一
  - [ ] 编辑模式数据回填（使用 `<script type="application/json">` 传递JSON）
- [ ] 8.3 审计 `app/Http/Admin/Views/assignment/grading.volt`
  - [ ] 字段名统一

---

## 9. 代码审计与修复 - Views层（前台）
- [ ] 9.1 审计 `app/Http/Home/Views/course/assignments.volt`
  - [ ] 字段名统一
  - [ ] 状态和按钮显示逻辑
- [ ] 9.2 审计 `app/Http/Home/Views/assignment/show.volt`
  - [ ] 字段名统一
  - [ ] 答题页面数据回填
- [ ] 9.3 审计 `app/Http/Home/Views/assignment/result.volt`
  - [ ] 字段名统一
  - [ ] 成绩显示
- [ ] 9.4 审计 `app/Http/Home/Views/user/console/assignments.volt`
  - [ ] 字段名统一
  - [ ] 个人中心列表显示

---

## 10. 代码审计与修复 - JavaScript层
- [ ] 10.1 审计 `public/static/admin/js/assignment.create.js`
  - [ ] 字段名统一（使用 snake_case）
  - [ ] JSON数据解析逻辑
  - [ ] 日期时间转换逻辑（转为Unix时间戳）
  - [ ] 选项数据解析（兼容对象和数组格式）
- [ ] 10.2 审计 `public/static/admin/js/assignment.list.js`
  - [ ] AJAX请求参数字段名
- [ ] 10.3 审计 `public/static/home/js/assignment.*.js`
  - [ ] 提交表单字段名
  - [ ] 草稿保存逻辑
  - [ ] 单选题答案格式（字符串）
  - [ ] 多选题答案格式（数组）

---

## 11. Git提交 - 第一批修复（规范统一）
- [ ] 11.1 检查所有修改的文件
  ```bash
  git status
  git diff
  ```
- [ ] 11.2 提交代码
  ```bash
  git add .
  git commit -m "fix: 统一作业模块前后台字段命名和数据结构规范
  
  - 统一所有字段名为标准命名（max_score, due_date, allow_late等）
  - 统一题目数据结构解析逻辑
  - 规范单选题答案格式（字符串）和多选题答案格式（数组）
  - 修复自动评分逻辑，只对选择题有效
  - 禁止简答题、编程题、文件上传题自动评分
  
  详见：openspec/changes/debug-optimize-assignment/"
  ```
- [ ] 11.3 推送到远程
  ```bash
  git push origin bugfix/debug-optimize-assignment
  ```
- [ ] 11.4 通知用户同步线上代码

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
**预计工作量**: 2-3天  
**当前阶段**: 准备阶段 → 规范统一审查 ⚠️  
**下一步**: 完成第2章"前后台规范统一审查"后再开始编码

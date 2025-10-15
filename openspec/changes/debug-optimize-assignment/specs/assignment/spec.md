# Assignment Module Specification (Delta)

本文档定义作业模块的完整业务需求和规范。

---

## ADDED Requirements

### Requirement: 作业创建与管理

系统 MUST 提供完整的作业创建和管理功能，支持多种题型和批改模式。

#### Scenario: 管理员创建单选题作业
- **WHEN** 管理员在后台选择课程和章节，添加单选题，设置总分和截止时间
- **THEN** 系统应保存作业信息，题目内容存储在`content`字段（JSON数组格式）
- **AND** 作业状态为`draft`或`published`

#### Scenario: 管理员创建混合题型作业
- **WHEN** 管理员添加多个不同类型的题目（选择题、多选题、简答题）
- **THEN** 系统应保存所有题目到`content`数组，每题包含`type`、`score`、`title`等字段
- **AND** 总分等于各题分值之和

#### Scenario: 管理员编辑已发布的作业
- **WHEN** 管理员打开编辑页面
- **THEN** 系统应加载作业的所有数据，包括基本信息、题目内容、评分设置、时间设置
- **AND** JSON字段通过`getXxxData()`方法自动解析为数组
- **AND** 选择题的选项和正确答案正确回填到表单

### Requirement: 前后台数据规范统一 ⚠️ 核心规范

前端（JavaScript、Volt模板）和后端（PHP、MySQL）在处理作业数据时 MUST 使用统一的字段名、数据类型和数据结构，确保前后台参数命名、题目解析规范完全一致。

#### Scenario: 前后台字段名完全一致
- **WHEN** 后端返回作业数据给前端，或前端提交数据给后端
- **THEN** 字段名必须完全一致：
  - ✅ `max_score` (不是 `total_score` 或 `totalScore`)
  - ✅ `due_date` (不是 `deadline` 或 `dueDate`)
  - ✅ `allow_late` (不是 `allow_resubmit` 或 `allowLate`)
  - ✅ `content` (不是 `questions` 或 `answers`)
- **AND** 前端JavaScript不得使用驼峰命名，必须与数据库字段保持一致（snake_case）

#### Scenario: 题目数据结构前后台统一
- **WHEN** 后端通过API或Volt模板传递题目数据给前端
- **THEN** 题目数组结构必须统一：
  ```json
  [
    {
      "id": 1,
      "type": "choice",
      "title": "题目标题",
      "score": 40,
      "multiple": false,
      "options": {"A": "选项A", "B": "选项B"},
      "correct_answer": "A"
    }
  ]
  ```
- **AND** 前端JavaScript解析时不得修改结构或字段名

#### Scenario: 学生答案数据结构前后台统一
- **WHEN** 前端提交学生答案，或后端返回已保存的答案
- **THEN** 答案结构必须统一（题目ID → 答案值）：
  ```json
  {
    "1": "A",           // 单选题：字符串
    "2": ["A", "C"],    // 多选题：数组
    "3": "学生的简答..."  // 简答题：字符串
  }
  ```
- **AND** 单选题答案必须是字符串，不能是数组
- **AND** 多选题答案必须是数组，不能是字符串

#### Scenario: 题目类型标识前后台统一
- **WHEN** 判断题目类型
- **THEN** 类型值必须统一使用：
  - `choice` - 选择题（通过`multiple`字段区分单选/多选）
  - `essay` - 简答题
  - `code` - 编程题
  - `file_upload` - 文件上传题
- **AND** 前端不得使用 `radio`, `checkbox`, `text` 等自定义类型名

#### Scenario: 日期时间格式前后台统一
- **WHEN** 处理截止时间（`due_date`）
- **THEN** 后端存储和传输都使用Unix时间戳（整数）
- **AND** 前端提交时必须转换为Unix时间戳，不得传递日期字符串
- **AND** 前端显示时从时间戳转换为可读格式

#### Scenario: 题目ID的类型一致性
- **WHEN** 使用题目ID作为key（如答案对象的key）
- **THEN** 前后端都统一使用字符串类型的ID：
  - ✅ `{"1": "A", "2": ["B", "C"]}`
  - ❌ `{1: "A", 2: ["B", "C"]}` (数字key在JSON序列化时可能丢失)
- **AND** PHP解析答案时兼容字符串key和数字key

### Requirement: 字段命名规范

系统中所有作业相关的代码 MUST 使用标准字段名，废弃的旧字段名不得使用。

#### Scenario: 访问作业总分
- **WHEN** 代码需要读取或设置作业总分
- **THEN** 必须使用`max_score`字段
- **AND** 不得使用废弃字段`total_score`

#### Scenario: 访问作业截止时间
- **WHEN** 代码需要读取或设置作业截止时间
- **THEN** 必须使用`due_date`字段（Unix时间戳）
- **AND** 不得使用废弃字段`deadline`

#### Scenario: 访问允许迟交设置
- **WHEN** 代码需要判断是否允许迟交
- **THEN** 必须使用`allow_late`字段
- **AND** 不得使用废弃字段`allow_resubmit`

#### Scenario: 访问作业题目内容
- **WHEN** 代码需要读取或设置作业题目
- **THEN** 必须使用`content`字段
- **AND** 不得使用废弃字段`questions`或`answers`

#### Scenario: 访问提交时间
- **WHEN** 代码需要读取或设置学生提交时间
- **THEN** 必须使用`submit_time`字段
- **AND** 不得使用废弃字段`submitted_at`

#### Scenario: 访问批改时间
- **WHEN** 代码需要读取或设置批改时间
- **THEN** 必须使用`grade_time`字段
- **AND** 不得使用废弃字段`graded_at`

#### Scenario: 判断作业是否删除
- **WHEN** 代码需要判断作业或提交是否已删除
- **THEN** 必须使用`delete_time > 0`条件
- **AND** 不得使用废弃字段`deleted`

### Requirement: JSON字段处理规范

系统中所有JSON字段的读写 MUST 遵循统一的处理规范，确保数据正确性。

#### Scenario: 保存JSON字段
- **WHEN** 代码需要保存JSON字段（如`content`、`attachments`、`rubric`）
- **THEN** 必须使用Model的`setXxxData()`方法
- **AND** 不得手动调用`json_encode()`
- **AND** 空值必须传递`[]`（空数组），不得传递`''`（空字符串）或`null`

#### Scenario: 读取JSON字段
- **WHEN** 代码需要读取JSON字段
- **THEN** 如果直接访问Model属性，必须使用`getXxxData()`方法
- **AND** 如果通过`toArray()`获取，字段已自动解析为数组，不得再调用`json_decode()`

#### Scenario: 初始化空JSON字段
- **WHEN** 创建新的作业或提交记录
- **THEN** 所有JSON字段必须初始化为`[]`
- **AND** 不得初始化为`''`或`null`，否则会导致MySQL错误："Invalid JSON text: The document is empty"

### Requirement: 作业提交功能

学生 MUST 能够提交作业，系统根据截止时间和尝试次数控制提交权限。

#### Scenario: 学生首次提交作业
- **WHEN** 学生填写所有题目答案后点击提交
- **THEN** 系统应创建`AssignmentSubmission`记录
- **AND** `status`应设置为`submitted`（不是`pending`）
- **AND** `submit_time`应设置为当前时间戳
- **AND** 学生答案应存储在`content`字段（JSON格式，结构为`{题目ID: 答案}`）
- **AND** `grader_id`根据批改模式设置（auto模式为`null`，manual/mixed模式为`assignment.owner_id`）

#### Scenario: 学生保存草稿
- **WHEN** 学生未完成作业但点击保存草稿
- **THEN** 系统应创建或更新`AssignmentSubmission`记录
- **AND** `status`应设置为`draft`
- **AND** `delete_time`必须显式设置为`0`
- **AND** 再次打开作业时应能加载草稿内容

#### Scenario: 草稿保存后重新加载
- **WHEN** 学生保存草稿后离开页面，再次进入作业详情页
- **THEN** 系统应通过`findByAssignmentAndUser()`查询到草稿记录（条件包含`delete_time = 0`）
- **AND** 答案内容应正确回填到表单中

#### Scenario: 学生重复提交作业
- **WHEN** 学生已提交过作业，再次尝试提交
- **THEN** 如果`allow_late = 1`且未超过`max_attempts`，应允许重新提交
- **AND** 更新原有记录的`content`、`submit_time`、`attempt_count`

#### Scenario: 超过截止时间提交
- **WHEN** 当前时间超过`due_date`且学生尝试提交
- **THEN** 如果`allow_late = 1`，应允许提交，并设置`is_late = 1`、根据`late_penalty`扣分
- **AND** 如果`allow_late = 0`，应拒绝提交，返回错误提示

#### Scenario: 超过最大尝试次数
- **WHEN** 学生已提交次数达到`max_attempts`且尝试再次提交
- **THEN** 系统应拒绝提交，返回错误提示

### Requirement: 自动评分功能

系统 MUST 支持选择题（单选题和多选题）的自动评分，非选择题（简答题、编程题、文件上传题）禁止自动评分，必须人工批改。

#### Scenario: 纯自动批改模式（auto）
- **WHEN** 作业的`grade_mode = 'auto'`且学生提交作业
- **THEN** 系统应立即对所有选择题进行自动评分
- **AND** `submission.status`应更新为`graded`
- **AND** `submission.grade_status`应设置为`completed`
- **AND** `submission.grader_id`应设置为`null`（不是`0`）
- **AND** `submission.grade_time`应设置为当前时间戳
- **AND** 计算总分并存储在`submission.score`字段

#### Scenario: 混合批改模式（mixed）
- **WHEN** 作业的`grade_mode = 'mixed'`且学生提交作业
- **THEN** 系统应对选择题进行自动评分
- **AND** `submission.status`应更新为`graded`
- **AND** `submission.grade_status`应设置为`pending`（等待教师批改主观题）
- **AND** `submission.grader_id`应设置为`assignment.owner_id`
- **AND** 简答题的分数暂时为0，等待教师手动批改

#### Scenario: 纯手动批改模式（manual）
- **WHEN** 作业的`grade_mode = 'manual'`且学生提交作业
- **THEN** 系统不进行自动评分
- **AND** `submission.status`保持为`submitted`
- **AND** `submission.grade_status`设置为`pending`
- **AND** `submission.grader_id`设置为`assignment.owner_id`
- **AND** 等待教师在批改工作台手动评分

#### Scenario: 单选题自动评分
- **WHEN** 题目类型为`choice`且`multiple = false`
- **THEN** 系统应比较学生答案（字符串，如`"A"`）与`correct_answer`字段（字符串）
- **AND** 完全匹配得该题满分（`question.score`），不匹配得0分
- **AND** 学生答案数据类型必须是字符串，不能是数组

#### Scenario: 多选题自动评分
- **WHEN** 题目类型为`choice`且`multiple = true`
- **THEN** 系统应比较学生答案（数组，如`["A", "C"]`）与`correct_answer`（数组）
- **AND** 所有选项完全匹配（包括顺序）得该题满分，否则得0分
- **AND** 不支持部分得分（如4个选项答对3个不给分）
- **AND** 学生答案必须是数组类型，即使只选一个选项也是数组`["A"]`

#### Scenario: 简答题禁止自动评分
- **WHEN** 题目类型为`essay`
- **THEN** 系统不得进行自动评分
- **AND** 该题分数保持为0，等待教师手动批改
- **AND** 即使作业批改模式为`auto`，简答题也必须跳过

#### Scenario: 编程题禁止自动评分
- **WHEN** 题目类型为`code`
- **THEN** 系统不得进行自动评分
- **AND** 该题分数保持为0，等待教师手动批改

#### Scenario: 文件上传题禁止自动评分
- **WHEN** 题目类型为`file_upload`
- **THEN** 系统不得进行自动评分
- **AND** 该题分数保持为0，等待教师手动批改

### Requirement: 提交状态管理

系统 MUST 使用`status`和`grade_status`两个字段配合管理提交的状态，清晰区分批改进度。

#### Scenario: 草稿状态
- **WHEN** 学生保存草稿
- **THEN** `status = 'draft'`
- **AND** `grade_status = 'pending'`
- **AND** 前端显示"草稿"标签和"继续编辑"按钮

#### Scenario: 已提交等待批改状态
- **WHEN** 学生提交作业且批改模式为`manual`
- **THEN** `status = 'submitted'`
- **AND** `grade_status = 'pending'`
- **AND** 前端显示"已提交"标签和"等待批改"提示

#### Scenario: 批改中状态（混合模式）
- **WHEN** 混合模式作业的选择题已自动批改，但主观题未批改
- **THEN** `status = 'graded'`
- **AND** `grade_status = 'pending'` 或 `'grading'`
- **AND** 前端显示"批改中"标签和当前得分

#### Scenario: 批改完成状态
- **WHEN** 所有题目都已批改（自动或手动）
- **THEN** `status = 'graded'`
- **AND** `grade_status = 'completed'`
- **AND** 前端显示"已批改"标签和"查看成绩"按钮

#### Scenario: 作业退回状态
- **WHEN** 教师退回学生作业要求重新提交
- **THEN** `status = 'returned'`
- **AND** `grade_status = 'completed'`
- **AND** 前端显示"已退回"标签和"重新提交"按钮

### Requirement: 批改人ID（grader_id）管理

系统 MUST 正确设置和维护`grader_id`字段，避免外键约束错误。

#### Scenario: 自动批改模式设置grader_id
- **WHEN** 作业的`grade_mode = 'auto'`且系统进行自动评分
- **THEN** `submission.grader_id`必须设置为`null`
- **AND** 不得设置为`0`，否则会触发外键约束错误

#### Scenario: 手动批改模式设置grader_id
- **WHEN** 作业的`grade_mode = 'manual'`或`'mixed'`
- **THEN** `submission.grader_id`应设置为`assignment.owner_id`（作业创建者）
- **AND** 如果需要指定其他批改人，必须是存在的用户ID

#### Scenario: 创建提交记录时初始化grader_id
- **WHEN** 首次创建`AssignmentSubmission`记录
- **THEN** `grader_id`应显式设置为`null`（如果是auto模式）或有效用户ID
- **AND** Model的默认值（如`public $grader_id = 0`）可能不被Phalcon的`create()`使用，必须显式设置

### Requirement: 作业列表展示

系统 MUST 在前台（课程页面、个人中心）和后台正确展示作业列表，状态和按钮根据提交情况动态显示。

#### Scenario: 前台课程作业列表显示
- **WHEN** 学生访问课程的"作业"标签页
- **THEN** 系统应显示该课程的所有已发布作业
- **AND** 每个作业显示标题、题目数量、总分、截止时间、提交状态
- **AND** 根据`submission.status`和`submission.grade_status`显示正确的状态标签和操作按钮

#### Scenario: 作业列表状态判断逻辑
- **WHEN** 系统渲染作业列表项
- **THEN** 必须同时检查`status`和`grade_status`
- **AND** `status = 'graded'`且`grade_status = 'completed'` → 显示"已批改"
- **AND** `status = 'graded'`且`grade_status = 'pending'` → 显示"批改中"
- **AND** `status = 'submitted'` → 显示"已提交"或"等待批改"
- **AND** `status = 'draft'` → 显示"草稿"
- **AND** 无提交记录 → 显示"未做"

#### Scenario: 题目数量正确显示
- **WHEN** 系统在列表中显示作业的题目数量
- **THEN** 必须从`assignment.content`数组解析题目
- **AND** 如果通过`toArray()`获取，`content`已是数组，不得再调用`json_decode()`
- **AND** 题目数量 = `count($assignment['content'])`

### Requirement: 作业详情与答题页面

学生 MUST 能够查看作业详情，包括题目内容、答题表单、提交历史。

#### Scenario: 学生首次打开作业
- **WHEN** 学生点击"开始作业"或"继续编辑"按钮
- **THEN** 系统应加载作业详情（从`Assignment`模型）
- **AND** 题目内容从`assignment.content`解析
- **AND** 如果存在草稿，应加载学生的答案并回填到表单

#### Scenario: 题目内容解析
- **WHEN** 系统解析`assignment.content`字段
- **THEN** 必须兼容两种数据结构：
  - 新格式：`[{题目对象}, {题目对象}, ...]`（题目数组）
  - 旧格式：`{questions: [{题目对象}, ...]}`（包裹在对象中）
- **AND** 使用兼容逻辑：`$questions = isset($content['questions']) ? $content['questions'] : $content`

#### Scenario: 查询学生提交记录
- **WHEN** 系统需要查询学生的提交或草稿
- **THEN** 必须使用`AssignmentSubmissionRepo::findByAssignmentAndUser($assignmentId, $userId)`方法
- **AND** 不得使用不存在的方法如`findSubmission()`

### Requirement: 批改工作台

教师 MUST 能够在后台批改工作台查看提交列表，批改主观题，管理成绩。

#### Scenario: 教师查看提交列表
- **WHEN** 教师在作业列表点击"查看提交"按钮
- **THEN** 系统应跳转到批改工作台（`admin.assignment.grading.list`路由）
- **AND** 显示该作业的所有学生提交
- **AND** 显示学生姓名、提交时间、当前得分、批改状态

#### Scenario: 教师批改主观题
- **WHEN** 教师打开提交详情，对简答题给分
- **THEN** 系统应更新`submission.grade_details`（JSON格式，包含每题得分）
- **AND** 重新计算总分并更新`submission.score`
- **AND** 更新`submission.grade_status = 'completed'`
- **AND** 更新`submission.grade_time`为当前时间

### Requirement: 成绩查看

学生 MUST 能够查看已批改作业的成绩和批改详情。

#### Scenario: 学生查看成绩页面
- **WHEN** 学生点击"查看成绩"按钮
- **THEN** 系统应显示总分、每题得分、正确答案对比、教师评语
- **AND** 自动评分的题目显示对错标识
- **AND** 手动批改的题目显示教师给分和评语

#### Scenario: 成绩数据组装
- **WHEN** 系统组装成绩数据
- **THEN** 必须使用标准字段名（`max_score`、`grade_time`等）
- **AND** 从`submission.content`解析学生答案
- **AND** 从`submission.grade_details`解析每题得分
- **AND** 从`assignment.content`获取题目和正确答案

### Requirement: 数据一致性保证

系统 MUST 确保数据库操作的一致性，避免因字段名错误或空值导致的数据写入失败。

#### Scenario: 创建作业时JSON字段为空
- **WHEN** 创建作业但某些JSON字段（如`rubric`、`attachments`）为空
- **THEN** Controller层应为空字段设置默认值`[]`
- **AND** Repo层通过Setter方法将`[]`转换为JSON字符串`"[]"`
- **AND** 数据库中存储有效的JSON，不会触发"Invalid JSON text"错误

#### Scenario: 更新提交记录时验证外键
- **WHEN** 更新`AssignmentSubmission`记录的`grader_id`
- **THEN** 如果值为`null`，应保持`null`
- **AND** 如果值为用户ID，必须确保该ID在`kg_user`表中存在
- **AND** 不得设置为`0`或不存在的用户ID，否则触发外键约束错误

#### Scenario: 创建提交记录时错误处理
- **WHEN** 调用`$submission->create()`保存记录
- **THEN** 如果返回`false`，应立即调用`$submission->getMessages()`获取错误详情
- **AND** 抛出异常或记录日志，包含详细的错误信息
- **AND** 避免静默失败，导致数据未保存但无报错

### Requirement: 前端数据传递规范

前端JavaScript在提交表单数据时 MUST 正确格式化字段，与后端API约定保持一致。

#### Scenario: 前端提交日期时间字段
- **WHEN** 前端收集表单中的`due_date`（日期选择器的值为"2025-10-20 23:59:00"）
- **THEN** 必须转换为Unix时间戳（整数）：`Math.floor(new Date(dateStr).getTime() / 1000)`
- **AND** 不得直接传递日期字符串，否则后端验证器报错

#### Scenario: 前端提交复选框字段
- **WHEN** 前端收集`allow_late`复选框的值
- **THEN** 如果勾选，应传递`1`（整数）；如果未勾选，传递`0`
- **AND** 不得传递布尔值`true`/`false`或字符串`"on"`/`"off"`

#### Scenario: 前端编辑作业时加载JSON数据
- **WHEN** 后端通过Volt模板传递作业数据（如`{{ assignment|json_encode }}`）
- **THEN** 不得使用`<input type="hidden" value="{{ assignment|json_encode }}">`，会被HTML实体编码
- **AND** 必须使用`<script type="application/json" id="assignment-data">{{ assignment|json_encode }}</script>`
- **AND** JavaScript通过`document.getElementById('assignment-data').textContent`获取原始JSON字符串

#### Scenario: 前端显示选择题选项
- **WHEN** 编辑页面回填选择题的选项数据
- **THEN** 必须兼容两种options数据结构：
  - 对象格式：`{A: "选项A", B: "选项B"}`
  - 数组格式：`[{label: "A", content: "选项A"}, ...]`
- **AND** 正确提取`content`或`option`字段，不得直接将对象赋值给`input.value`（会显示`[object Object]`）

---

## 数据结构规范

### Assignment.content 结构（题目数组）

```json
[
  {
    "id": 1,
    "type": "choice",
    "title": "题目标题",
    "score": 40,
    "multiple": false,
    "options": {
      "A": "选项A内容",
      "B": "选项B内容",
      "C": "选项C内容",
      "D": "选项D内容"
    },
    "correct_answer": "A"
  },
  {
    "id": 2,
    "type": "choice",
    "title": "多选题示例",
    "score": 30,
    "multiple": true,
    "options": {
      "A": "选项A",
      "B": "选项B",
      "C": "选项C"
    },
    "correct_answer": ["A", "C"]
  },
  {
    "id": 3,
    "type": "essay",
    "title": "简答题示例",
    "score": 30,
    "min_length": 50,
    "max_length": 500
  }
]
```

### AssignmentSubmission.content 结构（学生答案）

```json
{
  "1": "A",
  "2": ["A", "C"],
  "3": "这是学生的简答题答案内容..."
}
```

### AssignmentSubmission.grade_details 结构（批改详情）

```json
{
  "1": {
    "score": 40,
    "is_correct": true,
    "student_answer": "A",
    "correct_answer": "A"
  },
  "2": {
    "score": 30,
    "is_correct": true,
    "student_answer": ["A", "C"],
    "correct_answer": ["A", "C"]
  },
  "3": {
    "score": 25,
    "is_correct": false,
    "student_answer": "学生答案...",
    "teacher_comment": "回答不够完整，需要补充..."
  }
}
```

---

## 状态机设计

### AssignmentSubmission 状态转换

```
[无记录] 
    ↓ 保存草稿
[draft + pending] (草稿)
    ↓ 提交作业
[submitted + pending] (已提交，等待批改)
    ↓
    ├─ auto模式 → [graded + completed] (已批改)
    ├─ mixed模式 → [graded + pending] (批改中) → 教师完成批改 → [graded + completed]
    └─ manual模式 → [submitted + pending] → 教师批改 → [graded + completed]
    
[graded + completed] (已批改)
    ↓ 教师退回
[returned + completed] (已退回)
    ↓ 学生重新提交
[submitted + pending] (重新开始流程)
```

---

**规范版本**: v1.0  
**最后更新**: 2025-10-15


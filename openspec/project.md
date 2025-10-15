# Project Context

## Purpose
酷瓜云课堂 (KooGua Cloud Classroom) - 开源的在线教育系统和知识付费平台。

**核心目标：**
- 提供完整的在线课程学习功能（点播、直播、专栏）
- 支持作业、考试、问答等教学互动
- 实现会员体系、积分系统、支付功能
- 基于腾讯云基础服务架构
- GPL-2.0 开源协议

## Tech Stack

### 后端框架
- **Phalcon 3.4** - C扩展PHP框架（主框架）
- **PHP 7.3+** - 运行环境
- **MySQL 5.7+** - 关系型数据库
- **Redis 5.0+** - 缓存和会话存储

### 前端框架
- **Layui 2.9** - UI组件库
- **jQuery** - JavaScript库
- **Volt** - Phalcon模板引擎

### 核心组件
- **XunSearch 1.4** - 全文检索引擎
- **WorkerMan GatewayWorker 3.0** - WebSocket长连接框架
- **Phinx 0.12** - 数据库迁移工具
- **HTMLPurifier** - HTML内容净化
- **GuzzleHTTP 6.5** - HTTP客户端

### 第三方服务
- **腾讯云 COS** - 对象存储
- **腾讯云 VOD** - 点播服务
- **微信支付/支付宝** - 支付接口
- **微信公众号** - 消息推送

## Project Conventions

### Code Style

#### PHP代码规范
- **PSR-1/PSR-12** 基本代码规范
- 类名使用 PascalCase（如 `AssignmentController`）
- 方法名使用 camelCase（如 `createAssignment()`）
- 常量使用 UPPER_SNAKE_CASE（如 `STATUS_PUBLISHED`）
- 私有属性/方法使用下划线前缀（可选）

#### 数据库字段规范
- 使用 snake_case（如 `user_id`, `create_time`）
- 时间字段统一使用 Unix 时间戳（int类型）
- 软删除使用 `delete_time` 字段（0表示未删除）
- JSON字段必须通过Model的Getter/Setter访问

#### 前端代码规范
- JavaScript变量使用 camelCase
- CSS类名使用 kebab-case 或 BEM规范
- Volt模板变量使用 snake_case

### Architecture Patterns

#### MVC架构
```
app/
├── Http/              # 控制器层
│   ├── Admin/         # 后台管理
│   ├── Home/          # 前台用户
│   └── Api/           # API接口
├── Models/            # 数据模型（ORM）
├── Repos/             # 仓储层（数据访问）
├── Services/          # 业务逻辑层
│   ├── Logic/         # 核心业务逻辑
│   └── Service/       # 通用服务
├── Validators/        # 表单验证器
├── Listeners/         # 事件监听器
└── Providers/         # 服务提供者
```

#### 分层职责
1. **Controller** - 接收请求，调用Service，返回响应（视图或JSON）
2. **Validator** - 验证请求参数
3. **Service** - 实现业务逻辑
4. **Repo** - 封装数据库操作
5. **Model** - ORM模型，定义表结构和关系

#### 核心设计模式
- **Repository Pattern** - 所有数据库操作通过Repo层
- **Service Layer** - 复杂业务逻辑封装在Service中
- **Event-Driven** - 使用 Listeners 处理异步任务和解耦
- **Dependency Injection** - 使用 Phalcon DI容器管理依赖

### Testing Strategy

#### 测试方法
- **功能测试** - 通过浏览器手动测试核心功能
- **数据库测试** - 使用测试环境数据库验证数据完整性
- **API测试** - 使用Postman或curl测试API端点

#### 测试环境
- 独立的测试服务器环境
- 测试数据库（非生产数据）
- 测试账号体系

#### 回归测试
- 修改核心功能后进行完整流程测试
- 关注字段名修改导致的连锁影响
- 验证前后台数据一致性

### Git Workflow

#### 分支策略
- `master/main` - 主分支，生产环境代码
- `develop` - 开发分支，集成最新功能
- `feature/*` - 功能分支
- `bugfix/*` - Bug修复分支
- `hotfix/*` - 紧急修复分支

#### 提交规范
遵循 [GIT_COMMIT_GUIDE.md](../GIT_COMMIT_GUIDE.md) 约定：
- `feat: 新功能描述` - 新功能
- `fix: Bug修复描述` - Bug修复
- `refactor: 重构描述` - 代码重构
- `docs: 文档更新` - 文档修改
- `style: 代码格式调整` - 格式化
- `test: 测试相关` - 测试代码
- `chore: 构建/工具链` - 构建配置

## Domain Context

### 核心业务领域

#### 1. 课程体系
- **课程 (Course)** - 包含多个章节的完整课程
- **章节 (Chapter)** - 课程的组成单元，包含课时
- **课时 (Lesson)** - 具体的学习内容（视频、文档等）
- **专栏 (Package)** - 课程包/学习路径

#### 2. 作业系统 ⚠️ **当前优化重点**
- **作业 (Assignment)** - 教学作业，包含多个题目
- **作业提交 (AssignmentSubmission)** - 学生的作业答案
- **题型支持**：选择题、多选题、简答题、编程题、文件上传
- **批改模式**：自动批改、手动批改、混合批改
- **关键字段规范** - 详见 [作业模块字段规范文档.md](../../作业模块字段规范文档.md)

#### 3. 用户系统
- **用户 (User)** - 学生、教师、管理员
- **角色权限** - 基于RBAC的权限控制
- **会员体系** - 会员等级、有效期管理

#### 4. 交易系统
- **订单 (Order)** - 课程/会员购买订单
- **支付 (Trade)** - 支付流水
- **退款 (Refund)** - 退款记录

#### 5. 互动系统
- **问答 (Question/Answer)** - 课程问答
- **评论 (Review/Comment)** - 课程评价
- **咨询 (Consult)** - 用户咨询

### 关键业务规则

#### 作业模块核心规则
1. **字段命名必须统一**：
   - ✅ `max_score` (总分) ❌ ~~`total_score`~~
   - ✅ `due_date` (截止时间) ❌ ~~`deadline`~~
   - ✅ `allow_late` (允许迟交) ❌ ~~`allow_resubmit`~~
   - ✅ `content` (内容) ❌ ~~`answers`~~, ~~`questions`~~
   
2. **JSON字段处理规范**：
   - 必须通过Model的`setXxxData()`方法保存
   - 空值使用`[]`而不是`''`或`null`
   - `toArray()`后不要再`json_decode()`
   
3. **提交状态管理**：
   - `status`: `draft`, `submitted`, `graded`, `returned`
   - `grade_status`: `pending`, `grading`, `completed`
   - 两个字段配合使用，区分批改进度

4. **批改模式规则**：
   - `auto` - 纯自动批改，`grader_id = null`
   - `manual` - 纯手动批改，`grader_id = owner_id`
   - `mixed` - 混合模式，选择题自动批改，主观题手动批改

## Important Constraints

### 技术约束
- **PHP版本**: 必须 >= 7.3（依赖Phalcon 3.4）
- **MySQL版本**: 必须 >= 5.7（使用JSON字段类型）
- **Redis版本**: 必须 >= 5.0
- **Phalcon扩展**: 必须安装 phalcon.so 扩展

### 数据库约束
- **外键约束**: 
  - `grader_id` 允许NULL，不要设置为0
  - 删除操作使用软删除（`delete_time`）
- **JSON字段**: 不能为空字符串，必须是有效JSON
- **ENUM字段**: 只能使用预定义的值

### 业务约束
- **作业提交**: 
  - 超过`max_attempts`次数不允许再提交
  - 超过`due_date`根据`allow_late`判断是否允许提交
- **自动评分**: 只对选择题生效，主观题需人工批改
- **权限控制**: 学生只能看到自己的提交，教师能看所有提交

### 性能约束
- 列表查询必须分页，每页不超过100条
- 大文件上传使用分片上传
- 频繁访问的数据使用Redis缓存

## External Dependencies

### 腾讯云服务
- **COS对象存储** - 存储视频、文档、图片等静态资源
- **VOD点播服务** - 处理视频转码、加密、播放
- **直播服务** - 实时视频直播推流和拉流

### 支付服务
- **微信支付** - 微信公众号/小程序支付
- **支付宝** - 网页支付/手机支付

### 消息推送
- **微信公众号** - 模板消息推送
- **短信服务** - 验证码和通知短信

### 开发工具
- **XDebug** - PHP调试工具
- **Redis Desktop Manager** - Redis可视化工具
- **Navicat/DBeaver** - 数据库管理工具

## Key Files and Directories

### 核心配置文件
- `config/config.php` - 主配置文件（数据库、Redis、腾讯云等）
- `config/routes.php` - 路由配置
- `config/events.php` - 事件监听配置
- `.env` - 环境变量（敏感配置）

### 重要文档
- `GIT_COMMIT_GUIDE.md` - Git提交规范
- `CONFIG_MANAGEMENT.md` - 配置管理说明
- `DEPLOYMENT_GUIDE_BAOTA.md` - 宝塔部署指南
- `作业模块字段规范文档.md` - 作业模块开发必读 ⚠️

### 数据库脚本
- `db/migrations/` - Phinx迁移文件
- `db/seeds/` - 测试数据种子
- `db/scripts/` - 常用SQL脚本

---

**最后更新**: 2025-10-15  
**维护者**: 项目开发团队

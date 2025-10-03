# 🔐 配置文件管理规范

## 📋 配置文件说明

### 配置文件分类

| 文件名 | 用途 | Git跟踪 | 说明 |
|--------|------|---------|------|
| `config.default.php` | 配置模板 | ✅ 是 | 包含所有配置项的模板，**不包含敏感信息** |
| `config.php` | 实际配置 | ❌ 否 | 包含真实的数据库密码、密钥等，**已被.gitignore忽略** |
| `config*.php` | 其他配置变体 | ❌ 否 | 如 config1.php, config-local.php 等，**已被.gitignore忽略** |

---

## 🚀 快速开始

### 新环境部署步骤

1️⃣ **复制配置模板**
```bash
cd course-tencent-cloud/config
cp config.default.php config.php
```

2️⃣ **修改配置文件**

编辑 `config.php`，修改以下关键配置：

```php
// 运行环境
$config['env'] = 'dev';  // 开发环境用 dev，生产环境用 pro

// 数据库配置
$config['db']['host'] = 'localhost';
$config['db']['dbname'] = 'your_database';
$config['db']['username'] = 'your_username';
$config['db']['password'] = 'your_password';

// Redis配置
$config['redis']['host'] = '127.0.0.1';
$config['redis']['auth'] = 'your_redis_password';
```

3️⃣ **验证配置**
```bash
php test-env.php
```

---

## ⚠️ 安全注意事项

### ❌ 禁止操作

1. **禁止提交 config.php 到Git仓库**
   ```bash
   # 错误示例
   git add config/config.php  # ❌ 永远不要这样做！
   ```

2. **禁止在 config.default.php 中写入真实密码**
   ```php
   // ❌ 错误示例
   $config['db']['password'] = 'MyRealPassword123';  // 禁止！
   
   // ✅ 正确示例
   $config['db']['password'] = 'your_password_here';  // 使用占位符
   ```

3. **禁止在代码中硬编码敏感信息**
   ```php
   // ❌ 错误
   $password = 'MyRealPassword123';
   
   // ✅ 正确
   $password = $this->config->get('db')['password'];
   ```

### ✅ 最佳实践

1. **使用环境变量（推荐用于生产环境）**
   ```php
   $config['db']['password'] = getenv('DB_PASSWORD') ?: 'default_dev_password';
   ```

2. **不同环境使用不同配置**
   - 开发环境：`config.php` (本地，不提交)
   - 测试环境：`config-test.php` (服务器上，不提交)
   - 生产环境：`config-prod.php` (服务器上，不提交)

3. **敏感配置存储在数据库**
   - COS密钥 → `kg_setting` 表
   - 支付密钥 → `kg_setting` 表
   - 通过后台管理界面配置，而不是配置文件

---

## 🔍 检查配置安全性

### 检查是否有敏感文件被跟踪

```bash
# 检查 config.php 是否被Git跟踪
git ls-files config/config.php

# 如果有输出，说明文件被跟踪了，需要移除
git rm --cached config/config.php
git commit -m "Remove sensitive config file from git"
```

### 检查历史提交中是否有敏感信息

```bash
# 搜索历史提交中是否包含密码关键词
git log -S "password" --all --oneline

# 如果发现敏感信息，需要清理Git历史（谨慎操作！）
# 建议联系DevOps团队处理
```

---

## 📦 团队协作规范

### 1. 配置文件更新流程

当需要添加新的配置项时：

1. **更新配置模板** `config.default.php`
   ```php
   // 添加新配置项，使用占位符
   $config['new_service']['api_key'] = 'your_api_key_here';
   ```

2. **提交配置模板到Git**
   ```bash
   git add config/config.default.php
   git commit -m "Add new service API key config"
   ```

3. **通知团队成员**
   - 在项目文档或聊天群中通知
   - 说明新配置项的用途和如何获取真实值
   - 提供配置示例

4. **团队成员更新本地配置**
   ```bash
   # 每个人在自己的 config.php 中添加真实配置
   $config['new_service']['api_key'] = 'real_api_key_value';
   ```

### 2. 代码审查要点

在进行Code Review时，检查：

- [ ] 是否有敏感信息硬编码
- [ ] 是否错误地提交了 config.php
- [ ] config.default.php 是否使用了占位符
- [ ] 新增配置项是否在文档中说明

---

## 🛠️ 故障排查

### 问题1：配置文件被误提交

**症状**: config.php 被提交到了Git仓库

**解决方案**:
```bash
# 1. 从Git中移除（保留本地文件）
git rm --cached config/config.php

# 2. 确认 .gitignore 中已包含
grep "config.php" .gitignore

# 3. 提交更改
git commit -m "Remove config.php from git tracking"

# 4. 如果已经push到远程仓库，需要清理历史
# 警告：这会重写Git历史，需要团队协调
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch config/config.php" \
  --prune-empty --tag-name-filter cat -- --all

# 5. 强制推送（谨慎！）
# git push origin --force --all
```

### 问题2：其他开发者没有配置文件

**症状**: 新成员克隆代码后报错 "config.php not found"

**解决方案**:
1. 提供 `config.default.php` 作为模板
2. 提供详细的配置说明文档（本文档）
3. 提供测试脚本 `test-env.php` 验证配置

---

## 📝 .gitignore 规则说明

当前 `.gitignore` 中的配置相关规则：

```gitignore
# 实际配置文件（包含敏感信息）
/config/config.php
/config/config*.php        # 匹配所有 config 开头的PHP文件

# 搜索引擎配置（可能包含索引路径）
/config/xs.course.ini
/config/xs.article.ini
/config/xs.question.ini

# 支付证书（敏感）
/config/alipay/*.crt
/config/wxpay/*.pem

# 开发环境测试文件
/test-env.php
/配置说明-开发环境.md
```

### 为什么使用 `/config/config*.php`？

这个规则会忽略所有以 `config` 开头的PHP文件，包括：
- `config.php` - 主配置文件
- `config1.php` - 备用配置
- `config-local.php` - 本地配置
- `config-dev.php` - 开发配置
- `config-prod.php` - 生产配置

这样可以避免任何配置变体被误提交。

---

## 📞 联系支持

如果遇到配置相关问题：

1. 查看本文档的故障排查部分
2. 运行 `php test-env.php` 诊断
3. 查看 `配置说明-开发环境.md`
4. 联系DevOps或项目负责人

---

**最后更新**: 2024年10月3日  
**维护者**: 项目DevOps团队


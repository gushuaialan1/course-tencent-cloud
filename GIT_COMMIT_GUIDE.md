# 📝 Git提交指南

## 当前状态检查结果 ✅

### ✅ 已忽略的敏感文件
```
config/config.php          # 主配置文件（包含数据库密码等）
config/config1.php         # 备用配置文件
config/xs.article.ini      # 搜索引擎配置
config/xs.course.ini       # 搜索引擎配置
config/xs.question.ini     # 搜索引擎配置
test-env.php               # 环境测试脚本
配置说明-开发环境.md       # 可能包含敏感信息的配置文档
```

### 📋 待提交的文件

#### 1. `.gitignore` (必须提交)
**修改内容**:
- 添加 `/config/config*.php` - 忽略所有config开头的PHP配置文件
- 添加 `/test-env.php` - 忽略测试脚本
- 添加 `/配置说明-开发环境.md` - 忽略本地配置文档

**为什么要提交**: 确保其他团队成员也能正确忽略敏感配置文件

#### 2. `CONFIG_MANAGEMENT.md` (可选提交)
**文件内容**: 配置文件管理规范和最佳实践
**是否包含敏感信息**: ❌ 否，只包含规范说明
**建议**: ✅ 提交，帮助团队成员正确管理配置

---

## 🚀 推荐的提交步骤

### 方案A：只提交 .gitignore（最小化提交）

```bash
# 1. 添加 .gitignore
git add .gitignore

# 2. 提交
git commit -m "chore: update .gitignore to exclude sensitive config files

- Add /config/config*.php to ignore all config variants
- Add /test-env.php and local config docs
- Prevent sensitive credentials from being committed"

# 3. 推送到远程仓库
git push origin master
```

### 方案B：提交 .gitignore 和配置管理文档（推荐）

```bash
# 1. 添加文件
git add .gitignore CONFIG_MANAGEMENT.md

# 2. 提交
git commit -m "chore: improve config file management

- Update .gitignore to exclude all config variants
- Add CONFIG_MANAGEMENT.md with best practices
- Add security guidelines for config files"

# 3. 推送到远程仓库
git push origin master
```

---

## ⚠️ 提交前检查清单

在执行 `git push` 之前，请确认：

- [ ] `config.php` 和 `config1.php` **不在** 待提交文件中
- [ ] `git status` 中的 "Changes to be committed" **只包含**非敏感文件
- [ ] 已经运行 `git status --ignored` 确认敏感文件被忽略
- [ ] 检查提交的文件内容，确保没有硬编码的密码、密钥

### 快速检查命令

```bash
# 查看即将提交的文件
git diff --cached --name-only

# 查看即将提交的内容
git diff --cached

# 确认没有敏感信息
git diff --cached | grep -i "password\|secret\|key" 
```

---

## 🔍 验证Git历史是否安全

### 检查历史提交中是否有配置文件

```bash
# 检查 config.php 是否曾经被提交
git log --all --full-history --oneline -- config/config.php

# 如果有输出，说明文件曾被提交，需要清理
```

### 如果发现历史中有敏感文件

**⚠️ 警告**: 以下操作会重写Git历史，必须与团队协调

```bash
# 方法1：使用 git filter-branch（适用于简单情况）
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch config/config.php" \
  --prune-empty --tag-name-filter cat -- --all

# 方法2：使用 BFG Repo-Cleaner（推荐，速度快）
# 下载 https://rtyley.github.io/bfg-repo-cleaner/
java -jar bfg.jar --delete-files config.php
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# 方法3：联系DevOps团队处理
```

---

## 📞 遇到问题时

### 问题1：不确定文件是否安全

**解决方案**:
1. 使用 `git diff --cached` 查看文件内容
2. 搜索是否包含密码、密钥等关键词
3. 如果不确定，不要提交，先咨询团队

### 问题2：误提交了敏感文件

**如果还没有 push**:
```bash
# 撤销最后一次提交，但保留更改
git reset --soft HEAD^

# 从暂存区移除敏感文件
git reset HEAD config/config.php

# 重新提交
git commit -m "your message"
```

**如果已经 push**:
```bash
# 立即联系团队，停止其他人拉取
# 然后清理历史（见上面的方法）
```

---

## ✅ 最终确认

执行以下命令，确保一切正常：

```bash
# 1. 查看当前状态
git status

# 2. 查看被忽略的文件
git status --ignored | grep -A 10 "Ignored files"

# 3. 确认 config.php 不在跟踪列表
git ls-files | grep config.php
# 如果没有输出，说明正确 ✅

# 4. 查看即将提交的内容
git diff --cached
```

---

**创建时间**: 2024年10月3日  
**用途**: 安全提交配置文件相关更改


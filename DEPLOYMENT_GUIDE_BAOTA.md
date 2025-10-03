# 🚀 宝塔面板 - 代码部署和更新指南

**适用环境**: 宝塔Linux面板  
**项目**: 酷瓜云课堂扩展项目  
**更新时间**: 2024年10月3日

---

## 📋 目录
- [首次部署](#首次部署)
- [日常代码更新](#日常代码更新)
- [配置文件管理](#配置文件管理)
- [常见问题](#常见问题)

---

## 🎯 首次部署

### 方法A：通过宝塔面板（推荐新手）

1. **登录宝塔面板**
   ```
   访问: http://你的服务器IP:8888
   ```

2. **进入网站目录**
   - 点击左侧菜单 `文件`
   - 进入网站根目录（通常是 `/www/wwwroot/你的域名`）

3. **删除或备份现有文件**
   ```bash
   # 如果目录不为空，先备份
   mv /www/wwwroot/你的域名 /www/wwwroot/你的域名.backup
   ```

4. **克隆Git仓库**
   
   **方式1：使用宝塔Git工具**
   - 在文件管理器中，点击顶部 `远程下载`
   - 输入Git仓库地址
   - 等待下载完成

   **方式2：使用SSH终端**（推荐）
   - 点击左侧菜单 `终端`
   - 执行以下命令：
   ```bash
   cd /www/wwwroot
   git clone https://github.com/你的用户名/你的仓库名.git 你的域名
   cd 你的域名/course-tencent-cloud
   ```

5. **设置权限**
   ```bash
   # 设置正确的所有者和权限
   chown -R www:www /www/wwwroot/你的域名
   chmod -R 755 /www/wwwroot/你的域名
   
   # 特殊目录需要写权限
   chmod -R 777 /www/wwwroot/你的域名/course-tencent-cloud/storage
   ```

6. **配置环境**
   - 复制配置文件：`cp config/config.default.php config/config.php`
   - 编辑 `config/config.php` 填入真实配置
   - 参考 `配置说明-开发环境.md`

---

## 🔄 日常代码更新（重点！）

### 方法1：SSH终端更新（推荐）⭐

**完整更新脚本**（推荐使用）：

```bash
#!/bin/bash
# 酷瓜云课堂代码更新脚本

echo "=========================================="
echo "  酷瓜云课堂 - 代码更新"
echo "=========================================="

# 1. 进入项目目录
cd /www/wwwroot/你的域名/course-tencent-cloud || exit

# 2. 备份配置文件（重要！）
echo "【1】备份配置文件..."
cp config/config.php /tmp/config.php.backup
cp config/xs.course.ini /tmp/xs.course.ini.backup 2>/dev/null
cp config/xs.article.ini /tmp/xs.article.ini.backup 2>/dev/null
cp config/xs.question.ini /tmp/xs.question.ini.backup 2>/dev/null
echo "✓ 配置文件已备份到 /tmp/"

# 3. 暂存本地修改（如果有）
echo ""
echo "【2】检查本地修改..."
git stash save "Auto stash before update $(date '+%Y-%m-%d %H:%M:%S')"
echo "✓ 本地修改已暂存"

# 4. 拉取最新代码
echo ""
echo "【3】拉取最新代码..."
git pull origin master
if [ $? -eq 0 ]; then
    echo "✓ 代码更新成功"
else
    echo "✗ 代码更新失败，请检查错误信息"
    exit 1
fi

# 5. 恢复配置文件
echo ""
echo "【4】恢复配置文件..."
cp /tmp/config.php.backup config/config.php
cp /tmp/xs.course.ini.backup config/xs.course.ini 2>/dev/null
cp /tmp/xs.article.ini.backup config/xs.article.ini 2>/dev/null
cp /tmp/xs.question.ini.backup config/xs.question.ini 2>/dev/null
echo "✓ 配置文件已恢复"

# 6. 更新Composer依赖（如果composer.json有变化）
echo ""
echo "【5】检查依赖更新..."
if git diff HEAD@{1} HEAD --name-only | grep -q "composer.json"; then
    echo "检测到composer.json更新，正在更新依赖..."
    composer install --no-dev --optimize-autoloader
    echo "✓ 依赖更新完成"
else
    echo "✓ 无需更新依赖"
fi

# 7. 运行数据库迁移（如果有新的迁移文件）
echo ""
echo "【6】检查数据库迁移..."
if git diff HEAD@{1} HEAD --name-only | grep -q "db/migrations"; then
    echo "检测到新的迁移文件..."
    php console.php migration run
    echo "✓ 数据库迁移完成"
else
    echo "✓ 无新的迁移"
fi

# 8. 清除缓存
echo ""
echo "【7】清除缓存..."
php console.php cache clear
rm -rf storage/cache/volt/*
rm -rf storage/cache/metadata/*
echo "✓ 缓存已清除"

# 9. 修复权限
echo ""
echo "【8】修复文件权限..."
chown -R www:www /www/wwwroot/你的域名/course-tencent-cloud
chmod -R 755 /www/wwwroot/你的域名/course-tencent-cloud
chmod -R 777 /www/wwwroot/你的域名/course-tencent-cloud/storage
echo "✓ 权限修复完成"

# 10. 完成
echo ""
echo "=========================================="
echo "  ✓ 代码更新完成！"
echo "=========================================="
echo ""
echo "更新内容:"
git log HEAD@{1}..HEAD --oneline
echo ""
```

**使用方法**：

```bash
# 1. 创建更新脚本
cd /www/wwwroot/你的域名/course-tencent-cloud
nano update.sh

# 2. 粘贴上面的脚本内容，修改路径
# 将 "你的域名" 替换为实际域名

# 3. 添加执行权限
chmod +x update.sh

# 4. 执行更新
./update.sh
```

### 方法2：手动分步更新（新手友好）

**步骤1：备份配置文件**
```bash
# 进入项目目录
cd /www/wwwroot/你的域名/course-tencent-cloud

# 备份配置文件到安全位置
cp config/config.php /root/config.php.backup
cp config/xs.course.ini /root/xs.course.ini.backup 2>/dev/null
cp config/xs.article.ini /root/xs.article.ini.backup 2>/dev/null
```

**步骤2：拉取最新代码**
```bash
# 查看当前分支
git branch

# 查看远程更新
git fetch origin

# 拉取并合并
git pull origin master
```

**步骤3：恢复配置文件**
```bash
# 恢复备份的配置
cp /root/config.php.backup config/config.php
cp /root/xs.course.ini.backup config/xs.course.ini 2>/dev/null
cp /root/xs.article.ini.backup config/xs.article.ini 2>/dev/null
```

**步骤4：更新依赖（如果需要）**
```bash
# 检查composer.json是否有变化
git diff HEAD@{1} HEAD composer.json

# 如果有变化，更新依赖
composer install --no-dev
```

**步骤5：运行数据库迁移**
```bash
# 查看是否有新的迁移文件
ls -l db/migrations/

# 运行迁移
php console.php migration run
```

**步骤6：清除缓存**
```bash
# 清除应用缓存
php console.php cache clear

# 清除Volt模板缓存
rm -rf storage/cache/volt/*

# 清除元数据缓存
rm -rf storage/cache/metadata/*
```

**步骤7：修复权限**
```bash
# 修复所有者
chown -R www:www /www/wwwroot/你的域名

# 修复权限
chmod -R 755 /www/wwwroot/你的域名
chmod -R 777 /www/wwwroot/你的域名/course-tencent-cloud/storage
```

### 方法3：使用宝塔面板Git管理器

1. **进入Git管理**
   - 文件管理 → 进入项目目录
   - 右键点击项目文件夹 → `Git`

2. **拉取更新**
   - 点击 `拉取(Pull)`
   - 选择分支 `master`
   - 点击确认

3. **手动恢复配置**
   - 文件管理器中恢复 `config/config.php`
   - 从备份中复制回来

---

## 🔐 配置文件管理（重要！）

### ⚠️ 关键注意事项

1. **配置文件永远不会被拉取覆盖**
   - `config/config.php` 已在 `.gitignore` 中
   - Git不会跟踪这个文件
   - 拉取代码时不会被覆盖

2. **但是为了保险，仍然建议备份**
   ```bash
   # 每次更新前都备份
   cp config/config.php /root/config_backup_$(date +%Y%m%d_%H%M%S).php
   ```

3. **配置文件的正确位置**
   - 数据库、Redis等基础配置 → `config/config.php`
   - COS、支付等业务配置 → 数据库 `kg_setting` 表

### 配置文件检查清单

更新代码后，检查以下配置：

```bash
# 1. 检查config.php是否存在
ls -l config/config.php

# 2. 检查配置文件权限
chmod 644 config/config.php

# 3. 验证配置文件内容
head -n 20 config/config.php

# 4. 测试配置是否正确
php test-env.php
```

---

## 🛠️ 常见问题

### 问题1：git pull时提示冲突

**症状**:
```
error: Your local changes to the following files would be overwritten by merge:
  config/config.php
```

**解决方案**:
```bash
# 方法1：暂存本地修改
git stash
git pull origin master
git stash pop

# 方法2：强制使用远程版本（谨慎！）
git fetch origin
git reset --hard origin/master

# 然后恢复配置文件
cp /root/config.php.backup config/config.php
```

### 问题2：权限不足

**症状**:
```
Permission denied
```

**解决方案**:
```bash
# 修复所有者
chown -R www:www /www/wwwroot/你的域名

# 给当前用户临时权限
chmod -R 755 /www/wwwroot/你的域名
```

### 问题3：Composer依赖安装失败

**症状**:
```
composer install failed
```

**解决方案**:
```bash
# 清除Composer缓存
composer clear-cache

# 重新安装
composer install --no-dev --prefer-dist

# 如果内存不足，增加内存限制
php -d memory_limit=-1 /usr/bin/composer install --no-dev
```

### 问题4：数据库迁移失败

**症状**:
```
Migration failed
```

**解决方案**:
```bash
# 1. 检查数据库连接
mysql -h localhost -u 数据库用户 -p

# 2. 查看迁移状态
php console.php migration status

# 3. 手动运行SQL（如果必要）
mysql -h localhost -u 数据库用户 -p 数据库名 < db/migrations/某个迁移.sql
```

### 问题5：更新后页面500错误

**检查步骤**:

```bash
# 1. 查看错误日志
tail -f storage/log/error.log

# 2. 检查PHP错误日志
tail -f /www/wwwroot/你的域名/course-tencent-cloud/storage/log/app.log

# 3. 检查Nginx错误日志（宝塔面板）
tail -f /www/server/panel/logs/error.log

# 4. 清除所有缓存
php console.php cache clear
rm -rf storage/cache/volt/*
rm -rf storage/cache/metadata/*

# 5. 检查权限
chmod -R 777 storage/
```

---

## 📋 更新后验证清单

更新完成后，请验证以下项目：

- [ ] ✅ 前台首页可以正常访问
- [ ] ✅ 后台登录正常
- [ ] ✅ 课程列表正常显示
- [ ] ✅ 文件上传功能正常
- [ ] ✅ 知识图谱功能正常
- [ ] ✅ 作业系统功能正常
- [ ] ✅ 没有500错误
- [ ] ✅ 错误日志无严重错误

### 快速验证脚本

```bash
#!/bin/bash
echo "验证系统状态..."

# 检查Web服务
curl -I http://你的域名 | head -n 1

# 检查后台
curl -I http://你的域名/admin | head -n 1

# 检查错误日志
echo "最近的错误:"
tail -n 20 storage/log/error.log

echo "验证完成"
```

---

## 🔄 回滚操作（如果更新出问题）

### 快速回滚到上一个版本

```bash
# 1. 查看提交历史
git log --oneline -5

# 2. 回滚到上一个版本
git reset --hard HEAD^

# 或回滚到指定版本
git reset --hard <commit-id>

# 3. 清除缓存
php console.php cache clear

# 4. 重启PHP
# 在宝塔面板中: 软件商店 → PHP → 重启
```

---

## 📞 技术支持

### 宝塔面板相关
- 宝塔官方论坛: https://www.bt.cn/bbs/
- 宝塔文档: https://www.bt.cn/bbs/forum-39-1.html

### 项目相关
- 查看项目文档: `酷瓜云课堂扩展功能集成分析与开发指南.md`
- 配置问题: `CONFIG_MANAGEMENT.md`
- Git问题: `GIT_COMMIT_GUIDE.md`

---

## 📝 推荐的更新流程总结

```bash
# 1. 备份配置
cp config/config.php /root/config_backup.php

# 2. 拉取代码
git pull origin master

# 3. 恢复配置（如果需要）
cp /root/config_backup.php config/config.php

# 4. 更新依赖（如果需要）
composer install --no-dev

# 5. 运行迁移
php console.php migration run

# 6. 清除缓存
php console.php cache clear
rm -rf storage/cache/volt/*

# 7. 修复权限
chown -R www:www .
chmod -R 777 storage/

# 8. 验证
curl -I http://你的域名
```

---

**最后更新**: 2024年10月3日  
**维护者**: 项目DevOps团队

**重要提醒**: 
- ⚠️ 生产环境更新前务必备份数据库
- ⚠️ 建议在低峰时段更新
- ⚠️ 更新前通知用户系统维护


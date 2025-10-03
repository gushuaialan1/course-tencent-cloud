#!/bin/bash
###########################################
# 酷瓜云课堂 - 宝塔环境代码更新脚本
# 使用方法: ./update-code.sh
###########################################

# 配置项（请修改为你的实际路径）
PROJECT_PATH="/www/wwwroot/你的域名/course-tencent-cloud"
BACKUP_PATH="/root/backups"

echo "=========================================="
echo "  酷瓜云课堂 - 代码更新"
echo "  时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "=========================================="
echo ""

# 创建备份目录
mkdir -p $BACKUP_PATH

# 1. 进入项目目录
echo "【1/9】进入项目目录..."
cd $PROJECT_PATH || { echo "✗ 项目目录不存在: $PROJECT_PATH"; exit 1; }
echo "✓ 当前目录: $(pwd)"
echo ""

# 2. 备份配置文件
echo "【2/9】备份配置文件..."
BACKUP_TIME=$(date '+%Y%m%d_%H%M%S')
cp config/config.php $BACKUP_PATH/config_$BACKUP_TIME.php 2>/dev/null && echo "✓ config.php 已备份"
cp config/xs.course.ini $BACKUP_PATH/xs.course_$BACKUP_TIME.ini 2>/dev/null && echo "✓ xs.course.ini 已备份"
cp config/xs.article.ini $BACKUP_PATH/xs.article_$BACKUP_TIME.ini 2>/dev/null && echo "✓ xs.article.ini 已备份"
cp config/xs.question.ini $BACKUP_PATH/xs.question_$BACKUP_TIME.ini 2>/dev/null && echo "✓ xs.question.ini 已备份"
echo "✓ 配置文件已备份到: $BACKUP_PATH"
echo ""

# 3. 检查Git状态
echo "【3/9】检查Git状态..."
git status
echo ""

# 4. 暂存本地修改
echo "【4/9】暂存本地修改..."
if [ -n "$(git status --porcelain)" ]; then
    git stash save "Auto stash before update $BACKUP_TIME"
    echo "✓ 本地修改已暂存"
else
    echo "✓ 无本地修改"
fi
echo ""

# 5. 拉取最新代码
echo "【5/9】拉取最新代码..."
echo "远程仓库: $(git remote -v | grep fetch)"
echo "当前分支: $(git branch --show-current)"
echo ""
echo "开始拉取..."

git pull origin master

if [ $? -eq 0 ]; then
    echo "✓ 代码更新成功"
    echo ""
    echo "更新内容:"
    git log HEAD@{1}..HEAD --oneline --graph --decorate
else
    echo "✗ 代码更新失败！"
    echo ""
    echo "可能的原因:"
    echo "1. 网络连接问题"
    echo "2. Git仓库权限问题"
    echo "3. 本地有冲突文件"
    echo ""
    echo "恢复配置文件..."
    cp $BACKUP_PATH/config_$BACKUP_TIME.php config/config.php 2>/dev/null
    exit 1
fi
echo ""

# 6. 恢复配置文件
echo "【6/9】恢复配置文件..."
cp $BACKUP_PATH/config_$BACKUP_TIME.php config/config.php 2>/dev/null && echo "✓ config.php 已恢复"
cp $BACKUP_PATH/xs.course_$BACKUP_TIME.ini config/xs.course.ini 2>/dev/null && echo "✓ xs.course.ini 已恢复"
cp $BACKUP_PATH/xs.article_$BACKUP_TIME.ini config/xs.article.ini 2>/dev/null && echo "✓ xs.article.ini 已恢复"
cp $BACKUP_PATH/xs.question_$BACKUP_TIME.ini config/xs.question.ini 2>/dev/null && echo "✓ xs.question.ini 已恢复"
echo ""

# 7. 检查是否需要更新依赖
echo "【7/9】检查Composer依赖..."
if git diff HEAD@{1} HEAD --name-only | grep -q "composer.json\|composer.lock"; then
    echo "检测到composer文件变化，开始更新依赖..."
    composer install --no-dev --optimize-autoloader
    if [ $? -eq 0 ]; then
        echo "✓ 依赖更新成功"
    else
        echo "⚠ 依赖更新失败，但继续执行..."
    fi
else
    echo "✓ 依赖无需更新"
fi
echo ""

# 8. 检查是否需要运行数据库迁移
echo "【8/9】检查数据库迁移..."
if git diff HEAD@{1} HEAD --name-only | grep -q "db/migrations/"; then
    echo "检测到新的迁移文件，开始运行迁移..."
    php console.php migration run
    if [ $? -eq 0 ]; then
        echo "✓ 数据库迁移成功"
    else
        echo "⚠ 数据库迁移失败，请手动检查"
    fi
else
    echo "✓ 无新的数据库迁移"
fi
echo ""

# 9. 清除缓存和修复权限
echo "【9/9】清除缓存和修复权限..."

# 清除应用缓存
php console.php cache clear 2>/dev/null && echo "✓ 应用缓存已清除"

# 清除模板缓存
rm -rf storage/cache/volt/* 2>/dev/null && echo "✓ Volt模板缓存已清除"
rm -rf storage/cache/metadata/* 2>/dev/null && echo "✓ 元数据缓存已清除"

# 修复权限
chown -R www:www $PROJECT_PATH && echo "✓ 文件所有者已修复"
chmod -R 755 $PROJECT_PATH && echo "✓ 文件权限已修复"
chmod -R 777 $PROJECT_PATH/storage && echo "✓ storage目录权限已修复"

echo ""
echo "=========================================="
echo "  ✓ 代码更新完成！"
echo "=========================================="
echo ""
echo "📋 更新摘要:"
echo "  - 备份时间: $BACKUP_TIME"
echo "  - 备份位置: $BACKUP_PATH"
echo "  - 项目路径: $PROJECT_PATH"
echo ""
echo "🔍 下一步建议:"
echo "  1. 访问网站检查功能是否正常"
echo "  2. 查看错误日志: tail -f storage/log/error.log"
echo "  3. 如有问题，运行回滚: git reset --hard HEAD^"
echo ""


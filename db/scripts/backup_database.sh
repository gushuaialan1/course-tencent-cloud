#!/bin/bash
#########################################################
# 数据库备份脚本
# 使用方法: bash backup_database.sh
#########################################################

# 数据库配置（从 config.php 读取）
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="kugua_mooc"
DB_USER="kugua_user"
DB_PASS="67GEyZ56k2n6HwsB"

# 备份目录
BACKUP_DIR="$(cd "$(dirname "$0")" && pwd)/../../storage/backup"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/kugua_mooc_backup_${TIMESTAMP}.sql"

# 创建备份目录
mkdir -p "$BACKUP_DIR"

echo "======================================"
echo "开始备份数据库: ${DB_NAME}"
echo "备份时间: $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================"

# 执行备份
mysqldump -h"${DB_HOST}" \
          -P"${DB_PORT}" \
          -u"${DB_USER}" \
          -p"${DB_PASS}" \
          --default-character-set=utf8mb4 \
          --single-transaction \
          --routines \
          --triggers \
          --events \
          --hex-blob \
          "${DB_NAME}" > "${BACKUP_FILE}"

# 检查备份结果
if [ $? -eq 0 ]; then
    # 压缩备份文件
    gzip "${BACKUP_FILE}"
    BACKUP_FILE="${BACKUP_FILE}.gz"
    
    FILE_SIZE=$(ls -lh "${BACKUP_FILE}" | awk '{print $5}')
    
    echo ""
    echo "✅ 备份成功！"
    echo "备份文件: ${BACKUP_FILE}"
    echo "文件大小: ${FILE_SIZE}"
    echo ""
    
    # 删除7天前的旧备份
    find "$BACKUP_DIR" -name "*.sql.gz" -mtime +7 -delete
    echo "已清理7天前的旧备份"
else
    echo ""
    echo "❌ 备份失败！"
    echo "请检查数据库连接配置和权限"
    exit 1
fi

echo "======================================"


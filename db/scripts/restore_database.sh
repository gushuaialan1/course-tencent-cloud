#!/bin/bash
#########################################################
# 数据库恢复脚本
# 使用方法: bash restore_database.sh [备份文件路径]
#########################################################

# 数据库配置
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="kugua_mooc"
DB_USER="kugua_user"
DB_PASS="67GEyZ56k2n6HwsB"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 获取备份目录
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_DIR="$(cd "${SCRIPT_DIR}/../../storage/backup" && pwd)"

echo "======================================"
echo "数据库恢复工具"
echo "======================================"
echo ""

# 如果没有指定备份文件，列出可用备份
if [ -z "$1" ]; then
    echo "可用的备份文件："
    echo ""
    
    ls -lht "${BACKUP_DIR}"/*.sql.gz 2>/dev/null | head -10 | awk '{print NR". "$9" ("$5", "$6" "$7")"}'
    
    echo ""
    echo "使用方法:"
    echo "  bash restore_database.sh <备份文件路径>"
    echo ""
    echo "示例:"
    echo "  bash restore_database.sh ${BACKUP_DIR}/kugua_mooc_backup_20250115_120000.sql.gz"
    exit 0
fi

BACKUP_FILE="$1"

# 检查备份文件是否存在
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}❌ 备份文件不存在: ${BACKUP_FILE}${NC}"
    exit 1
fi

echo "备份文件: ${BACKUP_FILE}"
echo "目标数据库: ${DB_NAME}"
echo ""

# 确认操作
echo -e "${RED}⚠️  警告：此操作将覆盖当前数据库的所有数据！${NC}"
read -p "是否继续？(yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "已取消恢复"
    exit 0
fi

echo ""
echo "======================================"
echo "开始恢复数据库..."
echo "======================================"
echo ""

# 解压并恢复
if [[ "$BACKUP_FILE" == *.gz ]]; then
    gunzip < "$BACKUP_FILE" | mysql -h"${DB_HOST}" \
                                    -P"${DB_PORT}" \
                                    -u"${DB_USER}" \
                                    -p"${DB_PASS}" \
                                    --default-character-set=utf8mb4 \
                                    "${DB_NAME}"
else
    mysql -h"${DB_HOST}" \
          -P"${DB_PORT}" \
          -u"${DB_USER}" \
          -p"${DB_PASS}" \
          --default-character-set=utf8mb4 \
          "${DB_NAME}" < "$BACKUP_FILE"
fi

# 检查恢复结果
if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ 数据库恢复成功！${NC}"
    echo ""
else
    echo ""
    echo -e "${RED}❌ 数据库恢复失败！${NC}"
    exit 1
fi

echo "======================================"


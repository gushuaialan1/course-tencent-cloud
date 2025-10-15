#!/bin/bash
#########################################################
# 作业模块数据迁移脚本
# ⚠️  执行前务必先备份数据库！
# 使用方法: bash run_assignment_migrations.sh
#########################################################

# 数据库配置
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="kugua_mooc"
DB_USER="kugua_user"
DB_PASS="67GEyZ56k2n6HwsB"

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
MIGRATION_DIR="${PROJECT_ROOT}/db/migrations"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "======================================"
echo "作业模块数据迁移工具"
echo "======================================"
echo ""

# 第一步：备份数据库
echo -e "${YELLOW}步骤 1/5: 备份数据库${NC}"
echo "正在执行备份..."

bash "${SCRIPT_DIR}/backup_database.sh"

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ 备份失败，终止迁移！${NC}"
    exit 1
fi

echo ""
read -p "备份已完成，是否继续迁移？(yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo "已取消迁移"
    exit 0
fi

echo ""
echo "======================================"

# MySQL连接函数
function run_mysql() {
    mysql -h"${DB_HOST}" \
          -P"${DB_PORT}" \
          -u"${DB_USER}" \
          -p"${DB_PASS}" \
          --default-character-set=utf8mb4 \
          "${DB_NAME}" \
          "$@"
}

# 第二步：检查当前数据
echo -e "${YELLOW}步骤 2/5: 检查当前数据${NC}"

ASSIGNMENT_COUNT=$(run_mysql -sN -e "SELECT COUNT(*) FROM kg_assignment WHERE delete_time = 0")
SUBMISSION_COUNT=$(run_mysql -sN -e "SELECT COUNT(*) FROM kg_assignment_submission WHERE delete_time = 0")

echo "发现作业数量: ${ASSIGNMENT_COUNT}"
echo "发现提交记录: ${SUBMISSION_COUNT}"
echo ""

if [ "$ASSIGNMENT_COUNT" = "0" ] && [ "$SUBMISSION_COUNT" = "0" ]; then
    echo -e "${GREEN}✅ 数据库为空，可以安全迁移${NC}"
else
    echo -e "${YELLOW}⚠️  数据库包含现有数据，将进行格式转换${NC}"
    read -p "是否继续？(yes/no): " CONFIRM2
    
    if [ "$CONFIRM2" != "yes" ]; then
        echo "已取消迁移"
        exit 0
    fi
fi

echo ""
echo "======================================"

# 第三步：迁移作业题目格式
echo -e "${YELLOW}步骤 3/5: 迁移作业题目格式${NC}"
echo "执行文件: MigrateAssignmentQuestionsToNewFormat.php"

php "${MIGRATION_DIR}/MigrateAssignmentQuestionsToNewFormat.php"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ 题目格式迁移成功${NC}"
else
    echo -e "${RED}❌ 题目格式迁移失败！${NC}"
    exit 1
fi

echo ""
echo "======================================"

# 第四步：迁移学生答案格式
echo -e "${YELLOW}步骤 4/5: 迁移学生答案格式${NC}"
echo "执行文件: MigrateSubmissionAnswersToNewFormat.php"

php "${MIGRATION_DIR}/MigrateSubmissionAnswersToNewFormat.php"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ 答案格式迁移成功${NC}"
else
    echo -e "${RED}❌ 答案格式迁移失败！${NC}"
    exit 1
fi

echo ""
echo "======================================"

# 第五步：迁移提交状态
echo -e "${YELLOW}步骤 5/5: 迁移提交状态字段${NC}"
echo "执行文件: MigrateSubmissionStatusToSimplified.php"

php "${MIGRATION_DIR}/MigrateSubmissionStatusToSimplified.php"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ 状态迁移成功${NC}"
else
    echo -e "${RED}❌ 状态迁移失败！${NC}"
    exit 1
fi

echo ""
echo "======================================"
echo -e "${GREEN}🎉 所有迁移已完成！${NC}"
echo "======================================"
echo ""

# 验证迁移结果
echo "验证迁移结果..."
echo ""

MIGRATED_ASSIGNMENTS=$(run_mysql -sN -e "
    SELECT COUNT(*) FROM kg_assignment 
    WHERE delete_time = 0 
    AND JSON_VALID(content) = 1 
    AND JSON_CONTAINS_PATH(content, 'one', '$.questions')
")

MIGRATED_SUBMISSIONS=$(run_mysql -sN -e "
    SELECT COUNT(*) FROM kg_assignment_submission 
    WHERE delete_time = 0 
    AND JSON_VALID(content) = 1 
    AND JSON_CONTAINS_PATH(content, 'one', '$.answers')
")

echo "已迁移作业: ${MIGRATED_ASSIGNMENTS} / ${ASSIGNMENT_COUNT}"
echo "已迁移提交: ${MIGRATED_SUBMISSIONS} / ${SUBMISSION_COUNT}"
echo ""

if [ "$MIGRATED_ASSIGNMENTS" = "$ASSIGNMENT_COUNT" ] && [ "$MIGRATED_SUBMISSIONS" = "$SUBMISSION_COUNT" ]; then
    echo -e "${GREEN}✅ 数据完整性验证通过！${NC}"
else
    echo -e "${YELLOW}⚠️  部分数据未迁移，请检查日志${NC}"
fi

echo ""
echo "备份文件位置: ${PROJECT_ROOT}/storage/backup/"
echo ""


#!/bin/bash
#########################################################
# 检查数据迁移状态
# 使用方法: bash check_migration_status.sh
#########################################################

# 数据库配置
DB_HOST="localhost"
DB_PORT="3306"
DB_NAME="kugua_mooc"
DB_USER="kugua_user"
DB_PASS="67GEyZ56k2n6HwsB"

# 颜色输出
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# MySQL连接函数
function run_query() {
    mysql -h"${DB_HOST}" \
          -P"${DB_PORT}" \
          -u"${DB_USER}" \
          -p"${DB_PASS}" \
          --default-character-set=utf8mb4 \
          "${DB_NAME}" \
          -sN \
          -e "$1"
}

echo "======================================"
echo "作业模块数据迁移状态检查"
echo "======================================"
echo ""

# 1. 检查作业表
echo "【作业表 - kg_assignment】"
echo "----------------------------------------"

TOTAL_ASSIGNMENTS=$(run_query "SELECT COUNT(*) FROM kg_assignment WHERE deleted = 0")
echo "总作业数: ${TOTAL_ASSIGNMENTS}"

if [ "$TOTAL_ASSIGNMENTS" -gt 0 ]; then
    # 检查新格式
    NEW_FORMAT=$(run_query "
        SELECT COUNT(*) FROM kg_assignment 
        WHERE deleted = 0 
        AND JSON_VALID(content) = 1 
        AND JSON_CONTAINS_PATH(content, 'one', '\$.questions')
    ")
    
    # 检查旧格式
    OLD_FORMAT=$(run_query "
        SELECT COUNT(*) FROM kg_assignment 
        WHERE deleted = 0 
        AND JSON_VALID(content) = 1 
        AND NOT JSON_CONTAINS_PATH(content, 'one', '\$.questions')
    ")
    
    echo "新格式作业: ${NEW_FORMAT}"
    echo "旧格式作业: ${OLD_FORMAT}"
    
    if [ "$OLD_FORMAT" -eq 0 ]; then
        echo -e "${GREEN}✅ 所有作业已迁移到新格式${NC}"
    else
        echo -e "${YELLOW}⚠️  还有 ${OLD_FORMAT} 个作业需要迁移${NC}"
    fi
    
    # 显示示例
    echo ""
    echo "示例作业数据："
    run_query "
        SELECT id, title, 
               LEFT(content, 100) as content_preview 
        FROM kg_assignment 
        WHERE deleted = 0 
        LIMIT 1
    " | head -3
fi

echo ""
echo "【提交表 - kg_assignment_submission】"
echo "----------------------------------------"

TOTAL_SUBMISSIONS=$(run_query "SELECT COUNT(*) FROM kg_assignment_submission WHERE deleted = 0")
echo "总提交数: ${TOTAL_SUBMISSIONS}"

if [ "$TOTAL_SUBMISSIONS" -gt 0 ]; then
    # 检查答案新格式
    NEW_ANSWER_FORMAT=$(run_query "
        SELECT COUNT(*) FROM kg_assignment_submission 
        WHERE deleted = 0 
        AND JSON_VALID(content) = 1 
        AND JSON_CONTAINS_PATH(content, 'one', '\$.answers')
    ")
    
    OLD_ANSWER_FORMAT=$((TOTAL_SUBMISSIONS - NEW_ANSWER_FORMAT))
    
    echo "新格式答案: ${NEW_ANSWER_FORMAT}"
    echo "旧格式答案: ${OLD_ANSWER_FORMAT}"
    
    if [ "$OLD_ANSWER_FORMAT" -eq 0 ]; then
        echo -e "${GREEN}✅ 所有答案已迁移到新格式${NC}"
    else
        echo -e "${YELLOW}⚠️  还有 ${OLD_ANSWER_FORMAT} 个答案需要迁移${NC}"
    fi
    
    echo ""
    
    # 检查状态分布
    echo "状态分布："
    run_query "
        SELECT 
            status,
            COUNT(*) as count,
            ROUND(COUNT(*) * 100.0 / ${TOTAL_SUBMISSIONS}, 2) as percentage
        FROM kg_assignment_submission 
        WHERE deleted = 0 
        GROUP BY status
        ORDER BY count DESC
    " | awk '{printf "  %-20s %5s (%s%%)\n", $1, $2, $3}'
    
    echo ""
    
    # 检查是否还在使用 grade_status
    USING_GRADE_STATUS=$(run_query "
        SELECT COUNT(*) FROM kg_assignment_submission 
        WHERE deleted = 0 
        AND grade_status IS NOT NULL 
        AND grade_status != ''
    ")
    
    if [ "$USING_GRADE_STATUS" -eq 0 ]; then
        echo -e "${GREEN}✅ 已完全迁移到简化状态（不再使用grade_status）${NC}"
    else
        echo -e "${YELLOW}⚠️  还有 ${USING_GRADE_STATUS} 条记录使用旧的grade_status字段${NC}"
    fi
fi

echo ""
echo "【评分详情检查】"
echo "----------------------------------------"

if [ "$TOTAL_SUBMISSIONS" -gt 0 ]; then
    VALID_GRADE_DETAILS=$(run_query "
        SELECT COUNT(*) FROM kg_assignment_submission 
        WHERE deleted = 0 
        AND grade_details IS NOT NULL
        AND JSON_VALID(grade_details) = 1
        AND JSON_CONTAINS_PATH(grade_details, 'one', '\$.grading')
        AND JSON_CONTAINS_PATH(grade_details, 'one', '\$.summary')
    ")
    
    echo "符合新格式的评分详情: ${VALID_GRADE_DETAILS} / ${TOTAL_SUBMISSIONS}"
    
    if [ "$VALID_GRADE_DETAILS" -eq "$TOTAL_SUBMISSIONS" ]; then
        echo -e "${GREEN}✅ 所有评分详情符合新格式${NC}"
    fi
fi

echo ""
echo "======================================"
echo "检查完成"
echo "======================================"


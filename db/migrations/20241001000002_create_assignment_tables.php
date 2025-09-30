<?php
/**
 * 作业系统数据库迁移
 * 创建作业表和作业提交表
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class CreateAssignmentTables extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        // 创建作业表
        $this->createAssignmentTable();
        
        // 创建作业提交表  
        $this->createAssignmentSubmissionTable();
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        $this->table('kg_assignment_submission')->drop()->save();
        $this->table('kg_assignment')->drop()->save();
    }

    /**
     * 创建作业表
     */
    private function createAssignmentTable()
    {
        $table = $this->table('kg_assignment', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '作业表'
        ]);

        $table->addColumn('title', 'string', [
            'limit' => 200,
            'null' => false,
            'comment' => '作业标题'
        ])
        ->addColumn('description', 'text', [
            'null' => true,
            'comment' => '作业描述'
        ])
        ->addColumn('course_id', 'integer', [
            'null' => false,
            'comment' => '关联课程ID'
        ])
        ->addColumn('chapter_id', 'integer', [
            'null' => true,
            'comment' => '关联章节ID'
        ])
        ->addColumn('assignment_type', 'enum', [
            'values' => ['choice', 'essay', 'upload', 'mixed'],
            'default' => 'mixed',
            'null' => false,
            'comment' => '作业类型'
        ])
        ->addColumn('max_score', 'decimal', [
            'precision' => 5,
            'scale' => 2,
            'default' => 100.00,
            'null' => false,
            'comment' => '总分'
        ])
        ->addColumn('due_date', 'integer', [
            'null' => false,
            'comment' => '截止时间'
        ])
        ->addColumn('allow_late', 'boolean', [
            'default' => false,
            'comment' => '是否允许迟交'
        ])
        ->addColumn('late_penalty', 'decimal', [
            'precision' => 3,
            'scale' => 2,
            'default' => 0.00,
            'comment' => '迟交扣分比例'
        ])
        ->addColumn('grade_mode', 'enum', [
            'values' => ['auto', 'manual', 'mixed'],
            'default' => 'manual',
            'null' => false,
            'comment' => '评分模式'
        ])
        ->addColumn('rubric', 'json', [
            'null' => true,
            'comment' => '评分标准'
        ])
        ->addColumn('instructions', 'text', [
            'null' => true,
            'comment' => '作业说明'
        ])
        ->addColumn('attachments', 'json', [
            'null' => true,
            'comment' => '附件列表'
        ])
        ->addColumn('content', 'longtext', [
            'null' => true,
            'comment' => '作业内容(题目详情)'
        ])
        ->addColumn('reference_answer', 'longtext', [
            'null' => true,
            'comment' => '参考答案'
        ])
        ->addColumn('max_attempts', 'integer', [
            'default' => 1,
            'comment' => '最大提交次数'
        ])
        ->addColumn('time_limit', 'integer', [
            'default' => 0,
            'comment' => '时间限制(分钟)'
        ])
        ->addColumn('status', 'enum', [
            'values' => ['draft', 'published', 'closed', 'archived'],
            'default' => 'draft',
            'null' => false,
            'comment' => '作业状态'
        ])
        ->addColumn('publish_time', 'integer', [
            'default' => 0,
            'comment' => '发布时间'
        ])
        ->addColumn('visibility', 'json', [
            'null' => true,
            'comment' => '可见性设置'
        ])
        ->addColumn('owner_id', 'integer', [
            'null' => false,
            'comment' => '创建者ID'
        ])
        ->addColumn('create_time', 'integer', [
            'null' => false,
            'comment' => '创建时间'
        ])
        ->addColumn('update_time', 'integer', [
            'null' => false,
            'comment' => '更新时间'
        ])
        ->addColumn('delete_time', 'integer', [
            'default' => 0,
            'comment' => '删除时间'
        ])
        ->addIndex(['course_id'], ['name' => 'idx_course_id'])
        ->addIndex(['chapter_id'], ['name' => 'idx_chapter_id'])
        ->addIndex(['owner_id'], ['name' => 'idx_owner_id'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['due_date'], ['name' => 'idx_due_date'])
        ->addIndex(['delete_time'], ['name' => 'idx_delete_time'])
        ->create();
    }

    /**
     * 创建作业提交表
     */
    private function createAssignmentSubmissionTable()
    {
        $table = $this->table('kg_assignment_submission', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '作业提交表'
        ]);

        $table->addColumn('assignment_id', 'integer', [
            'null' => false,
            'comment' => '作业ID'
        ])
        ->addColumn('user_id', 'integer', [
            'null' => false,
            'comment' => '学生ID'
        ])
        ->addColumn('content', 'longtext', [
            'null' => true,
            'comment' => '提交内容'
        ])
        ->addColumn('attachments', 'json', [
            'null' => true,
            'comment' => '提交附件'
        ])
        ->addColumn('score', 'decimal', [
            'precision' => 5,
            'scale' => 2,
            'null' => true,
            'comment' => '得分'
        ])
        ->addColumn('max_score', 'decimal', [
            'precision' => 5,
            'scale' => 2,
            'default' => 100.00,
            'null' => false,
            'comment' => '满分'
        ])
        ->addColumn('feedback', 'text', [
            'null' => true,
            'comment' => '批改反馈'
        ])
        ->addColumn('grade_details', 'json', [
            'null' => true,
            'comment' => '批改详情(分题批改)'
        ])
        ->addColumn('grader_id', 'integer', [
            'null' => true,
            'comment' => '批改老师ID'
        ])
        ->addColumn('status', 'enum', [
            'values' => ['draft', 'submitted', 'graded', 'returned'],
            'default' => 'draft',
            'null' => false,
            'comment' => '提交状态'
        ])
        ->addColumn('grade_status', 'enum', [
            'values' => ['pending', 'grading', 'completed'],
            'default' => 'pending',
            'null' => false,
            'comment' => '评分状态'
        ])
        ->addColumn('submit_time', 'integer', [
            'default' => 0,
            'comment' => '提交时间'
        ])
        ->addColumn('grade_time', 'integer', [
            'default' => 0,
            'comment' => '批改时间'
        ])
        ->addColumn('is_late', 'boolean', [
            'default' => false,
            'comment' => '是否迟交'
        ])
        ->addColumn('attempt_count', 'integer', [
            'default' => 1,
            'comment' => '提交次数'
        ])
        ->addColumn('duration', 'integer', [
            'default' => 0,
            'comment' => '完成时长(秒)'
        ])
        ->addColumn('submit_ip', 'string', [
            'limit' => 45,
            'default' => '',
            'comment' => 'IP地址'
        ])
        ->addColumn('user_agent', 'text', [
            'null' => true,
            'comment' => '用户代理'
        ])
        ->addColumn('create_time', 'integer', [
            'null' => false,
            'comment' => '创建时间'
        ])
        ->addColumn('update_time', 'integer', [
            'null' => false,
            'comment' => '更新时间'
        ])
        ->addColumn('delete_time', 'integer', [
            'default' => 0,
            'comment' => '删除时间'
        ])
        ->addIndex(['assignment_id', 'user_id'], [
            'unique' => true,
            'name' => 'uk_assignment_user'
        ])
        ->addIndex(['assignment_id'], ['name' => 'idx_assignment_id'])
        ->addIndex(['user_id'], ['name' => 'idx_user_id'])
        ->addIndex(['grader_id'], ['name' => 'idx_grader_id'])
        ->addIndex(['status'], ['name' => 'idx_status'])
        ->addIndex(['grade_status'], ['name' => 'idx_grade_status'])
        ->addIndex(['submit_time'], ['name' => 'idx_submit_time'])
        ->addIndex(['delete_time'], ['name' => 'idx_delete_time'])
        ->create();
    }
}

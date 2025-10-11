<?php
/**
 * 更新管理员角色权限 - 添加知识图谱和作业系统权限
 * 
 * 执行命令: php vendor/bin/phinx migrate
 */

use Phinx\Migration\AbstractMigration;

class UpdateAdminRolePermissions extends AbstractMigration
{
    public function up()
    {
        // 获取管理员角色（ID=1）当前的权限
        $role = $this->fetchRow('SELECT routes FROM kg_role WHERE id = 1');
        
        if (!$role) {
            echo "管理员角色不存在，跳过\n";
            return;
        }
        
        // 解析现有权限
        $routes = json_decode($role['routes'], true);
        if (!is_array($routes)) {
            $routes = [];
        }
        
        // 知识图谱相关权限
        $knowledgeGraphRoutes = [
            'admin.knowledge_graph.list',
            'admin.knowledge_graph.editor',
            'admin.knowledge_graph.nodes',
            'admin.knowledge_graph.node_create',
            'admin.knowledge_graph.node_edit',
            'admin.knowledge_graph.create_node',
            'admin.knowledge_graph.update_node',
            'admin.knowledge_graph.delete_node',
            'admin.knowledge_graph.create_relation',
            'admin.knowledge_graph.update_relation',
            'admin.knowledge_graph.delete_relation',
            'admin.knowledge_graph.data',
            'admin.knowledge_graph.save',
            'admin.knowledge_graph.analysis',
            'admin.knowledge_graph.export',
            'admin.knowledge_graph.templates',
            'admin.knowledge_graph.template_detail',
            'admin.knowledge_graph.apply_template',
            'admin.knowledge_graph.template_create',
            'admin.knowledge_graph.template_update',
            'admin.knowledge_graph.template_delete',
        ];
        
        // 作业系统相关权限
        $assignmentRoutes = [
            'admin.assignment.list',
            'admin.assignment.create',
            'admin.assignment.search',
            'admin.assignment.stats',
            'admin.assignment.edit',
            'admin.assignment.show',
            'admin.assignment.store',
            'admin.assignment.update',
            'admin.assignment.delete',
            'admin.assignment.restore',
            'admin.assignment.publish',
            'admin.assignment.close',
            'admin.assignment_submission.list',
            'admin.assignment_submission.detail',
            'admin.assignment_submission.review',
            'admin.assignment_submission.update_score',
            'admin.assignment_submission.batch_review',
        ];
        
        // 资源管理增强权限
        $resourceRoutes = [
            'admin.resource.upload_enhanced',
            'admin.resource.recent',
            'admin.resource.batch_upload',
            'admin.resource.preview',
        ];
        
        // 合并所有新权限
        $newRoutes = array_merge(
            $knowledgeGraphRoutes,
            $assignmentRoutes,
            $resourceRoutes
        );
        
        // 添加到现有权限中（去重）
        $routes = array_unique(array_merge($routes, $newRoutes));
        
        // 更新数据库
        $routesJson = json_encode($routes, JSON_UNESCAPED_UNICODE);
        
        $this->execute(
            sprintf(
                "UPDATE kg_role SET routes = '%s', update_time = %d WHERE id = 1",
                addslashes($routesJson),
                time()
            )
        );
        
        echo "✓ 已为管理员角色添加 " . count($newRoutes) . " 个新权限\n";
        echo "✓ 管理员角色现在共有 " . count($routes) . " 个权限\n";
    }
    
    public function down()
    {
        // 如果需要回滚，可以在这里移除添加的权限
        echo "回滚操作：需要手动在后台管理界面调整权限\n";
    }
}


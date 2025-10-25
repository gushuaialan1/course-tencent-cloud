<?php
/**
 * 添加数据看板标题更新权限
 * 
 * 执行命令: php vendor/bin/phinx migrate
 */

use Phinx\Migration\AbstractMigration;

class AddDataBoardTitlePermission extends AbstractMigration
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
        
        // 数据看板标题更新权限
        $newRoutes = [
            'admin.data_board.update_title',
        ];
        
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
        
        echo "✓ 已为管理员角色添加数据看板标题更新权限\n";
    }
    
    public function down()
    {
        // 回滚移除权限
        $role = $this->fetchRow('SELECT routes FROM kg_role WHERE id = 1');
        
        if (!$role) {
            return;
        }
        
        $routes = json_decode($role['routes'], true);
        if (!is_array($routes)) {
            return;
        }
        
        $routes = array_diff($routes, ['admin.data_board.update_title']);
        
        $routesJson = json_encode(array_values($routes), JSON_UNESCAPED_UNICODE);
        
        $this->execute(
            sprintf(
                "UPDATE kg_role SET routes = '%s', update_time = %d WHERE id = 1",
                addslashes($routesJson),
                time()
            )
        );
        
        echo "✓ 已移除数据看板标题更新权限\n";
    }
}


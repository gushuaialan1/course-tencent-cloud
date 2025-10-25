<?php
/**
 * 添加数据看板权限到管理员角色
 * 
 * 执行命令: php vendor/bin/phinx migrate
 */

use Phinx\Migration\AbstractMigration;

class AddDataBoardPermissions extends AbstractMigration
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
        
        // 数据看板相关权限
        $dataBoardRoutes = [
            'admin.data_board.show',
            'admin.data_board.list',
            'admin.data_board.edit',
            'admin.data_board.update',
            'admin.data_board.refresh',
            'admin.data_board.refresh_single',
        ];
        
        // 添加到现有权限中（去重）
        $routes = array_unique(array_merge($routes, $dataBoardRoutes));
        
        // 更新数据库
        $routesJson = json_encode($routes, JSON_UNESCAPED_UNICODE);
        
        $this->execute(
            sprintf(
                "UPDATE kg_role SET routes = '%s', update_time = %d WHERE id = 1",
                addslashes($routesJson),
                time()
            )
        );
        
        echo "✓ 已为管理员角色添加数据看板权限\n";
        echo "✓ 新增权限数量: " . count($dataBoardRoutes) . "\n";
        echo "✓ 管理员角色现在共有 " . count($routes) . " 个权限\n";
    }
    
    public function down()
    {
        // 如果需要回滚，移除添加的权限
        $role = $this->fetchRow('SELECT routes FROM kg_role WHERE id = 1');
        
        if (!$role) {
            return;
        }
        
        $routes = json_decode($role['routes'], true);
        if (!is_array($routes)) {
            return;
        }
        
        // 移除数据看板权限
        $dataBoardRoutes = [
            'admin.data_board.show',
            'admin.data_board.list',
            'admin.data_board.edit',
            'admin.data_board.update',
            'admin.data_board.refresh',
            'admin.data_board.refresh_single',
        ];
        
        $routes = array_diff($routes, $dataBoardRoutes);
        
        $routesJson = json_encode(array_values($routes), JSON_UNESCAPED_UNICODE);
        
        $this->execute(
            sprintf(
                "UPDATE kg_role SET routes = '%s', update_time = %d WHERE id = 1",
                addslashes($routesJson),
                time()
            )
        );
        
        echo "✓ 已移除数据看板权限\n";
    }
}


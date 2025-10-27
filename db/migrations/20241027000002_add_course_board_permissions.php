<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

use Phinx\Migration\AbstractMigration;

final class AddCourseBoardPermissions extends AbstractMigration
{
    /**
     * 添加课程数据看板权限到管理员角色
     */
    public function up()
    {
        $newRoutes = [
            'admin.data_board.course',
            'admin.data_board.show_course',
            'admin.data_board.search_course',
            'admin.data_board.set_course',
            'admin.data_board.refresh_course',
            'admin.data_board.refresh_course_single',
            'admin.data_board.update_course_stat',
            'admin.data_board.update_course_intro',
        ];

        // 查询管理员角色
        $sql = "SELECT id, routes FROM kg_role WHERE id = 1";
        $role = $this->fetchRow($sql);

        if ($role) {
            // 解析现有的routes
            $routes = json_decode($role['routes'], true);
            if (!is_array($routes)) {
                $routes = [];
            }

            // 添加新路由
            foreach ($newRoutes as $route) {
                if (!in_array($route, $routes)) {
                    $routes[] = $route;
                }
            }

            // 更新角色权限
            $this->execute(sprintf(
                "UPDATE kg_role SET routes = '%s' WHERE id = 1",
                json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
        }
    }

    /**
     * 回滚操作
     */
    public function down()
    {
        $routesToRemove = [
            'admin.data_board.course',
            'admin.data_board.show_course',
            'admin.data_board.search_course',
            'admin.data_board.set_course',
            'admin.data_board.refresh_course',
            'admin.data_board.refresh_course_single',
            'admin.data_board.update_course_stat',
            'admin.data_board.update_course_intro',
        ];

        // 查询管理员角色
        $sql = "SELECT id, routes FROM kg_role WHERE id = 1";
        $role = $this->fetchRow($sql);

        if ($role) {
            // 解析现有的routes
            $routes = json_decode($role['routes'], true);
            if (is_array($routes)) {
                // 移除指定路由
                $routes = array_diff($routes, $routesToRemove);
                $routes = array_values($routes); // 重新索引

                // 更新角色权限
                $this->execute(sprintf(
                    "UPDATE kg_role SET routes = '%s' WHERE id = 1",
                    json_encode($routes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ));
            }
        }
    }
}


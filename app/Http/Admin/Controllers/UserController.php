<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Controllers;

use App\Http\Admin\Services\User as UserService;
use App\Models\Role as RoleModel;

/**
 * @RoutePrefix("/admin/user")
 */
class UserController extends Controller
{

    /**
     * @Get("/search", name="admin.user.search")
     */
    public function searchAction()
    {
        $userService = new UserService();

        $eduRoleTypes = $userService->getEduRoleTypes();
        $adminRoles = $userService->getAdminRoles();

        $this->view->setVar('edu_role_types', $eduRoleTypes);
        $this->view->setVar('admin_roles', $adminRoles);
    }

    /**
     * @Get("/list", name="admin.user.list")
     */
    public function listAction()
    {
        $userService = new UserService();

        $pager = $userService->getUsers();

        $this->view->setVar('pager', $pager);
    }

    /**
     * @Get("/add", name="admin.user.add")
     */
    public function addAction()
    {
        $userService = new UserService();

        $adminRoles = $userService->getAdminRoles();

        $this->view->setVar('admin_roles', $adminRoles);
    }

    /**
     * @Post("/create", name="admin.user.create")
     */
    public function createAction()
    {
        $adminRole = $this->request->getPost('admin_role', 'int', 0);

        if ($adminRole == RoleModel::ROLE_ROOT) {
            return $this->response->redirect(['action' => 'list']);
        }

        $userService = new UserService();

        $userService->createUser();

        $location = $this->url->get(['for' => 'admin.user.list']);

        $content = [
            'location' => $location,
            'msg' => '新增用户成功',
        ];

        return $this->jsonSuccess($content);
    }

    /**
     * @Get("/{id:[0-9]+}/edit", name="admin.user.edit")
     */
    public function editAction($id)
    {
        $userService = new UserService();

        $user = $userService->getUser($id);
        $account = $userService->getAccount($id);
        $adminRoles = $userService->getAdminRoles();

        $defaultAvatar = kg_cos_user_avatar_url(null);

        if ($user->admin_role == RoleModel::ROLE_ROOT) {
            return $this->response->redirect(['for' => 'admin.user.list']);
        }

        $this->view->setVar('user', $user);
        $this->view->setVar('account', $account);
        $this->view->setVar('admin_roles', $adminRoles);
        $this->view->setVar('default_avatar', $defaultAvatar);
    }

    /**
     * @Get("/{id:[0-9]+}/online", name="admin.user.online")
     */
    public function onlineAction($id)
    {
        $userService = new UserService();

        $pager = $userService->getOnlineLogs($id);

        $this->view->setVar('pager', $pager);
    }

    /**
     * @Post("/{id:[0-9]+}/update", name="admin.user.update")
     */
    public function updateAction($id)
    {
        $adminRole = $this->request->getPost('admin_role', 'int', 0);

        if ($adminRole == RoleModel::ROLE_ROOT) {
            return $this->response->redirect(['action' => 'list']);
        }

        $type = $this->request->getPost('type', 'string', 'user');

        $userService = new UserService();

        if ($type == 'user') {
            $userService->updateUser($id);
        } else {
            $userService->updateAccount($id);
        }

        $content = ['msg' => '更新用户成功'];

        return $this->jsonSuccess($content);
    }

    /**
     * @Post("/{id:[0-9]+}/delete", name="admin.user.delete")
     */
    public function deleteAction($id)
    {
        $userService = new UserService();

        $userService->deleteUser($id);

        $location = $this->url->get(['for' => 'admin.user.list']);

        $content = [
            'location' => $location,
            'msg' => '删除用户成功',
        ];

        return $this->jsonSuccess($content);
    }

    /**
     * @Post("/{id:[0-9]+}/restore", name="admin.user.restore")
     */
    public function restoreAction($id)
    {
        $userService = new UserService();

        $userService->restoreUser($id);

        $location = $this->url->get(['for' => 'admin.user.list']);

        $content = [
            'location' => $location,
            'msg' => '还原用户成功',
        ];

        return $this->jsonSuccess($content);
    }

    /**
     * 下载用户导入模板
     * 
     * @Get("/download_template", name="admin.user.download_template")
     */
    public function downloadTemplateAction()
    {
        // 设置CSV文件头
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="user_import_template_' . date('YmdHis') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // 输出UTF-8 BOM（确保Excel正确识别中文）
        echo "\xEF\xBB\xBF";

        // 打开PHP输出流
        $output = fopen('php://output', 'w');

        // 写入CSV表头
        fputcsv($output, ['姓名', '手机号', '密码', '邮箱', '性别', '角色']);

        // 写入示例数据（帮助用户理解格式）
        fputcsv($output, ['张三', '13800138001', '123456', 'zhangsan@example.com', '男', '学员']);
        fputcsv($output, ['李四', '13800138002', '123456', 'lisi@example.com', '女', '讲师']);
        fputcsv($output, ['王五', '13800138003', '', 'wangwu@example.com', '保密', '学员']);

        fclose($output);
        exit;
    }

    /**
     * 批量导入用户
     * 
     * @Post("/batch_import", name="admin.user.batch_import")
     */
    public function batchImportAction()
    {
        try {
            // 检查是否有上传文件
            if (!$this->request->hasFiles()) {
                return $this->jsonError(['msg' => '请选择要上传的CSV文件']);
            }

            $file = $this->request->getUploadedFiles()[0];

            // 验证文件类型
            $ext = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                return $this->jsonError(['msg' => '只支持CSV格式文件']);
            }

            // 读取CSV文件
            $filePath = $file->getTempName();
            $handle = fopen($filePath, 'r');
            
            if (!$handle) {
                return $this->jsonError(['msg' => 'CSV文件读取失败']);
            }

            // 跳过BOM（如果存在）
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // 读取表头
            $header = fgetcsv($handle);
            if (!$header || count($header) < 3) {
                fclose($handle);
                return $this->jsonError(['msg' => 'CSV格式错误：缺少必要的列']);
            }

            $successCount = 0;
            $failCount = 0;
            $errors = [];
            $lineNumber = 1;

            // 逐行读取数据
            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;
                
                // 跳过空行
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    // 解析数据（支持不同列数）
                    $name = trim($row[0] ?? '');
                    $phone = trim($row[1] ?? '');
                    $password = trim($row[2] ?? '');
                    $email = trim($row[3] ?? '');
                    $gender = trim($row[4] ?? '保密');
                    $role = trim($row[5] ?? '学员');

                    // 验证必填项
                    if (empty($name)) {
                        throw new \Exception("第{$lineNumber}行：姓名不能为空");
                    }
                    if (empty($phone)) {
                        throw new \Exception("第{$lineNumber}行：手机号不能为空");
                    }

                    // 验证手机号格式
                    if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
                        throw new \Exception("第{$lineNumber}行：手机号格式不正确");
                    }

                    // 检查手机号是否已存在
                    $existingAccount = \App\Models\Account::findFirst([
                        'conditions' => 'phone = :phone: AND deleted = 0',
                        'bind' => ['phone' => $phone]
                    ]);
                    if ($existingAccount) {
                        throw new \Exception("第{$lineNumber}行：手机号 {$phone} 已存在");
                    }

                    // 验证邮箱格式（如果提供）
                    if (!empty($email)) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            throw new \Exception("第{$lineNumber}行：邮箱格式不正确");
                        }
                        // 检查邮箱是否已存在
                        $existingEmail = \App\Models\Account::findFirst([
                            'conditions' => 'email = :email: AND deleted = 0',
                            'bind' => ['email' => $email]
                        ]);
                        if ($existingEmail) {
                            throw new \Exception("第{$lineNumber}行：邮箱 {$email} 已存在");
                        }
                    }

                    // 使用默认密码（如果未提供）
                    if (empty($password)) {
                        $password = '123456';
                    }

                    // 验证密码长度
                    if (strlen($password) < 6 || strlen($password) > 20) {
                        throw new \Exception("第{$lineNumber}行：密码长度必须在6-20个字符之间");
                    }

                    // 转换性别
                    $genderValue = 3; // 默认保密
                    if ($gender === '男') {
                        $genderValue = 1;
                    } elseif ($gender === '女') {
                        $genderValue = 2;
                    }

                    // 转换角色
                    $eduRole = 1; // 默认学员
                    if ($role === '讲师') {
                        $eduRole = 2;
                    }

                    // 创建账号
                    $account = new \App\Models\Account();
                    $account->phone = $phone;
                    $account->email = $email;
                    $account->salt = $this->random->base64Safe();
                    $account->password = $this->security->hash($password . $account->salt);

                    if (!$account->create()) {
                        throw new \Exception("第{$lineNumber}行：创建账号失败");
                    }

                    // 更新用户信息（Account的afterCreate已自动创建User）
                    $user = \App\Models\User::findFirst($account->id);
                    if ($user) {
                        $user->name = $name;
                        $user->gender = $genderValue;
                        $user->edu_role = $eduRole;
                        $user->save();
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = $e->getMessage();
                }
            }

            fclose($handle);

            // 返回结果
            $result = [
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'errors' => $errors
            ];

            if ($failCount > 0) {
                $msg = "成功导入 {$successCount} 个用户，失败 {$failCount} 个";
                if (count($errors) > 0) {
                    $msg .= "\n错误详情：\n" . implode("\n", array_slice($errors, 0, 10));
                    if (count($errors) > 10) {
                        $msg .= "\n... 更多错误请检查数据";
                    }
                }
                return $this->jsonError(['msg' => $msg, 'data' => $result]);
            }

            return $this->jsonSuccess([
                'data' => $result,
                'msg' => "成功导入 {$successCount} 个用户"
            ]);

        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '导入失败: ' . $e->getMessage()]);
        }
    }

}

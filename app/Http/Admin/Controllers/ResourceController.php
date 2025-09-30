<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Admin\Controllers;

use App\Http\Admin\Services\Resource as ResourceService;

/**
 * @RoutePrefix("/admin/resource")
 */
class ResourceController extends Controller
{

    /**
     * @Get("/upload/enhanced", name="admin.resource.upload_enhanced")
     */
    public function uploadEnhancedAction()
    {
        $courses = []; // 获取课程列表，稍后实现
        
        $this->view->setVar('courses', $courses);
        $this->view->setVar('course_id', $this->request->get('course_id', 'int', 0));
        
        return $this->view->pick('resource/upload_enhanced');
    }

    /**
     * @Get("/recent", name="admin.resource.recent")
     */
    public function recentAction()
    {
        $limit = $this->request->get('limit', 'int', 10);
        
        // 获取最近上传的资源
        $recentUploads = []; // 稍后从数据库获取
        
        return $this->jsonSuccess($recentUploads);
    }

    /**
     * @Post("/create", name="admin.resource.create")
     */
    public function createAction()
    {
        $resourceService = new ResourceService();

        $resourceService->createResource();

        return $this->jsonSuccess(['msg' => '上传资源成功']);
    }

    /**
     * @Post("/{id:[0-9]+}/update", name="admin.resource.update")
     */
    public function updateAction($id)
    {
        $resourceService = new ResourceService();

        $resourceService->updateResource($id);

        return $this->jsonSuccess(['msg' => '更新资源成功']);
    }

    /**
     * @Post("/{id:[0-9]+}/delete", name="admin.resource.delete")
     */
    public function deleteAction($id)
    {
        $resourceService = new ResourceService();

        $resourceService->deleteResource($id);

        return $this->jsonSuccess(['msg' => '删除资源成功']);
    }

}

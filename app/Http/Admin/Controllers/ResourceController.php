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
        try {
            // 获取课程列表 - 复用现有服务
            $courseRepo = new \App\Repos\Course();
            $courses = $courseRepo->findAll(['published' => 1, 'deleted' => 0]);
            
            // 获取上传类型选项
            $uploadTypes = [
                'cover' => '封面图',
                'content' => '内容图',
                'resource' => '课件资源',
                'document' => '文档资料',
                'video' => '视频资源',
                'audio' => '音频资源',
            ];
            
            $this->view->setVars([
                'courses' => $courses,
                'course_id' => $this->request->get('course_id', 'int', 0),
                'upload_types' => $uploadTypes,
                'csrfToken' => $this->di->get('csrfToken')
            ]);
            
            return $this->view->pick('resource/upload_enhanced');
            
        } catch (\Exception $e) {
            $this->flashSession->error('获取数据失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/index/index');
        }
    }

    /**
     * @Get("/recent", name="admin.resource.recent")
     */
    public function recentAction()
    {
        try {
            $limit = $this->request->get('limit', 'int', 10);
            
            // 获取最近上传的资源
            $uploads = \App\Models\Upload::find([
                'conditions' => 'deleted = 0',
                'order' => 'create_time DESC',
                'limit' => $limit
            ]);
            
            $recentUploads = [];
            foreach ($uploads as $upload) {
                $recentUploads[] = [
                    'id' => $upload->id,
                    'name' => $upload->name,
                    'size' => $upload->size,
                    'mime' => $upload->mime,
                    'path' => $upload->path,
                    'create_time' => date('Y-m-d H:i:s', $upload->create_time)
                ];
            }
            
            return $this->jsonSuccess($recentUploads);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '获取数据失败: ' . $e->getMessage()]);
        }
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

<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Home\Controllers;

use App\Services\MyStorage as StorageService;
use App\Validators\Validator as AppValidator;

/**
 * @RoutePrefix("/upload")
 */
class UploadController extends Controller
{

    public function initialize()
    {
        $authUser = $this->getAuthUser();

        $validator = new AppValidator();

        $validator->checkAuthUser($authUser->id);
    }

    /**
     * @Post("/avatar/img", name="home.upload.avatar_img")
     */
    public function uploadAvatarImageAction()
    {
        $service = new StorageService();

        $file = $service->uploadAvatarImage();

        if (!$file) {
            return $this->jsonError(['msg' => '上传文件失败']);
        }

        $data = [
            'id' => $file->id,
            'name' => $file->name,
            'url' => $service->getImageUrl($file->path),
        ];

        return $this->jsonSuccess(['data' => $data]);
    }

    /**
     * @Post("/content/img", name="home.upload.content_img")
     */
    public function uploadContentImageAction()
    {
        $service = new StorageService();

        $file = $service->uploadContentImage();

        if (!$file) {
            return $this->jsonError([
                'message' => '上传文件失败',
                'error' => 1,
            ]);
        }

        return $this->jsonSuccess([
            'url' => $service->getImageUrl($file->path),
            'error' => 0,
        ]);
    }

    /**
     * @Post("/file", name="home.upload.file")
     */
    public function uploadFileAction()
    {
        try {
            $service = new StorageService();

            $file = $service->uploadResourceFile();

            if (!$file) {
                return $this->jsonError(['msg' => '上传文件失败']);
            }

            // 获取文件访问URL
            $fileUrl = $service->getFileUrl($file->path);
            
            $data = [
                'id' => $file->id,
                'name' => $file->name,
                'file_name' => $file->name,
                'url' => $fileUrl,
                'file_url' => $fileUrl,
                'path' => $file->path,
                'size' => $file->size,
                'mime' => $file->mime,
            ];

            return $this->jsonSuccess([
                'data' => $data,
                'msg' => '上传成功'
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['msg' => '上传失败：' . $e->getMessage()]);
        }
    }

}
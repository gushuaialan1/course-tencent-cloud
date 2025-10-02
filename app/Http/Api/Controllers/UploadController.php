<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */


namespace App\Http\Api\Controllers;

use App\Services\MyStorage as StorageService;

/**
 * @RoutePrefix("/api/upload")
 */
class UploadController extends Controller
{

    /**
     * @Post("/avatar/img", name="api.upload.avatar_img")
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
     * @Post("/enhanced", name="api.upload.enhanced")
     */
    public function enhancedUploadAction()
    {
        try {
            $files = $this->request->getUploadedFiles();
            $uploadService = new StorageService();
            $results = [];
            
            foreach ($files as $file) {
                // 文件验证
                $validation = $this->validateUploadFile($file);
                if (!$validation['valid']) {
                    $results[] = [
                        'success' => false,
                        'filename' => $file->getName(),
                        'error' => $validation['message']
                    ];
                    continue;
                }
                
                // 创建上传记录
                $upload = $uploadService->uploadResourceFile();
                
                if ($upload) {
                    $results[] = [
                        'success' => true,
                        'file' => [
                            'id' => $upload->id,
                            'name' => $upload->name,
                            'path' => $upload->path,
                            'size' => $upload->size,
                            'mime' => $upload->mime,
                            'url' => $uploadService->getFileUrl($upload->path)
                        ],
                        'preview_url' => $this->generatePreviewUrl($upload)
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'filename' => $file->getName(),
                        'error' => '上传失败'
                    ];
                }
            }
            
            return $this->jsonSuccess([
                'uploads' => $results,
                'total' => count($results),
                'success_count' => count(array_filter($results, function($r) { return $r['success']; }))
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '上传失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 验证上传文件
     */
    private function validateUploadFile($file)
    {
        $maxSize = 100 * 1024 * 1024; // 100MB
        $allowedTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
            'image/jpeg',
            'image/png',
            'image/gif',
            'video/mp4',
            'audio/mpeg'
        ];
        
        if ($file->getSize() > $maxSize) {
            return ['valid' => false, 'message' => '文件大小超过限制(100MB)'];
        }
        
        if (!in_array($file->getRealType(), $allowedTypes)) {
            return ['valid' => false, 'message' => '不支持的文件类型: ' . $file->getRealType()];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 生成预览URL
     */
    private function generatePreviewUrl($upload)
    {
        $extension = pathinfo($upload->name, PATHINFO_EXTENSION);
        
        if ($extension === 'pdf') {
            return "/preview/pdf/{$upload->id}";
        } elseif (in_array($extension, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'])) {
            return "/preview/office/{$upload->id}";
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            return "/preview/image/{$upload->id}";
        } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
            return "/preview/video/{$upload->id}";
        }
        
        return null;
    }

}
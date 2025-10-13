<?php
/**
 * @copyright Copyright (c) 2021 深圳市酷瓜软件有限公司
 * @license https://opensource.org/licenses/GPL-2.0
 * @link https://www.koogua.com
 */

namespace App\Http\Api\Controllers;

/**
 * @RoutePrefix("/api/log")
 */
class LogController extends Controller
{
    /**
     * 接收前端日志并写入服务器error_log
     * 
     * @Post("/frontend", name="api.log.frontend")
     */
    public function frontendAction()
    {
        // 获取原始POST数据
        $rawData = $this->request->getRawBody();
        
        if (empty($rawData)) {
            error_log("[前端日志-ERROR] 接收到空数据");
            return $this->jsonSuccess(['status' => 'ok']);
        }
        
        $logData = json_decode($rawData, true);
        
        if (!$logData || !isset($logData['message'])) {
            error_log("[前端日志-ERROR] 日志数据格式无效: " . $rawData);
            return $this->jsonSuccess(['status' => 'ok']);
        }
        
        // 构建日志消息
        $level = strtoupper($logData['level'] ?? 'INFO');
        $message = $logData['message'] ?? '';
        $url = $logData['url'] ?? 'unknown';
        $timestamp = $logData['timestamp'] ?? date('c');
        
        // 格式化日志输出
        $logMessage = sprintf(
            "[前端日志-%s] %s | URL: %s | Time: %s",
            $level,
            $message,
            $url,
            $timestamp
        );
        
        // 如果有附加数据，也输出
        if (!empty($logData['data'])) {
            $logMessage .= " | Data: " . $logData['data'];
        }
        
        // 写入PHP错误日志
        error_log($logMessage);
        
        // 返回成功
        return $this->jsonSuccess(['status' => 'ok']);
    }
}


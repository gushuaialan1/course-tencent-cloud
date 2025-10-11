<?php
/**
 * AI服务类
 * 
 * 功能：封装DeepSeek和硅基流动API调用
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services;

use App\Models\KgAiConfig;

class AiService extends Service
{
    /**
     * 生成知识图谱
     * 
     * @param string $provider 服务提供商 (deepseek/siliconflow)
     * @param string $apiKey API密钥
     * @param string $model 模型名称
     * @param string $baseUrl API基础URL
     * @param string $prompt 提示词
     * @param array $options 额外选项
     * @return string AI响应内容
     * @throws \Exception
     */
    public function generateKnowledgeGraph($provider, $apiKey, $model, $baseUrl, $prompt, $options = [])
    {
        $timeout = $options['timeout'] ?? 30;
        $maxTokens = $options['max_tokens'] ?? 2000;
        $temperature = $options['temperature'] ?? 0.7;
        
        switch ($provider) {
            case KgAiConfig::PROVIDER_DEEPSEEK:
                return $this->callDeepSeek($apiKey, $model, $baseUrl, $prompt, $maxTokens, $temperature, $timeout);
                
            case KgAiConfig::PROVIDER_SILICONFLOW:
                return $this->callSiliconFlow($apiKey, $model, $baseUrl, $prompt, $maxTokens, $temperature, $timeout);
                
            default:
                throw new \Exception('不支持的AI服务提供商');
        }
    }
    
    /**
     * 调用DeepSeek API
     * 
     * @param string $apiKey
     * @param string $model
     * @param string $baseUrl
     * @param string $prompt
     * @param int $maxTokens
     * @param float $temperature
     * @param int $timeout
     * @return string
     * @throws \Exception
     */
    private function callDeepSeek($apiKey, $model, $baseUrl, $prompt, $maxTokens, $temperature, $timeout)
    {
        $url = rtrim($baseUrl, '/') . '/chat/completions';
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一位专业的教育内容分析专家，擅长提取知识点和构建知识图谱。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => false
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
        
        $response = $this->httpPost($url, $data, $headers, $timeout);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \Exception('DeepSeek API响应格式错误');
        }
        
        return $response['choices'][0]['message']['content'];
    }
    
    /**
     * 调用硅基流动 API
     * 
     * @param string $apiKey
     * @param string $model
     * @param string $baseUrl
     * @param string $prompt
     * @param int $maxTokens
     * @param float $temperature
     * @param int $timeout
     * @return string
     * @throws \Exception
     */
    private function callSiliconFlow($apiKey, $model, $baseUrl, $prompt, $maxTokens, $temperature, $timeout)
    {
        $url = rtrim($baseUrl, '/') . '/v1/chat/completions';
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一位专业的教育内容分析专家，擅长提取知识点和构建知识图谱。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'stream' => false
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
        
        $response = $this->httpPost($url, $data, $headers, $timeout);
        
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \Exception('硅基流动 API响应格式错误');
        }
        
        return $response['choices'][0]['message']['content'];
    }
    
    /**
     * 测试AI连接
     * 
     * @param string $provider
     * @param string $apiKey
     * @param string $model
     * @param string $baseUrl
     * @param int $timeout
     * @return array ['success' => bool, 'message' => string, 'response_time' => float]
     */
    public function testConnection($provider, $apiKey, $model, $baseUrl, $timeout = 10)
    {
        $startTime = microtime(true);
        
        try {
            $testPrompt = '请用一句话介绍什么是知识图谱。';
            $response = $this->generateKnowledgeGraph(
                $provider,
                $apiKey,
                $model,
                $baseUrl,
                $testPrompt,
                ['timeout' => $timeout, 'max_tokens' => 100, 'temperature' => 0.7]
            );
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'message' => '连接成功！响应时间：' . $responseTime . 'ms',
                'response_time' => $responseTime,
                'response_preview' => mb_substr($response, 0, 100, 'UTF-8')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '连接失败：' . $e->getMessage(),
                'response_time' => 0
            ];
        }
    }
    
    /**
     * HTTP POST请求
     * 
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param int $timeout
     * @return array
     * @throws \Exception
     */
    private function httpPost($url, $data, $headers, $timeout)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($response === false) {
            throw new \Exception('请求失败：' . $error);
        }
        
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? $errorData['message'] ?? '未知错误';
            throw new \Exception('API返回错误（HTTP ' . $httpCode . '）：' . $errorMessage);
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            throw new \Exception('响应解析失败：无效的JSON');
        }
        
        return $result;
    }
}


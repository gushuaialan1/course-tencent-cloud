<?php
/**
 * AI配置模型
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Models;

class KgAiConfig extends Model
{
    /**
     * AI服务提供商常量
     */
    const PROVIDER_DISABLED = 'disabled';
    const PROVIDER_DEEPSEEK = 'deepseek';
    const PROVIDER_SILICONFLOW = 'siliconflow';

    /**
     * 生成模式常量
     */
    const MODE_SIMPLE = 'simple';
    const MODE_AI = 'ai';

    /**
     * 配置键名常量
     */
    const KEY_PROVIDER = 'ai_provider';
    const KEY_API_KEY = 'ai_api_key';
    const KEY_MODEL = 'ai_model';
    const KEY_BASE_URL = 'ai_base_url';
    const KEY_GENERATION_MODE = 'generation_mode';
    const KEY_TIMEOUT = 'ai_timeout';
    const KEY_MAX_TOKENS = 'ai_max_tokens';
    const KEY_TEMPERATURE = 'ai_temperature';

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 配置键名
     *
     * @var string
     */
    public $config_key = '';

    /**
     * 配置值
     *
     * @var string
     */
    public $config_value = '';

    /**
     * 配置说明
     *
     * @var string
     */
    public $description = '';

    /**
     * 是否加密存储
     *
     * @var bool
     */
    public $is_encrypted = false;

    /**
     * 更新者用户ID
     *
     * @var int
     */
    public $updated_by = 0;

    /**
     * 创建时间
     *
     * @var int
     */
    public $create_time = 0;

    /**
     * 更新时间
     *
     * @var int
     */
    public $update_time = 0;

    public function getSource(): string
    {
        return 'kg_ai_config';
    }

    public function initialize()
    {
        parent::initialize();
    }

    public function beforeCreate()
    {
        $this->create_time = time();
        $this->update_time = time();
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
    }

    /**
     * 获取所有AI服务提供商
     *
     * @return array
     */
    public static function getProviders(): array
    {
        return [
            self::PROVIDER_DISABLED => '关闭AI功能',
            self::PROVIDER_DEEPSEEK => 'DeepSeek',
            self::PROVIDER_SILICONFLOW => '硅基流动'
        ];
    }

    /**
     * 获取所有生成模式
     *
     * @return array
     */
    public static function getGenerationModes(): array
    {
        return [
            self::MODE_SIMPLE => '简单生成（从章节）',
            self::MODE_AI => 'AI智能生成'
        ];
    }

    /**
     * 获取DeepSeek支持的模型
     *
     * @return array
     */
    public static function getDeepSeekModels(): array
    {
        return [
            'deepseek-chat' => 'DeepSeek Chat (推荐)',
            'deepseek-coder' => 'DeepSeek Coder'
        ];
    }

    /**
     * 获取硅基流动支持的模型
     *
     * @return array
     */
    public static function getSiliconFlowModels(): array
    {
        return [
            'Qwen/Qwen2-72B-Instruct' => 'Qwen2 72B (推荐)',
            'Qwen/Qwen2-7B-Instruct' => 'Qwen2 7B',
            'deepseek-ai/DeepSeek-V2-Chat' => 'DeepSeek V2',
            'THUDM/glm-4-9b-chat' => 'GLM-4 9B'
        ];
    }

    /**
     * 获取提供商的默认模型
     *
     * @param string $provider
     * @return string
     */
    public static function getDefaultModel(string $provider): string
    {
        $defaults = [
            self::PROVIDER_DEEPSEEK => 'deepseek-chat',
            self::PROVIDER_SILICONFLOW => 'Qwen/Qwen2-72B-Instruct'
        ];

        return $defaults[$provider] ?? '';
    }

    /**
     * 获取提供商的默认API地址
     *
     * @param string $provider
     * @return string
     */
    public static function getDefaultBaseUrl(string $provider): string
    {
        $urls = [
            self::PROVIDER_DEEPSEEK => 'https://api.deepseek.com',
            self::PROVIDER_SILICONFLOW => 'https://api.siliconflow.cn'
        ];

        return $urls[$provider] ?? '';
    }

    /**
     * 获取提供商信息
     *
     * @param string $provider
     * @return array
     */
    public static function getProviderInfo(string $provider): array
    {
        $info = [
            self::PROVIDER_DEEPSEEK => [
                'name' => 'DeepSeek',
                'description' => '国内AI服务商，性价比高',
                'website' => 'https://platform.deepseek.com',
                'doc_url' => 'https://platform.deepseek.com/docs',
                'pricing' => '¥0.001/千tokens（输入），¥0.002/千tokens（输出）',
                'features' => ['价格低廉', '响应快速', '中文友好']
            ],
            self::PROVIDER_SILICONFLOW => [
                'name' => '硅基流动',
                'description' => '多模型支持，灵活选择',
                'website' => 'https://siliconflow.cn',
                'doc_url' => 'https://docs.siliconflow.cn',
                'pricing' => '根据选择的模型不同而不同',
                'features' => ['多模型选择', '高性能', '稳定可靠']
            ]
        ];

        return $info[$provider] ?? [];
    }
}


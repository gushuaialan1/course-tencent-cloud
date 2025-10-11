<?php
/**
 * AI配置仓储类
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Repos;

use App\Models\KgAiConfig as KgAiConfigModel;

class KgAiConfig extends Repository
{
    /**
     * 加密密钥（应从环境变量或配置文件读取）
     */
    private const ENCRYPT_KEY = 'kg_ai_config_encrypt_key_2024';

    /**
     * 根据配置键获取配置
     *
     * @param string $key
     * @return KgAiConfigModel|null
     */
    public function findByKey(string $key): ?KgAiConfigModel
    {
        return KgAiConfigModel::findFirst([
            'conditions' => 'config_key = :key:',
            'bind' => ['key' => $key]
        ]);
    }

    /**
     * 获取配置值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $key, $default = null)
    {
        $config = $this->findByKey($key);
        
        if (!$config) {
            return $default;
        }

        $value = $config->config_value;

        // 如果是加密存储，解密
        if ($config->is_encrypted && !empty($value)) {
            $value = $this->decrypt($value);
        }

        return $value;
    }

    /**
     * 设置配置值
     *
     * @param string $key
     * @param mixed $value
     * @param int $userId
     * @return bool
     */
    public function setValue(string $key, $value, int $userId = 0): bool
    {
        $config = $this->findByKey($key);

        if (!$config) {
            // 创建新配置
            $config = new KgAiConfigModel();
            $config->config_key = $key;
            $config->is_encrypted = in_array($key, [KgAiConfigModel::KEY_API_KEY]);
        }

        // 如果需要加密
        if ($config->is_encrypted && !empty($value)) {
            $value = $this->encrypt($value);
        }

        $config->config_value = $value;
        $config->updated_by = $userId;

        return $config->save();
    }

    /**
     * 获取所有配置（作为关联数组）
     *
     * @param bool $decrypt 是否解密敏感信息
     * @return array
     */
    public function getAllConfigs(bool $decrypt = false): array
    {
        $configs = KgAiConfigModel::find();
        $result = [];

        foreach ($configs as $config) {
            $value = $config->config_value;

            // 如果需要解密且是加密字段
            if ($decrypt && $config->is_encrypted && !empty($value)) {
                $value = $this->decrypt($value);
            } else if (!$decrypt && $config->is_encrypted && !empty($value)) {
                // 不解密时，敏感信息显示为星号
                $value = str_repeat('*', min(strlen($value), 32));
            }

            $result[$config->config_key] = $value;
        }

        return $result;
    }

    /**
     * 批量更新配置
     *
     * @param array $configs 配置键值对
     * @param int $userId
     * @return bool
     */
    public function batchUpdate(array $configs, int $userId = 0): bool
    {
        $db = $this->getDI()->get('db');
        
        try {
            $db->begin();

            foreach ($configs as $key => $value) {
                if (!$this->setValue($key, $value, $userId)) {
                    $db->rollback();
                    return false;
                }
            }

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * 获取AI配置（便捷方法）
     *
     * @return array
     */
    public function getAiConfig(): array
    {
        return [
            'provider' => $this->getValue(KgAiConfigModel::KEY_PROVIDER, KgAiConfigModel::PROVIDER_DISABLED),
            'api_key' => $this->getValue(KgAiConfigModel::KEY_API_KEY, ''),
            'model' => $this->getValue(KgAiConfigModel::KEY_MODEL, ''),
            'base_url' => $this->getValue(KgAiConfigModel::KEY_BASE_URL, ''),
            'generation_mode' => $this->getValue(KgAiConfigModel::KEY_GENERATION_MODE, KgAiConfigModel::MODE_SIMPLE),
            'timeout' => (int)$this->getValue(KgAiConfigModel::KEY_TIMEOUT, 30),
            'max_tokens' => (int)$this->getValue(KgAiConfigModel::KEY_MAX_TOKENS, 2000),
            'temperature' => (float)$this->getValue(KgAiConfigModel::KEY_TEMPERATURE, 0.7)
        ];
    }

    /**
     * 检查AI是否已配置
     *
     * @return bool
     */
    public function isAiConfigured(): bool
    {
        $provider = $this->getValue(KgAiConfigModel::KEY_PROVIDER);
        $apiKey = $this->getValue(KgAiConfigModel::KEY_API_KEY);

        return $provider !== KgAiConfigModel::PROVIDER_DISABLED && !empty($apiKey);
    }

    /**
     * 加密字符串
     *
     * @param string $data
     * @return string
     */
    private function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($data, $cipher, self::ENCRYPT_KEY, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext, self::ENCRYPT_KEY, true);
        
        return base64_encode($iv . $hmac . $ciphertext);
    }

    /**
     * 解密字符串
     *
     * @param string $data
     * @return string
     */
    private function decrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        try {
            $c = base64_decode($data);
            $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
            $iv = substr($c, 0, $ivlen);
            $hmac = substr($c, $ivlen, 32);
            $ciphertext = substr($c, $ivlen + 32);
            
            $plaintext = openssl_decrypt($ciphertext, $cipher, self::ENCRYPT_KEY, OPENSSL_RAW_DATA, $iv);
            $calcmac = hash_hmac('sha256', $ciphertext, self::ENCRYPT_KEY, true);
            
            if (hash_equals($hmac, $calcmac)) {
                return $plaintext;
            }
        } catch (\Exception $e) {
            // 解密失败，返回空字符串
        }

        return '';
    }

    /**
     * 重置为默认配置
     *
     * @param int $userId
     * @return bool
     */
    public function resetToDefaults(int $userId = 0): bool
    {
        $defaults = [
            KgAiConfigModel::KEY_PROVIDER => KgAiConfigModel::PROVIDER_DISABLED,
            KgAiConfigModel::KEY_API_KEY => '',
            KgAiConfigModel::KEY_MODEL => '',
            KgAiConfigModel::KEY_BASE_URL => '',
            KgAiConfigModel::KEY_GENERATION_MODE => KgAiConfigModel::MODE_SIMPLE,
            KgAiConfigModel::KEY_TIMEOUT => '30',
            KgAiConfigModel::KEY_MAX_TOKENS => '2000',
            KgAiConfigModel::KEY_TEMPERATURE => '0.7'
        ];

        return $this->batchUpdate($defaults, $userId);
    }
}


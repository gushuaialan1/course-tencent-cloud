<?php
/**
 * 知识图谱AI配置表数据库迁移
 * 用于存储AI服务配置信息
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

use Phinx\Migration\AbstractMigration;

class CreateKgAiConfigTable extends AbstractMigration
{
    /**
     * 执行迁移
     */
    public function up()
    {
        $this->createAiConfigTable();
        $this->insertDefaultConfig();
    }

    /**
     * 回滚迁移
     */
    public function down()
    {
        $this->table('kg_ai_config')->drop()->save();
    }

    /**
     * 创建AI配置表
     */
    private function createAiConfigTable()
    {
        $table = $this->table('kg_ai_config', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '知识图谱AI配置表'
        ]);

        $table->addColumn('config_key', 'string', [
            'limit' => 50,
            'null' => false,
            'comment' => '配置键名'
        ])
        ->addColumn('config_value', 'text', [
            'null' => true,
            'comment' => '配置值（敏感信息加密存储）'
        ])
        ->addColumn('description', 'string', [
            'limit' => 255,
            'null' => true,
            'comment' => '配置说明'
        ])
        ->addColumn('is_encrypted', 'boolean', [
            'null' => false,
            'default' => false,
            'comment' => '是否加密存储'
        ])
        ->addColumn('updated_by', 'integer', [
            'null' => true,
            'comment' => '更新者用户ID'
        ])
        ->addColumn('create_time', 'integer', [
            'null' => false,
            'comment' => '创建时间戳'
        ])
        ->addColumn('update_time', 'integer', [
            'null' => false,
            'default' => 0,
            'comment' => '更新时间戳'
        ])
        ->addIndex(['config_key'], [
            'unique' => true,
            'name' => 'uk_config_key'
        ])
        ->create();
    }

    /**
     * 插入默认配置
     */
    private function insertDefaultConfig()
    {
        $now = time();
        
        $data = [
            [
                'config_key' => 'ai_provider',
                'config_value' => 'disabled',
                'description' => 'AI服务提供商：deepseek/siliconflow/disabled',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'ai_api_key',
                'config_value' => '',
                'description' => 'API密钥（加密存储）',
                'is_encrypted' => true,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'ai_model',
                'config_value' => '',
                'description' => '使用的模型名称',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'ai_base_url',
                'config_value' => '',
                'description' => 'API基础URL（可选）',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'generation_mode',
                'config_value' => 'simple',
                'description' => '生成方式：simple/ai',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'ai_timeout',
                'config_value' => '30',
                'description' => 'API请求超时时间（秒）',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'ai_max_tokens',
                'config_value' => '2000',
                'description' => '最大生成令牌数',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ],
            [
                'config_key' => 'ai_temperature',
                'config_value' => '0.7',
                'description' => '生成温度（0-1）',
                'is_encrypted' => false,
                'create_time' => $now,
                'update_time' => $now
            ]
        ];

        $this->table('kg_ai_config')->insert($data)->saveData();
    }
}


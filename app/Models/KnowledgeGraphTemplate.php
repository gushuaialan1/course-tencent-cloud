<?php
/**
 * 知识图谱模板模型
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Models;

class KnowledgeGraphTemplate extends Model
{
    /**
     * 模板分类常量
     */
    const CATEGORY_CS = 'cs';              // 计算机科学
    const CATEGORY_MATH = 'math';          // 数学
    const CATEGORY_LANGUAGE = 'language';  // 语言
    const CATEGORY_BUSINESS = 'business';  // 商业
    const CATEGORY_OTHER = 'other';        // 其他

    /**
     * 难度级别常量
     */
    const DIFFICULTY_BEGINNER = 'beginner';           // 初级
    const DIFFICULTY_INTERMEDIATE = 'intermediate';   // 中级
    const DIFFICULTY_ADVANCED = 'advanced';           // 高级

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 模板名称
     *
     * @var string
     */
    public $name = '';

    /**
     * 模板分类
     *
     * @var string
     */
    public $category = self::CATEGORY_OTHER;

    /**
     * 模板描述
     *
     * @var string
     */
    public $description = '';

    /**
     * 预览图片URL
     *
     * @var string
     */
    public $preview_image = '';

    /**
     * 节点数据JSON
     *
     * @var string
     */
    public $node_data = '';

    /**
     * 关系数据JSON
     *
     * @var string
     */
    public $relation_data = '';

    /**
     * 节点数量
     *
     * @var int
     */
    public $node_count = 0;

    /**
     * 关系数量
     *
     * @var int
     */
    public $relation_count = 0;

    /**
     * 难度级别
     *
     * @var string
     */
    public $difficulty_level = self::DIFFICULTY_BEGINNER;

    /**
     * 标签（逗号分隔）
     *
     * @var string
     */
    public $tags = '';

    /**
     * 是否系统预置模板
     *
     * @var bool
     */
    public $is_system = false;

    /**
     * 是否启用
     *
     * @var bool
     */
    public $is_active = true;

    /**
     * 使用次数
     *
     * @var int
     */
    public $usage_count = 0;

    /**
     * 排序顺序
     *
     * @var int
     */
    public $sort_order = 0;

    /**
     * 创建者用户ID
     *
     * @var int
     */
    public $created_by = 0;

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
        return 'kg_knowledge_graph_template';
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
     * 获取所有模板分类
     *
     * @return array
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_CS => '计算机科学',
            self::CATEGORY_MATH => '数学',
            self::CATEGORY_LANGUAGE => '语言',
            self::CATEGORY_BUSINESS => '商业',
            self::CATEGORY_OTHER => '其他'
        ];
    }

    /**
     * 获取所有难度级别
     *
     * @return array
     */
    public static function getDifficultyLevels(): array
    {
        return [
            self::DIFFICULTY_BEGINNER => '初级',
            self::DIFFICULTY_INTERMEDIATE => '中级',
            self::DIFFICULTY_ADVANCED => '高级'
        ];
    }

    /**
     * 获取分类显示名称
     *
     * @return string
     */
    public function getCategoryLabel(): string
    {
        $categories = self::getCategories();
        return $categories[$this->category] ?? $this->category;
    }

    /**
     * 获取难度级别显示名称
     *
     * @return string
     */
    public function getDifficultyLabel(): string
    {
        $levels = self::getDifficultyLevels();
        return $levels[$this->difficulty_level] ?? $this->difficulty_level;
    }

    /**
     * 获取节点数据（JSON解析）
     *
     * @return array
     */
    public function getNodeDataArray(): array
    {
        if (empty($this->node_data)) {
            return [];
        }

        $data = json_decode($this->node_data, true);
        return is_array($data) ? $data : [];
    }

    /**
     * 设置节点数据
     *
     * @param array $nodes
     * @return void
     */
    public function setNodeDataArray(array $nodes): void
    {
        $this->node_data = json_encode($nodes, JSON_UNESCAPED_UNICODE);
        $this->node_count = count($nodes);
    }

    /**
     * 获取关系数据（JSON解析）
     *
     * @return array
     */
    public function getRelationDataArray(): array
    {
        if (empty($this->relation_data)) {
            return [];
        }

        $data = json_decode($this->relation_data, true);
        return is_array($data) ? $data : [];
    }

    /**
     * 设置关系数据
     *
     * @param array $relations
     * @return void
     */
    public function setRelationDataArray(array $relations): void
    {
        $this->relation_data = json_encode($relations, JSON_UNESCAPED_UNICODE);
        $this->relation_count = count($relations);
    }

    /**
     * 获取标签数组
     *
     * @return array
     */
    public function getTagsArray(): array
    {
        if (empty($this->tags)) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $this->tags)));
    }

    /**
     * 设置标签数组
     *
     * @param array $tags
     * @return void
     */
    public function setTagsArray(array $tags): void
    {
        $this->tags = implode(',', array_filter($tags));
    }

    /**
     * 增加使用次数
     *
     * @return bool
     */
    public function incrementUsageCount(): bool
    {
        $this->usage_count++;
        return $this->save();
    }
}


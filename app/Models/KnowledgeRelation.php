<?php
/**
 * 知识图谱关系模型
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Models;

class KnowledgeRelation extends Model
{
    /**
     * 关系类型常量
     */
    const TYPE_PREREQUISITE = 'prerequisite';   // 前置关系
    const TYPE_CONTAINS = 'contains';           // 包含关系
    const TYPE_RELATED = 'related';             // 相关关系
    const TYPE_SUGGESTS = 'suggests';           // 建议关系
    const TYPE_EXTENDS = 'extends';             // 扩展关系

    /**
     * 关系状态常量
     */
    const STATUS_ACTIVE = 'active';     // 启用
    const STATUS_INACTIVE = 'inactive'; // 禁用

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 起始节点ID
     *
     * @var int
     */
    public $from_node_id = 0;

    /**
     * 目标节点ID
     *
     * @var int
     */
    public $to_node_id = 0;

    /**
     * 关系类型
     *
     * @var string
     */
    public $relation_type = self::TYPE_RELATED;

    /**
     * 关系权重
     *
     * @var float
     */
    public $weight = 1.00;

    /**
     * 关系描述
     *
     * @var string
     */
    public $description = '';

    /**
     * 扩展属性
     *
     * @var string
     */
    public $properties = '';

    /**
     * 样式配置
     *
     * @var string
     */
    public $style_config = '';

    /**
     * 状态
     *
     * @var string
     */
    public $status = self::STATUS_ACTIVE;

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
        return 'kg_knowledge_relation';
    }

    public function initialize()
    {
        parent::initialize();

        // 定义关联关系
        $this->belongsTo('from_node_id', KnowledgeNode::class, 'id', [
            'alias' => 'fromNode'
        ]);

        $this->belongsTo('to_node_id', KnowledgeNode::class, 'id', [
            'alias' => 'toNode'
        ]);
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
     * 获取所有关系类型
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PREREQUISITE => '前置关系',
            self::TYPE_CONTAINS => '包含关系',
            self::TYPE_RELATED => '相关关系',
            self::TYPE_SUGGESTS => '建议关系',
            self::TYPE_EXTENDS => '扩展关系'
        ];
    }

    /**
     * 获取所有状态
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => '启用',
            self::STATUS_INACTIVE => '禁用'
        ];
    }

    /**
     * 获取关系类型显示名称
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        $types = self::getTypes();
        return $types[$this->relation_type] ?? $this->relation_type;
    }

    /**
     * 获取状态显示名称
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        $statuses = self::getStatuses();
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 获取扩展属性（JSON解析）
     *
     * @return array
     */
    public function getPropertiesData(): array
    {
        if (empty($this->properties)) {
            return [];
        }

        $data = json_decode($this->properties, true);
        return is_array($data) ? $data : [];
    }

    /**
     * 设置扩展属性
     *
     * @param array $properties
     * @return void
     */
    public function setPropertiesData(array $properties): void
    {
        $this->properties = json_encode($properties, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取样式配置（JSON解析）
     *
     * @return array
     */
    public function getStyleConfigData(): array
    {
        if (empty($this->style_config)) {
            return $this->getDefaultStyleConfig();
        }

        $data = json_decode($this->style_config, true);
        return is_array($data) ? array_merge($this->getDefaultStyleConfig(), $data) : $this->getDefaultStyleConfig();
    }

    /**
     * 设置样式配置
     *
     * @param array $config
     * @return void
     */
    public function setStyleConfigData(array $config): void
    {
        $this->style_config = json_encode($config, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取默认样式配置
     *
     * @return array
     */
    public function getDefaultStyleConfig(): array
    {
        $configs = [
            self::TYPE_PREREQUISITE => [
                'line-color' => '#FF5722',
                'target-arrow-color' => '#FF5722',
                'target-arrow-shape' => 'triangle',
                'curve-style' => 'bezier',
                'width' => 3,
                'line-style' => 'solid',
                'label' => '前置',
                'text-rotation' => 'autorotate',
                'font-size' => '10px'
            ],
            self::TYPE_CONTAINS => [
                'line-color' => '#2196F3',
                'target-arrow-color' => '#2196F3',
                'target-arrow-shape' => 'triangle',
                'curve-style' => 'bezier',
                'width' => 2,
                'line-style' => 'solid',
                'label' => '包含',
                'text-rotation' => 'autorotate',
                'font-size' => '10px'
            ],
            self::TYPE_RELATED => [
                'line-color' => '#4CAF50',
                'target-arrow-color' => '#4CAF50',
                'target-arrow-shape' => 'circle',
                'curve-style' => 'straight',
                'width' => 2,
                'line-style' => 'dashed',
                'label' => '相关',
                'text-rotation' => 'autorotate',
                'font-size' => '10px'
            ],
            self::TYPE_SUGGESTS => [
                'line-color' => '#FF9800',
                'target-arrow-color' => '#FF9800',
                'target-arrow-shape' => 'triangle',
                'curve-style' => 'bezier',
                'width' => 2,
                'line-style' => 'dotted',
                'label' => '建议',
                'text-rotation' => 'autorotate',
                'font-size' => '10px'
            ],
            self::TYPE_EXTENDS => [
                'line-color' => '#9C27B0',
                'target-arrow-color' => '#9C27B0',
                'target-arrow-shape' => 'triangle',
                'curve-style' => 'bezier',
                'width' => 2,
                'line-style' => 'solid',
                'label' => '扩展',
                'text-rotation' => 'autorotate',
                'font-size' => '10px'
            ]
        ];

        return $configs[$this->relation_type] ?? $configs[self::TYPE_RELATED];
    }

    /**
     * 获取关系方向信息
     *
     * @return array
     */
    public function getDirectionInfo(): array
    {
        $directions = [
            self::TYPE_PREREQUISITE => ['directed' => true, 'label' => 'A 是 B 的前置'],
            self::TYPE_CONTAINS => ['directed' => true, 'label' => 'A 包含 B'],
            self::TYPE_RELATED => ['directed' => false, 'label' => 'A 与 B 相关'],
            self::TYPE_SUGGESTS => ['directed' => true, 'label' => 'A 建议学习 B'],
            self::TYPE_EXTENDS => ['directed' => true, 'label' => 'A 扩展了 B']
        ];

        return $directions[$this->relation_type] ?? $directions[self::TYPE_RELATED];
    }

    /**
     * 检查关系是否有效
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // 不能自己指向自己
        if ($this->from_node_id === $this->to_node_id) {
            return false;
        }

        // 检查节点是否存在
        $fromNode = KnowledgeNode::findFirst($this->from_node_id);
        $toNode = KnowledgeNode::findFirst($this->to_node_id);

        if (!$fromNode || !$toNode) {
            return false;
        }

        // 前置关系需要检查循环依赖
        if ($this->relation_type === self::TYPE_PREREQUISITE) {
            return $fromNode->canBePrerequisiteOf($toNode);
        }

        return true;
    }

    /**
     * 获取反向关系类型
     *
     * @return string|null
     */
    public function getReverseRelationType(): ?string
    {
        $reverseMap = [
            self::TYPE_PREREQUISITE => null, // 前置关系没有直接的反向关系
            self::TYPE_CONTAINS => null,     // 包含关系没有直接的反向关系
            self::TYPE_RELATED => self::TYPE_RELATED,     // 相关关系是双向的
            self::TYPE_SUGGESTS => null,     // 建议关系没有直接的反向关系
            self::TYPE_EXTENDS => null       // 扩展关系没有直接的反向关系
        ];

        return $reverseMap[$this->relation_type] ?? null;
    }

    /**
     * 创建反向关系（如果适用）
     *
     * @return KnowledgeRelation|null
     */
    public function createReverseRelation(): ?KnowledgeRelation
    {
        $reverseType = $this->getReverseRelationType();
        if (!$reverseType) {
            return null;
        }

        // 检查反向关系是否已存在
        $existing = self::findFirst([
            'conditions' => 'from_node_id = :to: AND to_node_id = :from: AND relation_type = :type:',
            'bind' => [
                'to' => $this->to_node_id,
                'from' => $this->from_node_id,
                'type' => $reverseType
            ]
        ]);

        if ($existing) {
            return $existing;
        }

        // 创建新的反向关系
        $reverseRelation = new self();
        $reverseRelation->from_node_id = $this->to_node_id;
        $reverseRelation->to_node_id = $this->from_node_id;
        $reverseRelation->relation_type = $reverseType;
        $reverseRelation->weight = $this->weight;
        $reverseRelation->description = '自动生成的反向关系';
        $reverseRelation->status = $this->status;
        $reverseRelation->created_by = $this->created_by;

        if ($reverseRelation->save()) {
            return $reverseRelation;
        }

        return null;
    }
}

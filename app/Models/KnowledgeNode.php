<?php
/**
 * 知识图谱节点模型
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Models;

use Phalcon\Mvc\Model\Behavior\SoftDelete;

class KnowledgeNode extends Model
{
    /**
     * 节点类型常量
     */
    const TYPE_CONCEPT = 'concept';    // 概念节点
    const TYPE_TOPIC = 'topic';        // 主题节点  
    const TYPE_SKILL = 'skill';        // 技能节点
    const TYPE_COURSE = 'course';      // 课程节点

    /**
     * 节点状态常量
     */
    const STATUS_DRAFT = 'draft';           // 草稿
    const STATUS_PUBLISHED = 'published';   // 已发布
    const STATUS_ARCHIVED = 'archived';     // 已归档

    /**
     * 主键编号
     *
     * @var int
     */
    public $id = 0;

    /**
     * 节点名称
     *
     * @var string
     */
    public $name = '';

    /**
     * 节点类型
     *
     * @var string
     */
    public $type = self::TYPE_CONCEPT;

    /**
     * 节点描述
     *
     * @var string
     */
    public $description = '';

    /**
     * 扩展属性（JSON格式，默认为空对象）
     *
     * @var string
     */
    public $properties = '{}';

    /**
     * 关联课程ID
     *
     * @var int
     */
    public $course_id = 0;

    /**
     * 关联章节ID
     *
     * @var int
     */
    public $chapter_id = 0;

    /**
     * X坐标位置
     *
     * @var float
     */
    public $position_x = 0.00;

    /**
     * Y坐标位置
     *
     * @var float
     */
    public $position_y = 0.00;

    /**
     * 样式配置（JSON格式，默认为空对象）
     *
     * @var string
     */
    public $style_config = '{}';

    /**
     * 节点权重
     *
     * @var float
     */
    public $weight = 1.00;

    /**
     * 状态
     *
     * @var string
     */
    public $status = self::STATUS_DRAFT;

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
        return 'kg_knowledge_node';
    }

    public function initialize()
    {
        parent::initialize();

        // 定义关联关系
        $this->hasMany('id', KnowledgeRelation::class, 'from_node_id', [
            'alias' => 'outgoingRelations'
        ]);

        $this->hasMany('id', KnowledgeRelation::class, 'to_node_id', [
            'alias' => 'incomingRelations'
        ]);

        $this->belongsTo('course_id', Course::class, 'id', [
            'alias' => 'course'
        ]);

        $this->belongsTo('chapter_id', Chapter::class, 'id', [
            'alias' => 'chapter'
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
     * 获取所有节点类型
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_CONCEPT => '概念',
            self::TYPE_TOPIC => '主题',
            self::TYPE_SKILL => '技能',
            self::TYPE_COURSE => '课程'
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
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_ARCHIVED => '已归档'
        ];
    }

    /**
     * 获取节点类型显示名称
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
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
            self::TYPE_CONCEPT => [
                'shape' => 'ellipse',
                'background-color' => '#FF5722',
                'color' => '#fff',
                'border-width' => 2,
                'border-color' => '#D32F2F',
                'font-size' => '12px',
                'width' => 60,
                'height' => 60
            ],
            self::TYPE_TOPIC => [
                'shape' => 'rectangle',
                'background-color' => '#2196F3',
                'color' => '#fff',
                'border-width' => 2,
                'border-color' => '#1976D2',
                'font-size' => '12px',
                'width' => 80,
                'height' => 40
            ],
            self::TYPE_SKILL => [
                'shape' => 'diamond',
                'background-color' => '#4CAF50',
                'color' => '#fff',
                'border-width' => 2,
                'border-color' => '#388E3C',
                'font-size' => '12px',
                'width' => 70,
                'height' => 70
            ],
            self::TYPE_COURSE => [
                'shape' => 'roundrectangle',
                'background-color' => '#009688',
                'color' => '#fff',
                'border-width' => 2,
                'border-color' => '#00695C',
                'font-size' => '12px',
                'width' => 100,
                'height' => 50
            ]
        ];

        return $configs[$this->type] ?? $configs[self::TYPE_CONCEPT];
    }

    /**
     * 获取前置节点
     *
     * @return array
     */
    public function getPrerequisiteNodes(): array
    {
        return $this->getRelatedNodesByType(KnowledgeRelation::TYPE_PREREQUISITE, 'incoming');
    }

    /**
     * 获取后续节点
     *
     * @return array
     */
    public function getDependentNodes(): array
    {
        return $this->getRelatedNodesByType(KnowledgeRelation::TYPE_PREREQUISITE, 'outgoing');
    }

    /**
     * 获取相关节点
     *
     * @return array
     */
    public function getRelatedNodes(): array
    {
        return $this->getRelatedNodesByType(KnowledgeRelation::TYPE_RELATED);
    }

    /**
     * 根据关系类型获取相关节点
     *
     * @param string $relationType
     * @param string $direction
     * @return array
     */
    private function getRelatedNodesByType(string $relationType, string $direction = 'both'): array
    {
        $nodes = [];

        if ($direction === 'incoming' || $direction === 'both') {
            $incomingRelations = $this->getRelated('incomingRelations', [
                'conditions' => 'relation_type = :type: AND status = :status:',
                'bind' => [
                    'type' => $relationType,
                    'status' => KnowledgeRelation::STATUS_ACTIVE
                ]
            ]);

            foreach ($incomingRelations as $relation) {
                $fromNode = $relation->getRelated('fromNode');
                if ($fromNode) {
                    $nodes[] = $fromNode;
                }
            }
        }

        if ($direction === 'outgoing' || $direction === 'both') {
            $outgoingRelations = $this->getRelated('outgoingRelations', [
                'conditions' => 'relation_type = :type: AND status = :status:',
                'bind' => [
                    'type' => $relationType,
                    'status' => KnowledgeRelation::STATUS_ACTIVE
                ]
            ]);

            foreach ($outgoingRelations as $relation) {
                $toNode = $relation->getRelated('toNode');
                if ($toNode) {
                    $nodes[] = $toNode;
                }
            }
        }

        return $nodes;
    }

    /**
     * 检查是否可以作为前置节点
     *
     * @param KnowledgeNode $targetNode
     * @return bool
     */
    public function canBePrerequisiteOf(KnowledgeNode $targetNode): bool
    {
        // 不能自己作为自己的前置
        if ($this->id === $targetNode->id) {
            return false;
        }

        // 检查是否会形成循环依赖
        return !$this->hasCircularDependency($targetNode);
    }

    /**
     * 检查是否存在循环依赖
     *
     * @param KnowledgeNode $targetNode
     * @param array $visited
     * @return bool
     */
    private function hasCircularDependency(KnowledgeNode $targetNode, array $visited = []): bool
    {
        if (in_array($this->id, $visited)) {
            return true;
        }

        $visited[] = $this->id;

        $prerequisites = $targetNode->getPrerequisiteNodes();
        foreach ($prerequisites as $prerequisite) {
            if ($prerequisite->id === $this->id) {
                return true;
            }

            if ($this->hasCircularDependency($prerequisite, $visited)) {
                return true;
            }
        }

        return false;
    }
}

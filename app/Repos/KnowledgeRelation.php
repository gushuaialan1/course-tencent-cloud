<?php
/**
 * 知识图谱关系仓储类
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Repos;

use App\Models\KnowledgeRelation as KnowledgeRelationModel;
use App\Models\KnowledgeNode as KnowledgeNodeModel;

class KnowledgeRelation extends Repository
{
    /**
     * 根据ID查找关系
     *
     * @param int $id
     * @return KnowledgeRelationModel|null
     */
    public function findById(int $id): ?KnowledgeRelationModel
    {
        return KnowledgeRelationModel::findFirst([
            'conditions' => 'id = :id:',
            'bind' => ['id' => $id]
        ]);
    }

    /**
     * 根据课程ID查找所有关系
     *
     * @param int $courseId
     * @param array $options
     * @return array
     */
    public function findByCourseId(int $courseId, array $options = []): array
    {
        // 先获取课程的所有已发布节点ID
        $nodeConditions = 'course_id = :course_id:';
        $nodeBind = ['course_id' => $courseId];
        
        // 如果选项中指定了状态，则使用指定的状态，否则只查询已发布的节点
        if (isset($options['node_status'])) {
            $nodeConditions .= ' AND status = :node_status:';
            $nodeBind['node_status'] = $options['node_status'];
        } else {
            $nodeConditions .= ' AND status = :node_status:';
            $nodeBind['node_status'] = 'published';
        }
        
        $nodes = KnowledgeNodeModel::find([
            'conditions' => $nodeConditions,
            'bind' => $nodeBind,
            'columns' => 'id'
        ]);
        
        if (count($nodes) === 0) {
            error_log("KnowledgeRelation::findByCourseId - 课程 {$courseId} 没有找到节点");
            return [];
        }
        
        $nodeIds = array_column($nodes->toArray(), 'id');
        error_log("KnowledgeRelation::findByCourseId - 课程 {$courseId} 找到 " . count($nodeIds) . " 个节点");
        
        $conditions = [];
        $bind = [];
        
        // 节点ID过滤 - from_node_id 和 to_node_id 都必须在节点列表中
        $nodeIdList = implode(',', array_map('intval', $nodeIds));
        $conditions[] = 'from_node_id IN (' . $nodeIdList . ')';
        $conditions[] = 'to_node_id IN (' . $nodeIdList . ')';

        // 关系类型过滤
        if (!empty($options['relation_type'])) {
            $conditions[] = 'relation_type = :relation_type:';
            $bind['relation_type'] = $options['relation_type'];
        }

        // 状态过滤
        if (isset($options['status'])) {
            $conditions[] = 'status = :status:';
            $bind['status'] = $options['status'];
        } else {
            $conditions[] = 'status = :status:';
            $bind['status'] = KnowledgeRelationModel::STATUS_ACTIVE;
        }

        $params = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'weight DESC, create_time DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $params['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $params['offset'] = $options['offset'];
            }
        }

        $relations = KnowledgeRelationModel::find($params)->toArray();
        error_log("KnowledgeRelation::findByCourseId - 找到 " . count($relations) . " 个关系");
        
        return $relations;
    }

    /**
     * 根据节点ID查找关系
     *
     * @param int $nodeId
     * @param string $direction
     * @return array
     */
    public function findByNodeId(int $nodeId, string $direction = 'both'): array
    {
        if ($direction === 'outgoing') {
            $conditions = 'from_node_id = :node_id:';
        } elseif ($direction === 'incoming') {
            $conditions = 'to_node_id = :node_id:';
        } else {
            $conditions = 'from_node_id = :node_id: OR to_node_id = :node_id:';
        }

        return KnowledgeRelationModel::find([
            'conditions' => $conditions . ' AND status = :status:',
            'bind' => [
                'node_id' => $nodeId,
                'status' => KnowledgeRelationModel::STATUS_ACTIVE
            ],
            'order' => 'weight DESC, create_time DESC'
        ])->toArray();
    }

    /**
     * 根据关系类型查找关系
     *
     * @param string $relationType
     * @param array $options
     * @return array
     */
    public function findByType(string $relationType, array $options = []): array
    {
        $conditions = ['relation_type = :type:', 'status = :status:'];
        $bind = [
            'type' => $relationType,
            'status' => KnowledgeRelationModel::STATUS_ACTIVE
        ];

        // 课程过滤（通过节点）
        if (!empty($options['course_id'])) {
            $conditions[] = 'from_node_id IN (SELECT id FROM kg_knowledge_node WHERE course_id = :course_id:)';
            $bind['course_id'] = $options['course_id'];
        }

        $params = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'weight DESC, create_time DESC'
        ];

        if (!empty($options['limit'])) {
            $params['limit'] = $options['limit'];
        }

        return KnowledgeRelationModel::find($params)->toArray();
    }

    /**
     * 检查关系是否存在
     *
     * @param int $fromNodeId
     * @param int $toNodeId
     * @param string $relationType
     * @return KnowledgeRelationModel|null
     */
    public function findRelation(int $fromNodeId, int $toNodeId, string $relationType): ?KnowledgeRelationModel
    {
        $result = KnowledgeRelationModel::findFirst([
            'conditions' => 'from_node_id = :from: AND to_node_id = :to: AND relation_type = :type:',
            'bind' => [
                'from' => $fromNodeId,
                'to' => $toNodeId,
                'type' => $relationType
            ]
        ]);
        
        // Phalcon的findFirst在找不到时返回false，需要转换为null
        return $result ?: null;
    }

    /**
     * 创建关系
     *
     * @param array $data
     * @return KnowledgeRelationModel|false
     */
    public function createRelation(array $data)
    {
        // 验证节点是否存在
        $fromNode = KnowledgeNodeModel::findFirst($data['from_node_id']);
        $toNode = KnowledgeNodeModel::findFirst($data['to_node_id']);

        if (!$fromNode || !$toNode) {
            return false;
        }

        // 检查关系是否已存在
        $existing = $this->findRelation(
            $data['from_node_id'],
            $data['to_node_id'],
            $data['relation_type']
        );

        if ($existing) {
            return false; // 关系已存在
        }

        $relation = new KnowledgeRelationModel();
        $relation->from_node_id = $data['from_node_id'];
        $relation->to_node_id = $data['to_node_id'];
        $relation->relation_type = $data['relation_type'];
        $relation->weight = $data['weight'] ?? 1.00;
        $relation->description = $data['description'] ?? '';
        $relation->status = $data['status'] ?? KnowledgeRelationModel::STATUS_ACTIVE;
        $relation->created_by = $data['created_by'] ?? 0;

        // 扩展属性（必须设置为有效JSON，即使为空）
        if (isset($data['properties'])) {
            $relation->setPropertiesData($data['properties']);
        } else {
            $relation->properties = '{}';  // 空JSON对象
        }

        // 样式配置（必须设置为有效JSON，即使为空）
        if (isset($data['style_config'])) {
            $relation->setStyleConfigData($data['style_config']);
        } else {
            $relation->style_config = '{}';  // 空JSON对象
        }

        // 验证关系是否有效
        if (!$relation->isValid()) {
            return false;
        }

        if ($relation->save()) {
            // 为双向关系创建反向关系
            $relation->createReverseRelation();
            return $relation;
        }

        return false;
    }

    /**
     * 更新关系
     *
     * @param KnowledgeRelationModel $relation
     * @param array $data
     * @return bool
     */
    public function updateRelation(KnowledgeRelationModel $relation, array $data): bool
    {
        // 基本字段更新
        $fields = ['weight', 'description', 'status'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $relation->$field = $data[$field];
            }
        }

        $relation->updated_by = $data['updated_by'] ?? 0;

        // 扩展属性更新
        if (array_key_exists('properties', $data)) {
            $relation->setPropertiesData($data['properties']);
        }

        // 样式配置更新
        if (array_key_exists('style_config', $data)) {
            $relation->setStyleConfigData($data['style_config']);
        }

        return $relation->save();
    }

    /**
     * 删除关系
     *
     * @param KnowledgeRelationModel $relation
     * @return bool
     */
    public function deleteRelation(KnowledgeRelationModel $relation): bool
    {
        // 删除反向关系（如果存在）
        $reverseType = $relation->getReverseRelationType();
        if ($reverseType) {
            $reverseRelation = $this->findRelation(
                $relation->to_node_id,
                $relation->from_node_id,
                $reverseType
            );
            if ($reverseRelation) {
                $reverseRelation->delete();
            }
        }

        return $relation->delete();
    }

    /**
     * 批量创建关系
     *
     * @param array $relations
     * @return array
     */
    public function createBatchRelations(array $relations): array
    {
        $results = [];

        foreach ($relations as $relationData) {
            $result = $this->createRelation($relationData);
            $results[] = [
                'data' => $relationData,
                'success' => $result !== false,
                'relation' => $result
            ];
        }

        return $results;
    }

    /**
     * 获取关系统计信息
     *
     * @param array $options
     * @return array
     */
    public function getRelationStatistics(array $options = []): array
    {
        $conditions = ['status = :status:'];
        $bind = ['status' => KnowledgeRelationModel::STATUS_ACTIVE];

        // 课程过滤 - 修复：先获取节点ID，避免子查询问题
        if (!empty($options['course_id'])) {
            // 获取该课程的所有节点ID
            $nodes = KnowledgeNodeModel::find([
                'conditions' => 'course_id = :course_id:',
                'bind' => ['course_id' => $options['course_id']],
                'columns' => 'id'
            ]);
            
            if (count($nodes) === 0) {
                // 如果没有节点，返回空统计
                return [
                    'total' => 0,
                    'by_type' => []
                ];
            }
            
            $nodeIds = array_column($nodes->toArray(), 'id');
            $nodeIdList = implode(',', array_map('intval', $nodeIds));
            $conditions[] = "from_node_id IN ({$nodeIdList})";
        }

        $baseConditions = implode(' AND ', $conditions);

        // 总数统计
        $total = KnowledgeRelationModel::count([
            'conditions' => $baseConditions,
            'bind' => $bind
        ]);

        // 按类型统计
        $typeStats = [];
        $types = KnowledgeRelationModel::getTypes();
        foreach ($types as $type => $label) {
            $count = KnowledgeRelationModel::count([
                'conditions' => $baseConditions . ' AND relation_type = :type:',
                'bind' => array_merge($bind, ['type' => $type])
            ]);
            $typeStats[$type] = ['label' => $label, 'count' => $count];
        }

        return [
            'total' => $total,
            'by_type' => $typeStats
        ];
    }

    /**
     * 检测循环依赖
     *
     * @param int $fromNodeId
     * @param int $toNodeId
     * @param string $relationType
     * @param array $visited
     * @return bool
     */
    public function hasCircularDependency(int $fromNodeId, int $toNodeId, string $relationType, array $visited = []): bool
    {
        // 只检查前置关系的循环依赖
        if ($relationType !== KnowledgeRelationModel::TYPE_PREREQUISITE) {
            return false;
        }

        if (in_array($fromNodeId, $visited)) {
            return true;
        }

        $visited[] = $fromNodeId;

        // 查找目标节点的所有前置关系
        $prerequisites = KnowledgeRelationModel::find([
            'conditions' => 'to_node_id = :to: AND relation_type = :type: AND status = :status:',
            'bind' => [
                'to' => $toNodeId,
                'type' => KnowledgeRelationModel::TYPE_PREREQUISITE,
                'status' => KnowledgeRelationModel::STATUS_ACTIVE
            ]
        ]);

        foreach ($prerequisites as $prerequisite) {
            if ($prerequisite->from_node_id === $fromNodeId) {
                return true;
            }

            if ($this->hasCircularDependency($fromNodeId, $prerequisite->from_node_id, $relationType, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取节点的依赖链
     *
     * @param int $nodeId
     * @param string $direction
     * @param int $maxDepth
     * @return array
     */
    public function getDependencyChain(int $nodeId, string $direction = 'prerequisite', int $maxDepth = 10): array
    {
        $chain = [];
        $visited = [];
        $this->buildDependencyChain($nodeId, $direction, $chain, $visited, 0, $maxDepth);
        return $chain;
    }

    /**
     * 递归构建依赖链
     *
     * @param int $nodeId
     * @param string $direction
     * @param array &$chain
     * @param array &$visited
     * @param int $depth
     * @param int $maxDepth
     * @return void
     */
    private function buildDependencyChain(int $nodeId, string $direction, array &$chain, array &$visited, int $depth, int $maxDepth): void
    {
        if ($depth >= $maxDepth || in_array($nodeId, $visited)) {
            return;
        }

        $visited[] = $nodeId;
        $node = KnowledgeNodeModel::findFirst($nodeId);
        if (!$node) {
            return;
        }

        $chain[] = [
            'node' => $node->toArray(),
            'depth' => $depth
        ];

        // 根据方向查找关系
        if ($direction === 'prerequisite') {
            // 查找前置节点
            $relations = KnowledgeRelationModel::find([
                'conditions' => 'to_node_id = :id: AND relation_type = :type: AND status = :status:',
                'bind' => [
                    'id' => $nodeId,
                    'type' => KnowledgeRelationModel::TYPE_PREREQUISITE,
                    'status' => KnowledgeRelationModel::STATUS_ACTIVE
                ]
            ]);

            foreach ($relations as $relation) {
                $this->buildDependencyChain($relation->from_node_id, $direction, $chain, $visited, $depth + 1, $maxDepth);
            }
        } else {
            // 查找后续节点
            $relations = KnowledgeRelationModel::find([
                'conditions' => 'from_node_id = :id: AND relation_type = :type: AND status = :status:',
                'bind' => [
                    'id' => $nodeId,
                    'type' => KnowledgeRelationModel::TYPE_PREREQUISITE,
                    'status' => KnowledgeRelationModel::STATUS_ACTIVE
                ]
            ]);

            foreach ($relations as $relation) {
                $this->buildDependencyChain($relation->to_node_id, $direction, $chain, $visited, $depth + 1, $maxDepth);
            }
        }
    }
}

<?php
/**
 * 知识图谱节点仓储类
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Repos;

use App\Models\KnowledgeNode as KnowledgeNodeModel;
use App\Models\KnowledgeRelation as KnowledgeRelationModel;

class KnowledgeNode extends Repository
{
    /**
     * 根据ID查找节点
     *
     * @param int $id
     * @return KnowledgeNodeModel|null
     */
    public function findById(int $id): ?KnowledgeNodeModel
    {
        $result = KnowledgeNodeModel::findFirst([
            'conditions' => 'id = :id:',
            'bind' => ['id' => $id]
        ]);
        
        // Phalcon的findFirst在找不到时返回false，需要转换为null
        return $result ?: null;
    }

    /**
     * 根据绑定的资源查找节点（避免重复创建）
     *
     * @param int $courseId
     * @param string $resourceType
     * @param int $resourceId
     * @return KnowledgeNodeModel|null
     */
    public function findByResource(int $courseId, string $resourceType, int $resourceId): ?KnowledgeNodeModel
    {
        $result = KnowledgeNodeModel::findFirst([
            'conditions' => 'course_id = :course_id: AND primary_resource_type = :type: AND primary_resource_id = :resource_id:',
            'bind' => [
                'course_id' => $courseId,
                'type' => $resourceType,
                'resource_id' => $resourceId
            ]
        ]);
        
        // Phalcon的findFirst在找不到时返回false，需要转换为null
        return $result ?: null;
    }

    /**
     * 根据课程ID查找节点
     *
     * @param int $courseId
     * @param array $options
     * @return array
     */
    public function findByCourseId(int $courseId, array $options = []): array
    {
        $conditions = ['course_id = :course_id:'];
        $bind = ['course_id' => $courseId];

        // 节点类型过滤
        if (!empty($options['type'])) {
            $conditions[] = 'type = :type:';
            $bind['type'] = $options['type'];
        }

        // 状态过滤
        if (!empty($options['status'])) {
            $conditions[] = 'status = :status:';
            $bind['status'] = $options['status'];
        }

        $params = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'sort_order ASC, create_time DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $params['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $params['offset'] = $options['offset'];
            }
        }

        return KnowledgeNodeModel::find($params)->toArray();
    }

    /**
     * 根据名称搜索节点
     *
     * @param string $keyword
     * @param array $options
     * @return array
     */
    public function searchByName(string $keyword, array $options = []): array
    {
        $conditions = ['name LIKE :keyword:'];
        $bind = ['keyword' => "%{$keyword}%"];

        // 课程ID过滤
        if (!empty($options['course_id'])) {
            $conditions[] = 'course_id = :course_id:';
            $bind['course_id'] = $options['course_id'];
        }

        // 节点类型过滤
        if (!empty($options['type'])) {
            $conditions[] = 'type = :type:';
            $bind['type'] = $options['type'];
        }

        $params = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'weight DESC, create_time DESC',
            'limit' => $options['limit'] ?? 50
        ];

        return KnowledgeNodeModel::find($params)->toArray();
    }

    /**
     * 统计课程节点数量(用于前台标签页显示)
     *
     * @param int $courseId
     * @return int
     */
    public function countByCourseId(int $courseId): int
    {
        return (int)KnowledgeNodeModel::count([
            'conditions' => 'course_id = :course_id:',
            'bind' => ['course_id' => $courseId]
        ]);
    }

    /**
     * 获取课程的知识图谱数据
     *
     * @param int $courseId
     * @return array
     */
    public function getCourseGraphData(int $courseId): array
    {
        // 获取所有节点
        $nodes = $this->findByCourseId($courseId, ['status' => KnowledgeNodeModel::STATUS_PUBLISHED]);

        // 获取所有关系
        $nodeIds = array_column($nodes, 'id');
        $relations = [];

        if (!empty($nodeIds)) {
            // 修复：使用字符串IN避免参数绑定冲突
            $nodeIdList = implode(',', array_map('intval', $nodeIds));
            $relations = KnowledgeRelationModel::find([
                'conditions' => "from_node_id IN ({$nodeIdList}) AND to_node_id IN ({$nodeIdList}) AND status = :status:",
                'bind' => [
                    'status' => KnowledgeRelationModel::STATUS_ACTIVE
                ]
            ])->toArray();
        }

        // 转换为Cytoscape.js格式
        return $this->convertToGraphFormat($nodes, $relations);
    }

    /**
     * 转换为图形格式
     *
     * @param array $nodes
     * @param array $relations
     * @return array
     */
    public function convertToGraphFormat(array $nodes, array $relations): array
    {
        $elements = [];

        // 转换节点
        foreach ($nodes as $node) {
            $nodeModel = new KnowledgeNodeModel();
            $nodeModel->assign($node);

            $elements[] = [
                'group' => 'nodes',
                'data' => [
                    'id' => $node['id'],
                    'label' => $node['name'],
                    'type' => $node['type'],
                    'description' => $node['description'],
                    'weight' => $node['weight'],
                    'properties' => $nodeModel->getPropertiesData()
                ],
                'position' => [
                    'x' => (float)$node['position_x'],
                    'y' => (float)$node['position_y']
                ],
                'style' => $nodeModel->getStyleConfigData()
            ];
        }

        // 转换关系
        foreach ($relations as $relation) {
            $relationModel = new KnowledgeRelationModel();
            $relationModel->assign($relation);

            $elements[] = [
                'group' => 'edges',
                'data' => [
                    'id' => $relation['id'],
                    'source' => $relation['from_node_id'],
                    'target' => $relation['to_node_id'],
                    'type' => $relation['relation_type'],
                    'weight' => $relation['weight'],
                    'description' => $relation['description'],
                    'properties' => $relationModel->getPropertiesData()
                ],
                'style' => $relationModel->getStyleConfigData()
            ];
        }

        return [
            'elements' => $elements,
            'statistics' => [
                'nodes' => count($nodes),
                'edges' => count($relations),
                'node_types' => array_count_values(array_column($nodes, 'type')),
                'edge_types' => array_count_values(array_column($relations, 'relation_type'))
            ]
        ];
    }

    /**
     * 创建节点
     *
     * @param array $data
     * @return KnowledgeNodeModel|false
     */
    public function createNode(array $data)
    {
        $node = new KnowledgeNodeModel();

        // 基本字段
        $node->name = $data['name'] ?? '';
        $node->type = $data['type'] ?? KnowledgeNodeModel::TYPE_CONCEPT;
        $node->description = $data['description'] ?? '';
        $node->course_id = $data['course_id'] ?? 0;
        $node->chapter_id = $data['chapter_id'] ?? null;
        $node->position_x = $data['position_x'] ?? 0;
        $node->position_y = $data['position_y'] ?? 0;
        $node->weight = $data['weight'] ?? 1.00;
        $node->status = $data['status'] ?? KnowledgeNodeModel::STATUS_DRAFT;
        $node->sort_order = $data['sort_order'] ?? 0;
        $node->created_by = $data['created_by'] ?? 0;
        
        // 资源绑定字段（用于避免重复创建）
        $node->primary_resource_type = $data['primary_resource_type'] ?? null;
        $node->primary_resource_id = $data['primary_resource_id'] ?? null;

        // 扩展属性（必须设置为有效JSON，即使为空）
        if (isset($data['properties'])) {
            $node->setPropertiesData($data['properties']);
        } else {
            $node->properties = '{}';  // 空JSON对象
        }

        // 样式配置（必须设置为有效JSON，即使为空）
        if (isset($data['style_config'])) {
            $node->setStyleConfigData($data['style_config']);
        } else {
            $node->style_config = '{}';  // 空JSON对象
        }

        if ($node->save()) {
            return $node;
        }

        return false;
    }

    /**
     * 更新节点
     *
     * @param KnowledgeNodeModel $node
     * @param array $data
     * @return bool
     */
    public function updateNode(KnowledgeNodeModel $node, array $data): bool
    {
        // 基本字段更新
        $fields = ['name', 'description', 'position_x', 'position_y', 'weight', 'status', 'sort_order'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $node->$field = $data[$field];
            }
        }

        $node->updated_by = $data['updated_by'] ?? 0;

        // 扩展属性更新
        if (array_key_exists('properties', $data)) {
            $node->setPropertiesData($data['properties']);
        }

        // 样式配置更新
        if (array_key_exists('style_config', $data)) {
            $node->setStyleConfigData($data['style_config']);
        }

        return $node->save();
    }

    /**
     * 删除节点
     *
     * @param KnowledgeNodeModel $node
     * @return bool
     */
    public function deleteNode(KnowledgeNodeModel $node): bool
    {
        // 删除相关的关系
        $this->deleteNodeRelations($node->id);

        // 删除节点
        return $node->delete();
    }

    /**
     * 删除节点的所有关系
     *
     * @param int $nodeId
     * @return bool
     */
    public function deleteNodeRelations(int $nodeId): bool
    {
        $relations = KnowledgeRelationModel::find([
            'conditions' => 'from_node_id = :id: OR to_node_id = :id:',
            'bind' => ['id' => $nodeId]
        ]);

        foreach ($relations as $relation) {
            $relation->delete();
        }

        return true;
    }

    /**
     * 批量更新节点位置
     *
     * @param array $positions
     * @return bool
     */
    public function updateNodesPosition(array $positions): bool
    {
        foreach ($positions as $position) {
            if (empty($position['id'])) {
                continue;
            }

            // 跳过临时节点ID（字符串形式，如 'node_1'）
            // 只处理已保存到数据库的节点（数字ID）
            if (!is_numeric($position['id'])) {
                continue;
            }

            $nodeId = intval($position['id']);
            if ($nodeId <= 0) {
                continue;
            }

            $node = $this->findById($nodeId);
            if (!$node) {
                continue;
            }

            $node->position_x = $position['x'] ?? $node->position_x;
            $node->position_y = $position['y'] ?? $node->position_y;
            $node->save();
        }

        return true;
    }

    /**
     * 获取节点统计信息
     *
     * @param int $courseId
     * @return array
     */
    public function getNodeStatistics(int $courseId): array
    {
        $conditions = 'course_id = :course_id:';
        $bind = ['course_id' => $courseId];

        // 总数
        $total = KnowledgeNodeModel::count([
            'conditions' => $conditions,
            'bind' => $bind
        ]);

        // 按类型统计
        $typeStats = [];
        $types = KnowledgeNodeModel::getTypes();
        foreach ($types as $type => $label) {
            $count = KnowledgeNodeModel::count([
                'conditions' => $conditions . ' AND type = :type:',
                'bind' => array_merge($bind, ['type' => $type])
            ]);
            $typeStats[$type] = ['label' => $label, 'count' => $count];
        }

        // 按状态统计
        $statusStats = [];
        $statuses = KnowledgeNodeModel::getStatuses();
        foreach ($statuses as $status => $label) {
            $count = KnowledgeNodeModel::count([
                'conditions' => $conditions . ' AND status = :status:',
                'bind' => array_merge($bind, ['status' => $status])
            ]);
            $statusStats[$status] = ['label' => $label, 'count' => $count];
        }

        return [
            'total' => $total,
            'by_type' => $typeStats,
            'by_status' => $statusStats
        ];
    }

    /**
     * 获取学习路径
     *
     * @param int $startNodeId
     * @param int $endNodeId
     * @return array
     */
    public function findLearningPath(int $startNodeId, int $endNodeId): array
    {
        // 使用Dijkstra算法找到最短学习路径
        // 这里简化实现，实际可以使用更复杂的图算法

        $visited = [];
        $distances = [];
        $previous = [];
        $queue = [];

        // 初始化
        $distances[$startNodeId] = 0;
        $queue[] = $startNodeId;

        while (!empty($queue)) {
            // 找到距离最短的未访问节点
            $current = array_shift($queue);
            if (in_array($current, $visited)) {
                continue;
            }

            $visited[] = $current;

            // 找到目标节点
            if ($current == $endNodeId) {
                break;
            }

            // 获取当前节点的所有前置关系
            $relations = KnowledgeRelationModel::find([
                'conditions' => 'from_node_id = :id: AND relation_type = :type: AND status = :status:',
                'bind' => [
                    'id' => $current,
                    'type' => KnowledgeRelationModel::TYPE_PREREQUISITE,
                    'status' => KnowledgeRelationModel::STATUS_ACTIVE
                ]
            ]);

            foreach ($relations as $relation) {
                $neighbor = $relation->to_node_id;
                $distance = $distances[$current] + $relation->weight;

                if (!isset($distances[$neighbor]) || $distance < $distances[$neighbor]) {
                    $distances[$neighbor] = $distance;
                    $previous[$neighbor] = $current;
                    $queue[] = $neighbor;
                }
            }
        }

        // 重建路径
        $path = [];
        $current = $endNodeId;

        while (isset($previous[$current])) {
            $path[] = $current;
            $current = $previous[$current];
        }

        if ($current == $startNodeId) {
            $path[] = $startNodeId;
            $path = array_reverse($path);
        } else {
            // 无法找到路径
            $path = [];
        }

        return $path;
    }
}

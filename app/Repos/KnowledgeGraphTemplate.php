<?php
/**
 * 知识图谱模板仓储类
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Repos;

use App\Models\KnowledgeGraphTemplate as KnowledgeGraphTemplateModel;
use App\Models\KnowledgeNode as KnowledgeNodeModel;
use App\Models\KnowledgeRelation as KnowledgeRelationModel;

class KnowledgeGraphTemplate extends Repository
{
    /**
     * 根据ID查找模板
     *
     * @param int $id
     * @return KnowledgeGraphTemplateModel|null
     */
    public function findById(int $id): ?KnowledgeGraphTemplateModel
    {
        return KnowledgeGraphTemplateModel::findFirst([
            'conditions' => 'id = :id: AND is_active = 1',
            'bind' => ['id' => $id]
        ]);
    }

    /**
     * 查找所有模板
     *
     * @param array $options
     * @return array
     */
    public function findAll(array $options = []): array
    {
        $conditions = ['is_active = 1'];
        $bind = [];

        // 分类过滤
        if (!empty($options['category'])) {
            $conditions[] = 'category = :category:';
            $bind['category'] = $options['category'];
        }

        // 难度级别过滤
        if (!empty($options['difficulty_level'])) {
            $conditions[] = 'difficulty_level = :difficulty_level:';
            $bind['difficulty_level'] = $options['difficulty_level'];
        }

        // 系统模板过滤
        if (isset($options['is_system'])) {
            $conditions[] = 'is_system = :is_system:';
            $bind['is_system'] = $options['is_system'] ? 1 : 0;
        }

        // 关键词搜索
        if (!empty($options['keyword'])) {
            $conditions[] = '(name LIKE :keyword: OR description LIKE :keyword: OR tags LIKE :keyword:)';
            $bind['keyword'] = "%{$options['keyword']}%";
        }

        $params = [
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind,
            'order' => $options['order'] ?? 'sort_order ASC, usage_count DESC, create_time DESC'
        ];

        // 分页
        if (!empty($options['limit'])) {
            $params['limit'] = $options['limit'];
            if (!empty($options['offset'])) {
                $params['offset'] = $options['offset'];
            }
        }

        return KnowledgeGraphTemplateModel::find($params)->toArray();
    }

    /**
     * 统计模板数量
     *
     * @param array $options
     * @return int
     */
    public function countAll(array $options = []): int
    {
        $conditions = ['is_active = 1'];
        $bind = [];

        if (!empty($options['category'])) {
            $conditions[] = 'category = :category:';
            $bind['category'] = $options['category'];
        }

        if (!empty($options['difficulty_level'])) {
            $conditions[] = 'difficulty_level = :difficulty_level:';
            $bind['difficulty_level'] = $options['difficulty_level'];
        }

        if (isset($options['is_system'])) {
            $conditions[] = 'is_system = :is_system:';
            $bind['is_system'] = $options['is_system'] ? 1 : 0;
        }

        if (!empty($options['keyword'])) {
            $conditions[] = '(name LIKE :keyword: OR description LIKE :keyword: OR tags LIKE :keyword:)';
            $bind['keyword'] = "%{$options['keyword']}%";
        }

        return KnowledgeGraphTemplateModel::count([
            'conditions' => implode(' AND ', $conditions),
            'bind' => $bind
        ]);
    }

    /**
     * 创建模板
     *
     * @param array $data
     * @return KnowledgeGraphTemplateModel|false
     */
    public function create(array $data)
    {
        $template = new KnowledgeGraphTemplateModel();

        $template->name = $data['name'] ?? '';
        $template->category = $data['category'] ?? KnowledgeGraphTemplateModel::CATEGORY_OTHER;
        $template->description = $data['description'] ?? '';
        $template->preview_image = $data['preview_image'] ?? '';
        $template->difficulty_level = $data['difficulty_level'] ?? KnowledgeGraphTemplateModel::DIFFICULTY_BEGINNER;
        $template->is_system = $data['is_system'] ?? false;
        $template->is_active = $data['is_active'] ?? true;
        $template->sort_order = $data['sort_order'] ?? 0;
        $template->created_by = $data['created_by'] ?? 0;

        // 设置节点数据
        if (!empty($data['nodes'])) {
            $template->setNodeDataArray($data['nodes']);
        }

        // 设置关系数据
        if (!empty($data['relations'])) {
            $template->setRelationDataArray($data['relations']);
        }

        // 设置标签
        if (!empty($data['tags'])) {
            if (is_array($data['tags'])) {
                $template->setTagsArray($data['tags']);
            } else {
                $template->tags = $data['tags'];
            }
        }

        if ($template->save()) {
            return $template;
        }

        return false;
    }

    /**
     * 更新模板
     *
     * @param KnowledgeGraphTemplateModel $template
     * @param array $data
     * @return bool
     */
    public function update(KnowledgeGraphTemplateModel $template, array $data): bool
    {
        $fields = ['name', 'category', 'description', 'preview_image', 'difficulty_level', 'is_active', 'sort_order'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $template->$field = $data[$field];
            }
        }

        $template->updated_by = $data['updated_by'] ?? 0;

        // 更新节点数据
        if (array_key_exists('nodes', $data)) {
            $template->setNodeDataArray($data['nodes']);
        }

        // 更新关系数据
        if (array_key_exists('relations', $data)) {
            $template->setRelationDataArray($data['relations']);
        }

        // 更新标签
        if (array_key_exists('tags', $data)) {
            if (is_array($data['tags'])) {
                $template->setTagsArray($data['tags']);
            } else {
                $template->tags = $data['tags'];
            }
        }

        return $template->save();
    }

    /**
     * 删除模板（软删除）
     *
     * @param KnowledgeGraphTemplateModel $template
     * @return bool
     */
    public function delete(KnowledgeGraphTemplateModel $template): bool
    {
        $template->is_active = false;
        return $template->save();
    }

    /**
     * 应用模板到课程
     *
     * @param KnowledgeGraphTemplateModel $template
     * @param int $courseId
     * @param int $userId
     * @return array 返回创建的节点和关系
     */
    public function applyToCourse(KnowledgeGraphTemplateModel $template, int $courseId, int $userId): array
    {
        $nodeData = $template->getNodeDataArray();
        $relationData = $template->getRelationDataArray();

        // 节点ID映射表（模板ID -> 新ID）
        $nodeIdMap = [];
        $createdNodes = [];
        $createdRelations = [];

        // 创建节点
        foreach ($nodeData as $node) {
            $newNode = new KnowledgeNodeModel();
            
            $newNode->name = $node['name'] ?? '';
            $newNode->type = $node['type'] ?? KnowledgeNodeModel::TYPE_CONCEPT;
            $newNode->description = $node['description'] ?? '';
            $newNode->course_id = $courseId;
            $newNode->position_x = $node['position_x'] ?? 0;
            $newNode->position_y = $node['position_y'] ?? 0;
            $newNode->weight = $node['weight'] ?? 1.00;
            $newNode->status = KnowledgeNodeModel::STATUS_DRAFT;
            $newNode->sort_order = $node['sort_order'] ?? 0;
            $newNode->created_by = $userId;

            // 设置扩展属性
            if (!empty($node['properties'])) {
                $newNode->setPropertiesData($node['properties']);
            }

            // 设置样式配置
            if (!empty($node['style_config'])) {
                $newNode->setStyleConfigData($node['style_config']);
            }

            if ($newNode->save()) {
                // 记录ID映射
                $templateNodeId = $node['id'] ?? $node['template_id'] ?? null;
                if ($templateNodeId) {
                    $nodeIdMap[$templateNodeId] = $newNode->id;
                }
                $createdNodes[] = $newNode->toArray();
            }
        }

        // 创建关系
        foreach ($relationData as $relation) {
            $fromNodeId = $relation['from_node_id'] ?? null;
            $toNodeId = $relation['to_node_id'] ?? null;

            // 映射到新的节点ID
            if (!isset($nodeIdMap[$fromNodeId]) || !isset($nodeIdMap[$toNodeId])) {
                continue;
            }

            $newRelation = new KnowledgeRelationModel();
            
            $newRelation->from_node_id = $nodeIdMap[$fromNodeId];
            $newRelation->to_node_id = $nodeIdMap[$toNodeId];
            $newRelation->relation_type = $relation['relation_type'] ?? KnowledgeRelationModel::TYPE_RELATED;
            $newRelation->weight = $relation['weight'] ?? 1.00;
            $newRelation->description = $relation['description'] ?? '';
            $newRelation->status = KnowledgeRelationModel::STATUS_ACTIVE;
            $newRelation->created_by = $userId;

            // 设置扩展属性
            if (!empty($relation['properties'])) {
                $newRelation->setPropertiesData($relation['properties']);
            }

            // 设置样式配置
            if (!empty($relation['style_config'])) {
                $newRelation->setStyleConfigData($relation['style_config']);
            }

            if ($newRelation->save()) {
                $createdRelations[] = $newRelation->toArray();
            }
        }

        // 增加模板使用次数
        $template->incrementUsageCount();

        return [
            'nodes' => $createdNodes,
            'relations' => $createdRelations,
            'node_id_map' => $nodeIdMap
        ];
    }

    /**
     * 获取模板统计信息
     *
     * @return array
     */
    public function getStatistics(): array
    {
        // 总数
        $total = KnowledgeGraphTemplateModel::count('is_active = 1');

        // 按分类统计
        $categoryStats = [];
        $categories = KnowledgeGraphTemplateModel::getCategories();
        foreach ($categories as $category => $label) {
            $count = KnowledgeGraphTemplateModel::count([
                'conditions' => 'is_active = 1 AND category = :category:',
                'bind' => ['category' => $category]
            ]);
            $categoryStats[$category] = ['label' => $label, 'count' => $count];
        }

        // 按难度统计
        $difficultyStats = [];
        $difficulties = KnowledgeGraphTemplateModel::getDifficultyLevels();
        foreach ($difficulties as $level => $label) {
            $count = KnowledgeGraphTemplateModel::count([
                'conditions' => 'is_active = 1 AND difficulty_level = :level:',
                'bind' => ['level' => $level]
            ]);
            $difficultyStats[$level] = ['label' => $label, 'count' => $count];
        }

        // 系统模板数量
        $systemCount = KnowledgeGraphTemplateModel::count('is_active = 1 AND is_system = 1');

        // 最受欢迎的模板（前10）
        $popular = KnowledgeGraphTemplateModel::find([
            'conditions' => 'is_active = 1',
            'order' => 'usage_count DESC',
            'limit' => 10
        ])->toArray();

        return [
            'total' => $total,
            'by_category' => $categoryStats,
            'by_difficulty' => $difficultyStats,
            'system_count' => $systemCount,
            'popular_templates' => $popular
        ];
    }
}


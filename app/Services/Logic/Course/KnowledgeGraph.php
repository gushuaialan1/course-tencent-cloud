<?php
/**
 * 课程知识图谱服务
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Logic\Course;

use App\Repos\KnowledgeNode as KnowledgeNodeRepo;
use App\Repos\KnowledgeRelation as KnowledgeRelationRepo;
use App\Services\Logic\CourseTrait;
use App\Services\Logic\Service as LogicService;

class KnowledgeGraph extends LogicService
{
    use CourseTrait;

    /**
     * 处理课程知识图谱数据
     *
     * @param int $courseId 课程ID
     * @return array Cytoscape.js格式的数据
     */
    public function handle($courseId)
    {
        // 检查课程是否存在
        $course = $this->checkCourse($courseId);

        $user = $this->getCurrentUser();

        // 获取课程的所有知识节点（只查询已发布的节点）
        $nodeRepo = new KnowledgeNodeRepo();
        $nodes = $nodeRepo->findByCourseId($course->id, [
            'status' => \App\Models\KnowledgeNode::STATUS_PUBLISHED
        ]);

        // 获取课程的所有知识关系（边）（只查询激活的关系）
        $relationRepo = new KnowledgeRelationRepo();
        $edges = $relationRepo->findByCourseId($course->id, [
            'status' => \App\Models\KnowledgeRelation::STATUS_ACTIVE
        ]);

        // 调试日志
        error_log("=== 前台知识图谱加载 ===");
        error_log("Course ID: " . $course->id);
        error_log("Nodes raw data: " . json_encode($nodes));
        error_log("Nodes count: " . count($nodes));
        error_log("Edges raw data: " . json_encode($edges));
        error_log("Edges count: " . count($edges));
        
        // 转换为Cytoscape.js格式
        $cytoscapeData = $this->convertToCytoscapeFormat($nodes, $edges);
        
        error_log("Cytoscape data: " . json_encode($cytoscapeData));
        error_log("Cytoscape nodes: " . count($cytoscapeData['nodes']));
        error_log("Cytoscape edges: " . count($cytoscapeData['edges']));

        // 如果用户已登录，可以获取学习进度（可选功能）
        if ($user->id > 0) {
            $cytoscapeData = $this->enrichWithUserProgress($cytoscapeData, $course->id, $user->id);
        }

        return [
            'graph_data' => $cytoscapeData,
            'node_count' => count($nodes),
            'edge_count' => count($edges),
            'course_title' => $course->title,
        ];
    }

    /**
     * 转换为Cytoscape.js格式
     *
     * @param array $nodes 节点数组
     * @param array $edges 边数组
     * @return array
     */
    protected function convertToCytoscapeFormat($nodes, $edges)
    {
        $cytoscapeNodes = [];
        $cytoscapeEdges = [];

        // 转换节点
        foreach ($nodes as $node) {
            $position = [
                'x' => $node['position_x'] ?? 0,
                'y' => $node['position_y'] ?? 0,
            ];
            
            $cytoscapeNodes[] = [
                'data' => [
                    'id' => 'node_' . $node['id'],
                    'label' => $node['name'] ?? '',
                    'type' => $node['type'] ?? 'concept',
                    'description' => $node['description'] ?? '',
                    'weight' => $node['weight'] ?? 1.0,
                ],
                'position' => $position,
                'classes' => $this->getNodeClasses($node),
            ];
        }

        // 转换边（关系）
        foreach ($edges as $edge) {
            $cytoscapeEdges[] = [
                'data' => [
                    'id' => 'edge_' . $edge['id'],
                    'source' => 'node_' . $edge['from_node_id'],
                    'target' => 'node_' . $edge['to_node_id'],
                    'label' => $edge['description'] ?? '',
                    'type' => $edge['relation_type'],
                ],
            ];
        }

        return [
            'nodes' => $cytoscapeNodes,
            'edges' => $cytoscapeEdges,
        ];
    }

    /**
     * 获取节点的CSS类名
     *
     * @param array $node 节点数据
     * @return string
     */
    protected function getNodeClasses($node)
    {
        $classes = [];
        
        // 根据类型添加类名
        if (!empty($node['type'])) {
            $classes[] = 'node-type-' . $node['type'];
        }
        
        // 根据权重添加重要性类名
        if (!empty($node['weight'])) {
            if ($node['weight'] >= 5) {
                $classes[] = 'importance-high';
            } elseif ($node['weight'] >= 3) {
                $classes[] = 'importance-medium';
            } else {
                $classes[] = 'importance-low';
            }
        }

        return implode(' ', $classes);
    }

    /**
     * 补充用户学习进度数据（可选功能）
     *
     * @param array $cytoscapeData Cytoscape数据
     * @param int $courseId 课程ID
     * @param int $userId 用户ID
     * @return array
     */
    protected function enrichWithUserProgress($cytoscapeData, $courseId, $userId)
    {
        // TODO: 未来可以实现学习进度功能
        // 例如：标记已学习的节点、推荐学习路径等
        
        // 现阶段返回原数据
        return $cytoscapeData;
    }
}


<?php
/**
 * 知识图谱API控制器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Api\Controllers;

use App\Http\Api\Controllers\Controller;
use App\Repos\KnowledgeNode as KnowledgeNodeRepo;
use App\Repos\KnowledgeRelation as KnowledgeRelationRepo;
use App\Models\KnowledgeNode;
use App\Models\KnowledgeRelation;
use App\Validators\Validator as AppValidator;

/**
 * @RoutePrefix("/api/knowledge-graph")
 */
class KnowledgeGraphController extends Controller
{
    /**
     * 获取课程知识图谱数据
     * 
     * @Get("/course/{courseId:[0-9]+}", name="api.knowledge_graph.course_graph")
     */
    public function getCourseGraphAction($courseId)
    {
        try {
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            
            // 获取图谱数据
            $graphData = $knowledgeNodeRepo->getCourseGraphData($courseId);
            
            return $this->jsonSuccess([
                'graph' => $graphData,
                'course_id' => $courseId
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '获取图谱数据失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取节点列表
     * 
     * @Get("/nodes", name="api.knowledge_graph.nodes")
     */
    public function getNodesAction()
    {
        try {
            $courseId = $this->request->get('course_id', 'int');
            $type = $this->request->get('type', 'string');
            $status = $this->request->get('status', 'string');
            $keyword = $this->request->get('keyword', 'string');
            $limit = $this->request->get('limit', 'int', 50);
            $offset = $this->request->get('offset', 'int', 0);
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            
            if (!empty($keyword)) {
                // 搜索模式
                $nodes = $knowledgeNodeRepo->searchByName($keyword, [
                    'course_id' => $courseId,
                    'type' => $type,
                    'limit' => $limit
                ]);
            } else {
                // 列表模式
                $options = [
                    'type' => $type,
                    'status' => $status,
                    'limit' => $limit,
                    'offset' => $offset
                ];
                
                if ($courseId) {
                    $nodes = $knowledgeNodeRepo->findByCourseId($courseId, $options);
                } else {
                    return $this->jsonError(['message' => '缺少课程ID参数']);
                }
            }
            
            return $this->jsonSuccess([
                'nodes' => $nodes,
                'total' => count($nodes)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '获取节点列表失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 创建节点
     * 
     * @Post("/nodes", name="api.knowledge_graph.create_node")
     */
    public function createNodeAction()
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            // 验证必填字段
            $validator = new AppValidator();
            $validator->add('name', '节点名称不能为空');
            $validator->add('type', '节点类型不能为空');
            $validator->add('course_id', '课程ID不能为空');
            
            if (!$validator->validate($data)) {
                return $this->jsonError(['message' => $validator->getFirstError()]);
            }
            
            // 添加创建者信息
            $authUser = $this->getAuthUser();
            $data['created_by'] = $authUser ? $authUser->id : 0;
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $node = $knowledgeNodeRepo->createNode($data);
            
            if ($node) {
                return $this->jsonSuccess([
                    'node' => $node->toArray(),
                    'message' => '节点创建成功'
                ]);
            } else {
                return $this->jsonError(['message' => '节点创建失败']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '创建节点失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新节点
     * 
     * @Put("/nodes/{id:[0-9]+}", name="api.knowledge_graph.update_node")
     */
    public function updateNodeAction($id)
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $node = $knowledgeNodeRepo->findById($id);
            
            if (!$node) {
                return $this->jsonError(['message' => '节点不存在']);
            }
            
            // 添加更新者信息
            $authUser = $this->getAuthUser();
            $data['updated_by'] = $authUser ? $authUser->id : 0;
            
            if ($knowledgeNodeRepo->updateNode($node, $data)) {
                return $this->jsonSuccess([
                    'node' => $node->toArray(),
                    'message' => '节点更新成功'
                ]);
            } else {
                return $this->jsonError(['message' => '节点更新失败']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '更新节点失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除节点
     * 
     * @Delete("/nodes/{id:[0-9]+}", name="api.knowledge_graph.delete_node")
     */
    public function deleteNodeAction($id)
    {
        try {
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $node = $knowledgeNodeRepo->findById($id);
            
            if (!$node) {
                return $this->jsonError(['message' => '节点不存在']);
            }
            
            if ($knowledgeNodeRepo->deleteNode($node)) {
                return $this->jsonSuccess(['message' => '节点删除成功']);
            } else {
                return $this->jsonError(['message' => '节点删除失败']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '删除节点失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 批量更新节点位置
     * 
     * @Post("/nodes/positions", name="api.knowledge_graph.update_positions")
     */
    public function updatePositionsAction()
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            $positions = $data['positions'] ?? [];
            
            if (empty($positions)) {
                return $this->jsonError(['message' => '位置数据不能为空']);
            }
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            
            if ($knowledgeNodeRepo->updateNodesPosition($positions)) {
                return $this->jsonSuccess(['message' => '位置更新成功']);
            } else {
                return $this->jsonError(['message' => '位置更新失败']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '更新位置失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 创建关系
     * 
     * @Post("/relations", name="api.knowledge_graph.create_relation")
     */
    public function createRelationAction()
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            // 验证必填字段
            $requiredFields = ['from_node_id', 'to_node_id', 'relation_type'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->jsonError(['message' => "字段 {$field} 不能为空"]);
                }
            }
            
            // 添加创建者信息
            $authUser = $this->getAuthUser();
            $data['created_by'] = $authUser ? $authUser->id : 0;
            
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            
            // 检查循环依赖
            if ($data['relation_type'] === KnowledgeRelation::TYPE_PREREQUISITE) {
                if ($knowledgeRelationRepo->hasCircularDependency(
                    $data['from_node_id'], 
                    $data['to_node_id'], 
                    $data['relation_type']
                )) {
                    return $this->jsonError(['message' => '创建此关系会形成循环依赖']);
                }
            }
            
            $relation = $knowledgeRelationRepo->createRelation($data);
            
            if ($relation) {
                return $this->jsonSuccess([
                    'relation' => $relation->toArray(),
                    'message' => '关系创建成功'
                ]);
            } else {
                return $this->jsonError(['message' => '关系创建失败，可能已存在']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '创建关系失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除关系
     * 
     * @Delete("/relations/{id:[0-9]+}", name="api.knowledge_graph.delete_relation")
     */
    public function deleteRelationAction($id)
    {
        try {
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            $relation = $knowledgeRelationRepo->findById($id);
            
            if (!$relation) {
                return $this->jsonError(['message' => '关系不存在']);
            }
            
            if ($knowledgeRelationRepo->deleteRelation($relation)) {
                return $this->jsonSuccess(['message' => '关系删除成功']);
            } else {
                return $this->jsonError(['message' => '关系删除失败']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '删除关系失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取学习路径
     * 
     * @Get("/learning-path/{startId:[0-9]+}/{endId:[0-9]+}", name="api.knowledge_graph.learning_path")
     */
    public function getLearningPathAction($startId, $endId)
    {
        try {
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $path = $knowledgeNodeRepo->findLearningPath($startId, $endId);
            
            // 获取路径节点详细信息
            $pathNodes = [];
            foreach ($path as $nodeId) {
                $node = $knowledgeNodeRepo->findById($nodeId);
                if ($node) {
                    $pathNodes[] = $node->toArray();
                }
            }
            
            return $this->jsonSuccess([
                'path' => $pathNodes,
                'path_ids' => $path,
                'length' => count($path)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '获取学习路径失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取节点依赖链
     * 
     * @Get("/dependencies/{nodeId:[0-9]+}", name="api.knowledge_graph.dependencies")
     */
    public function getDependenciesAction($nodeId)
    {
        try {
            $direction = $this->request->get('direction', 'string', 'prerequisite');
            $maxDepth = $this->request->get('max_depth', 'int', 5);
            
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            $chain = $knowledgeRelationRepo->getDependencyChain($nodeId, $direction, $maxDepth);
            
            return $this->jsonSuccess([
                'dependencies' => $chain,
                'node_id' => $nodeId,
                'direction' => $direction
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '获取依赖链失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取统计信息
     * 
     * @Get("/statistics", name="api.knowledge_graph.statistics")
     */
    public function getStatisticsAction()
    {
        try {
            $courseId = $this->request->get('course_id', 'int');
            
            if (!$courseId) {
                return $this->jsonError(['message' => '缺少课程ID参数']);
            }
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            
            $nodeStats = $knowledgeNodeRepo->getNodeStatistics($courseId);
            $relationStats = $knowledgeRelationRepo->getRelationStatistics(['course_id' => $courseId]);
            
            return $this->jsonSuccess([
                'nodes' => $nodeStats,
                'relations' => $relationStats,
                'course_id' => $courseId
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '获取统计信息失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 导出图谱数据
     * 
     * @Get("/export/{courseId:[0-9]+}", name="api.knowledge_graph.export")
     */
    public function exportGraphAction($courseId)
    {
        try {
            $format = $this->request->get('format', 'string', 'json');
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $graphData = $knowledgeNodeRepo->getCourseGraphData($courseId);
            
            // 添加导出元数据
            $exportData = [
                'version' => '1.0',
                'export_time' => date('Y-m-d H:i:s'),
                'course_id' => $courseId,
                'format' => $format,
                'data' => $graphData
            ];
            
            switch ($format) {
                case 'cytoscape':
                    // Cytoscape.js 格式
                    return $this->jsonSuccess($graphData);
                    
                case 'graphml':
                    // GraphML 格式（简化版）
                    $xml = $this->convertToGraphML($graphData);
                    $this->response->setContentType('application/xml');
                    return $xml;
                    
                default:
                    // JSON 格式
                    return $this->jsonSuccess($exportData);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '导出失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 导入图谱数据
     * 
     * @Post("/import/{courseId:[0-9]+}", name="api.knowledge_graph.import")
     */
    public function importGraphAction($courseId)
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            if (empty($data['elements'])) {
                return $this->jsonError(['message' => '导入数据格式错误']);
            }
            
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser->id : 0;
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            
            $nodeMapping = [];
            $importStats = ['nodes' => 0, 'relations' => 0, 'errors' => []];
            
            // 导入节点
            foreach ($data['elements'] as $element) {
                if ($element['group'] === 'nodes') {
                    try {
                        $nodeData = [
                            'name' => $element['data']['label'] ?? '',
                            'type' => $element['data']['type'] ?? KnowledgeNode::TYPE_CONCEPT,
                            'description' => $element['data']['description'] ?? '',
                            'course_id' => $courseId,
                            'position_x' => $element['position']['x'] ?? 0,
                            'position_y' => $element['position']['y'] ?? 0,
                            'properties' => $element['data']['properties'] ?? [],
                            'style_config' => $element['style'] ?? [],
                            'created_by' => $userId
                        ];
                        
                        $node = $knowledgeNodeRepo->createNode($nodeData);
                        if ($node) {
                            $nodeMapping[$element['data']['id']] = $node->id;
                            $importStats['nodes']++;
                        }
                    } catch (\Exception $e) {
                        $importStats['errors'][] = "节点导入失败: " . $e->getMessage();
                    }
                }
            }
            
            // 导入关系
            foreach ($data['elements'] as $element) {
                if ($element['group'] === 'edges') {
                    try {
                        $sourceId = $nodeMapping[$element['data']['source']] ?? null;
                        $targetId = $nodeMapping[$element['data']['target']] ?? null;
                        
                        if ($sourceId && $targetId) {
                            $relationData = [
                                'from_node_id' => $sourceId,
                                'to_node_id' => $targetId,
                                'relation_type' => $element['data']['type'] ?? KnowledgeRelation::TYPE_RELATED,
                                'weight' => $element['data']['weight'] ?? 1.0,
                                'description' => $element['data']['description'] ?? '',
                                'properties' => $element['data']['properties'] ?? [],
                                'style_config' => $element['style'] ?? [],
                                'created_by' => $userId
                            ];
                            
                            $relation = $knowledgeRelationRepo->createRelation($relationData);
                            if ($relation) {
                                $importStats['relations']++;
                            }
                        }
                    } catch (\Exception $e) {
                        $importStats['errors'][] = "关系导入失败: " . $e->getMessage();
                    }
                }
            }
            
            return $this->jsonSuccess([
                'message' => '导入完成',
                'statistics' => $importStats
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '导入失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 转换为GraphML格式（简化版）
     * 
     * @param array $graphData
     * @return string
     */
    private function convertToGraphML(array $graphData): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<graphml xmlns="http://graphml.graphdrawing.org/xmlns">' . "\n";
        $xml .= '<graph id="knowledge_graph" edgedefault="directed">' . "\n";
        
        // 节点
        foreach ($graphData['elements'] as $element) {
            if ($element['group'] === 'nodes') {
                $xml .= sprintf(
                    '<node id="%s"><data key="label">%s</data><data key="type">%s</data></node>' . "\n",
                    htmlspecialchars($element['data']['id']),
                    htmlspecialchars($element['data']['label']),
                    htmlspecialchars($element['data']['type'])
                );
            }
        }
        
        // 边
        foreach ($graphData['elements'] as $element) {
            if ($element['group'] === 'edges') {
                $xml .= sprintf(
                    '<edge source="%s" target="%s"><data key="type">%s</data></edge>' . "\n",
                    htmlspecialchars($element['data']['source']),
                    htmlspecialchars($element['data']['target']),
                    htmlspecialchars($element['data']['type'])
                );
            }
        }
        
        $xml .= '</graph>' . "\n";
        $xml .= '</graphml>';
        
        return $xml;
    }
}

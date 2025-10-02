<?php
/**
 * 知识图谱管理后台控制器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Http\Admin\Controllers;

use App\Http\Admin\Controllers\Controller;
use App\Repos\KnowledgeNode as KnowledgeNodeRepo;
use App\Repos\KnowledgeRelation as KnowledgeRelationRepo;
use App\Repos\Course as CourseRepo;
use App\Repos\KnowledgeGraphTemplate as KnowledgeGraphTemplateRepo;

/**
 * @RoutePrefix("/admin/knowledge-graph")
 */
class KnowledgeGraphController extends Controller
{
    /**
     * 知识图谱列表页
     * 
     * @Get("/list", name="admin.knowledge_graph.list")
     */
    public function listAction()
    {
        try {
            $courseRepo = new CourseRepo();
            $nodeRepo = new KnowledgeNodeRepo();
            
            // 获取课程列表
            $courses = $courseRepo->findAll(['published' => 1, 'deleted' => 0]);
            
            // 为每个课程添加节点统计
            $coursesWithStats = [];
            foreach ($courses as $course) {
                $courseArray = is_object($course) ? $course->toArray() : $course;
                $stats = $nodeRepo->getNodeStatistics($courseArray['id']);
                $courseArray['node_count'] = $stats['total'];
                $courseArray['node_stats'] = $stats;
                $coursesWithStats[] = $courseArray;
            }
            
            $this->view->setVar('courses', $coursesWithStats);
            
            return $this->view->pick('knowledge-graph/list');
            
        } catch (\Exception $e) {
            $this->flashSession->error('获取数据失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/index/index');
        }
    }

    /**
     * 知识图谱编辑器页面
     * 
     * @Get("/editor/{courseId:[0-9]+}", name="admin.knowledge_graph.editor")
     */
    public function editorAction($courseId)
    {
        try {
            $courseRepo = new CourseRepo();
            $course = $courseRepo->findById($courseId);
            
            if (!$course) {
                $this->flashSession->error('课程不存在');
                return $this->response->redirect('/admin/knowledge-graph/list');
            }
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $statistics = $knowledgeNodeRepo->getNodeStatistics($courseId);
            
            // 获取节点类型和状态选项
            $nodeTypes = \App\Models\KnowledgeNode::getTypes();
            $nodeStatuses = \App\Models\KnowledgeNode::getStatuses();
            $relationTypes = \App\Models\KnowledgeRelation::getTypes();
            
            $this->view->setVars([
                'course' => $course,
                'statistics' => $statistics,
                'course_id' => $courseId,
                'node_types' => $nodeTypes,
                'node_statuses' => $nodeStatuses,
                'relation_types' => $relationTypes
            ]);
            
            return $this->view->pick('knowledge-graph/editor');
            
        } catch (\Exception $e) {
            $this->flashSession->error('加载编辑器失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/knowledge-graph/list');
        }
    }

    /**
     * 节点管理页面
     * 
     * @Get("/nodes/{courseId:[0-9]+}", name="admin.knowledge_graph.nodes")
     */
    public function nodesAction($courseId)
    {
        try {
            $courseRepo = new CourseRepo();
            $course = $courseRepo->findById($courseId);
            
            if (!$course) {
                $this->flashSession->error('课程不存在');
                return $this->response->redirect('/admin/knowledge-graph/list');
            }
            
            $page = max(1, $this->request->get('page', 'int', 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            // 获取筛选条件
            $type = $this->request->get('type', 'string');
            $status = $this->request->get('status', 'string');
            $keyword = $this->request->get('keyword', 'string');
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            
            // 构建查询选项
            $options = [
                'limit' => $limit,
                'offset' => $offset
            ];
            
            if ($type) {
                $options['type'] = $type;
            }
            if ($status) {
                $options['status'] = $status;
            }
            
            // 根据是否有关键词选择不同的查询方法
            if ($keyword) {
                $nodes = $knowledgeNodeRepo->searchByName($keyword, array_merge($options, ['course_id' => $courseId]));
                $allNodes = $knowledgeNodeRepo->searchByName($keyword, ['course_id' => $courseId, 'limit' => 10000]);
            } else {
                $nodes = $knowledgeNodeRepo->findByCourseId($courseId, $options);
                $allNodes = $knowledgeNodeRepo->findByCourseId($courseId);
            }
            
            $total = count($allNodes);
            
            $this->view->setVars([
                'course' => $course,
                'nodes' => $nodes,
                'page' => $page,
                'total' => $total,
                'limit' => $limit,
                'type' => $type,
                'status' => $status,
                'keyword' => $keyword,
                'node_types' => \App\Models\KnowledgeNode::getTypes(),
                'node_statuses' => \App\Models\KnowledgeNode::getStatuses()
            ]);
            
            return $this->view->pick('knowledge-graph/nodes');
            
        } catch (\Exception $e) {
            $this->flashSession->error('获取节点列表失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/knowledge-graph/list');
        }
    }

    /**
     * 节点创建/编辑页面
     * 
     * @Get("/node/create/{courseId:[0-9]+}", name="admin.knowledge_graph.node_create")
     * @Get("/node/edit/{id:[0-9]+}", name="admin.knowledge_graph.node_edit")
     */
    public function nodeFormAction($courseId = null, $id = null)
    {
        $isEdit = !empty($id);
        $node = null;
        $course = null;
        
        if ($isEdit) {
            // 编辑模式
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $node = $knowledgeNodeRepo->findById($id);
            
            if (!$node) {
                return $this->dispatcher->forward([
                    'controller' => 'error',
                    'action' => 'show404'
                ]);
            }
            
            $courseRepo = new CourseRepo();
            $course = $courseRepo->findById($node->course_id);
        } else {
            // 创建模式
            $courseRepo = new CourseRepo();
            $course = $courseRepo->findById($courseId);
            
            if (!$course) {
                return $this->dispatcher->forward([
                    'controller' => 'error',
                    'action' => 'show404'
                ]);
            }
        }
        
        $this->view->setVar('node', $node);
        $this->view->setVar('course', $course);
        $this->view->setVar('is_edit', $isEdit);
        
        return $this->view->pick('knowledge-graph/node-form');
    }

    /**
     * 图谱数据API - 供前端图谱编辑器使用
     * 
     * @Get("/data/{courseId:[0-9]+}", name="admin.knowledge_graph.data")
     */
    public function getGraphDataAction($courseId)
    {
        try {
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $graphData = $knowledgeNodeRepo->getCourseGraphData($courseId);
            
            return $this->jsonSuccess($graphData);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '获取图谱数据失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 保存图谱数据（批量保存节点位置）
     * 
     * @Post("/save/{courseId:[0-9]+}", name="admin.knowledge_graph.save")
     */
    public function saveGraphAction($courseId)
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            if (empty($data['positions'])) {
                return $this->jsonError(['message' => '没有需要保存的位置数据']);
            }
            
            // 批量更新节点位置
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $knowledgeNodeRepo->updateNodesPosition($data['positions']);
            
            return $this->jsonSuccess(['message' => '保存成功']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '保存失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建节点
     * 
     * @Post("/node/create", name="admin.knowledge_graph.create_node")
     */
    public function createNodeAction()
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            // 验证必填字段
            if (empty($data['name']) || empty($data['course_id'])) {
                return $this->jsonError(['message' => '节点名称和课程ID不能为空']);
            }
            
            // 添加创建者信息
            $data['created_by'] = $this->authUser->id;
            
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $node = $knowledgeNodeRepo->createNode($data);
            
            if ($node) {
                return $this->jsonSuccess([
                    'message' => '节点创建成功',
                    'node' => $node->toArray()
                ]);
            }
            
            return $this->jsonError(['message' => '节点创建失败']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '创建失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新节点
     * 
     * @Post("/node/{id:[0-9]+}/update", name="admin.knowledge_graph.update_node")
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
            $data['updated_by'] = $this->authUser->id;
            
            $success = $knowledgeNodeRepo->updateNode($node, $data);
            
            if ($success) {
                return $this->jsonSuccess([
                    'message' => '节点更新成功',
                    'node' => $node->toArray()
                ]);
            }
            
            return $this->jsonError(['message' => '节点更新失败']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '更新失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除节点
     * 
     * @Post("/node/{id:[0-9]+}/delete", name="admin.knowledge_graph.delete_node")
     */
    public function deleteNodeAction($id)
    {
        try {
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $node = $knowledgeNodeRepo->findById($id);
            
            if (!$node) {
                return $this->jsonError(['message' => '节点不存在']);
            }
            
            $success = $knowledgeNodeRepo->deleteNode($node);
            
            if ($success) {
                return $this->jsonSuccess(['message' => '节点删除成功']);
            }
            
            return $this->jsonError(['message' => '节点删除失败']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '删除失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 创建关系
     * 
     * @Post("/relation/create", name="admin.knowledge_graph.create_relation")
     */
    public function createRelationAction()
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            // 验证必填字段
            if (empty($data['from_node_id']) || empty($data['to_node_id']) || empty($data['relation_type'])) {
                return $this->jsonError(['message' => '起始节点、目标节点和关系类型不能为空']);
            }
            
            // 添加创建者信息
            $data['created_by'] = $this->authUser->id;
            
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            $relation = $knowledgeRelationRepo->createRelation($data);
            
            if ($relation) {
                return $this->jsonSuccess([
                    'message' => '关系创建成功',
                    'relation' => $relation->toArray()
                ]);
            }
            
            return $this->jsonError(['message' => '关系创建失败，可能已存在或节点无效']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '创建失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 更新关系
     * 
     * @Post("/relation/{id:[0-9]+}/update", name="admin.knowledge_graph.update_relation")
     */
    public function updateRelationAction($id)
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            $relation = $knowledgeRelationRepo->findById($id);
            
            if (!$relation) {
                return $this->jsonError(['message' => '关系不存在']);
            }
            
            // 添加更新者信息
            $data['updated_by'] = $this->authUser->id;
            
            $success = $knowledgeRelationRepo->updateRelation($relation, $data);
            
            if ($success) {
                return $this->jsonSuccess([
                    'message' => '关系更新成功',
                    'relation' => $relation->toArray()
                ]);
            }
            
            return $this->jsonError(['message' => '关系更新失败']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '更新失败: ' . $e->getMessage()]);
        }
    }
    
    /**
     * 删除关系
     * 
     * @Post("/relation/{id:[0-9]+}/delete", name="admin.knowledge_graph.delete_relation")
     */
    public function deleteRelationAction($id)
    {
        try {
            $knowledgeRelationRepo = new KnowledgeRelationRepo();
            $relation = $knowledgeRelationRepo->findById($id);
            
            if (!$relation) {
                return $this->jsonError(['message' => '关系不存在']);
            }
            
            $success = $knowledgeRelationRepo->deleteRelation($relation);
            
            if ($success) {
                return $this->jsonSuccess(['message' => '关系删除成功']);
            }
            
            return $this->jsonError(['message' => '关系删除失败']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '删除失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 图谱分析报告
     * 
     * @Get("/analysis/{courseId:[0-9]+}", name="admin.knowledge_graph.analysis")
     */
    public function analysisAction($courseId)
    {
        $courseRepo = new CourseRepo();
        $course = $courseRepo->findById($courseId);
        
        if (!$course) {
            return $this->dispatcher->forward([
                'controller' => 'error',
                'action' => 'show404'
            ]);
        }
        
        $knowledgeNodeRepo = new KnowledgeNodeRepo();
        $knowledgeRelationRepo = new KnowledgeRelationRepo();
        
        // 获取统计数据
        $nodeStats = $knowledgeNodeRepo->getNodeStatistics($courseId);
        $relationStats = $knowledgeRelationRepo->getRelationStatistics(['course_id' => $courseId]);
        
        // 分析图谱复杂度
        $complexity = $this->analyzeGraphComplexity($nodeStats, $relationStats);
        
        // 检测孤立节点
        $isolatedNodes = $this->findIsolatedNodes($courseId);
        
        // 检测循环依赖
        $circularDependencies = $this->findCircularDependencies($courseId);
        
        $this->view->setVar('course', $course);
        $this->view->setVar('node_stats', $nodeStats);
        $this->view->setVar('relation_stats', $relationStats);
        $this->view->setVar('complexity', $complexity);
        $this->view->setVar('isolated_nodes', $isolatedNodes);
        $this->view->setVar('circular_dependencies', $circularDependencies);
        
        return $this->view->pick('knowledge-graph/analysis');
    }

    /**
     * 导出图谱
     * 
     * @Get("/export/{courseId:[0-9]+}/{format}", name="admin.knowledge_graph.export")
     */
    public function exportAction($courseId, $format = 'json')
    {
        try {
            $knowledgeNodeRepo = new KnowledgeNodeRepo();
            $graphData = $knowledgeNodeRepo->getCourseGraphData($courseId);
            
            $courseRepo = new CourseRepo();
            $course = $courseRepo->findById($courseId);
            
            $filename = sprintf('knowledge_graph_%s_%s.%s', 
                $course ? $course->title : 'course', 
                date('Y-m-d'), 
                $format
            );
            
            switch ($format) {
                case 'json':
                    $this->response->setContentType('application/json');
                    $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
                    return json_encode($graphData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    
                case 'csv':
                    return $this->exportToCSV($graphData, $filename);
                    
                default:
                    return $this->jsonError(['message' => '不支持的导出格式']);
            }
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '导出失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 分析图谱复杂度
     * 
     * @param array $nodeStats
     * @param array $relationStats
     * @return array
     */
    private function analyzeGraphComplexity(array $nodeStats, array $relationStats): array
    {
        $totalNodes = $nodeStats['total'];
        $totalRelations = $relationStats['total'];
        
        // 计算密度 (edges / (nodes * (nodes-1)))
        $density = $totalNodes > 1 ? $totalRelations / ($totalNodes * ($totalNodes - 1)) : 0;
        
        // 平均度数
        $avgDegree = $totalNodes > 0 ? ($totalRelations * 2) / $totalNodes : 0;
        
        // 复杂度评级
        $complexityLevel = 'low';
        if ($density > 0.3 || $avgDegree > 4) {
            $complexityLevel = 'high';
        } elseif ($density > 0.1 || $avgDegree > 2) {
            $complexityLevel = 'medium';
        }
        
        return [
            'density' => round($density, 4),
            'avg_degree' => round($avgDegree, 2),
            'level' => $complexityLevel,
            'total_nodes' => $totalNodes,
            'total_relations' => $totalRelations
        ];
    }

    /**
     * 查找孤立节点
     * 
     * @param int $courseId
     * @return array
     */
    private function findIsolatedNodes(int $courseId): array
    {
        // 简化实现，实际应该通过复杂查询找出没有任何关系的节点
        return [];
    }

    /**
     * 查找循环依赖
     * 
     * @param int $courseId
     * @return array
     */
    private function findCircularDependencies(int $courseId): array
    {
        // 简化实现，实际应该通过图算法检测环路
        return [];
    }

    /**
     * 导出为CSV格式
     * 
     * @param array $graphData
     * @param string $filename
     * @return mixed
     */
    private function exportToCSV(array $graphData, string $filename)
    {
        $csv = "Type,ID,Name,Description,Type,Source,Target,Relation\n";
        
        // 导出节点
        foreach ($graphData['elements'] as $element) {
            if ($element['group'] === 'nodes') {
                $csv .= sprintf("Node,%s,%s,%s,%s,,,\n",
                    $element['data']['id'],
                    addslashes($element['data']['label']),
                    addslashes($element['data']['description'] ?? ''),
                    $element['data']['type']
                );
            }
        }
        
        // 导出关系
        foreach ($graphData['elements'] as $element) {
            if ($element['group'] === 'edges') {
                $csv .= sprintf("Edge,%s,,,,%s,%s,%s\n",
                    $element['data']['id'],
                    $element['data']['source'],
                    $element['data']['target'],
                    $element['data']['type']
                );
            }
        }
        
        $this->response->setContentType('text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $csv;
    }

    /**
     * 获取模板列表
     * 
     * @Get("/templates", name="admin.knowledge_graph.templates")
     */
    public function templatesAction()
    {
        try {
            $page = max(1, $this->request->getQuery('page', 'int', 1));
            $limit = min(100, max(10, $this->request->getQuery('limit', 'int', 15)));
            $category = $this->request->getQuery('category', 'string');
            $difficulty = $this->request->getQuery('difficulty_level', 'string');
            $keyword = $this->request->getQuery('keyword', 'string');

            $templateRepo = new KnowledgeGraphTemplateRepo();
            
            $options = [
                'limit' => $limit,
                'offset' => ($page - 1) * $limit
            ];
            
            if ($category) {
                $options['category'] = $category;
            }
            if ($difficulty) {
                $options['difficulty_level'] = $difficulty;
            }
            if ($keyword) {
                $options['keyword'] = $keyword;
            }
            
            $templates = $templateRepo->findAll($options);
            $total = $templateRepo->countAll(array_diff_key($options, ['limit' => '', 'offset' => '']));
            
            // 获取分类和难度级别选项
            $categories = \App\Models\KnowledgeGraphTemplate::getCategories();
            $difficultyLevels = \App\Models\KnowledgeGraphTemplate::getDifficultyLevels();
            
            // 获取统计信息
            $statistics = $templateRepo->getStatistics();
            
            $this->view->setVars([
                'templates' => $templates,
                'categories' => $categories,
                'difficulty_levels' => $difficultyLevels,
                'statistics' => $statistics,
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'category' => $category,
                'difficulty' => $difficulty,
                'keyword' => $keyword
            ]);
            
            return $this->view->pick('knowledge-graph/templates');
            
        } catch (\Exception $e) {
            $this->flashSession->error('获取模板列表失败: ' . $e->getMessage());
            return $this->response->redirect('/admin/knowledge-graph/list');
        }
    }

    /**
     * 获取模板详情（API）
     * 
     * @Get("/template/{id:[0-9]+}", name="admin.knowledge_graph.template_detail")
     */
    public function templateDetailAction($id)
    {
        try {
            $templateRepo = new KnowledgeGraphTemplateRepo();
            $template = $templateRepo->findById($id);
            
            if (!$template) {
                return $this->jsonError(['msg' => '模板不存在']);
            }
            
            // 转换为数组并解析JSON数据
            $templateData = $template->toArray();
            $templateData['nodes'] = $template->getNodeDataArray();
            $templateData['relations'] = $template->getRelationDataArray();
            $templateData['tags_array'] = $template->getTagsArray();
            
            return $this->jsonSuccess([
                'data' => $templateData
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Get template detail error: ' . $e->getMessage());
            return $this->jsonError(['msg' => '获取模板详情失败']);
        }
    }

    /**
     * 应用模板到课程（API）
     * 
     * @Post("/apply-template/{courseId:[0-9]+}", name="admin.knowledge_graph.apply_template")
     */
    public function applyTemplateAction($courseId)
    {
        try {
            $templateId = $this->request->getPost('template_id', 'int');
            
            if (!$templateId) {
                return $this->jsonError(['msg' => '请选择模板']);
            }
            
            // 验证课程存在
            $courseRepo = new CourseRepo();
            $course = $courseRepo->findById($courseId);
            if (!$course) {
                return $this->jsonError(['msg' => '课程不存在']);
            }
            
            // 验证模板存在
            $templateRepo = new KnowledgeGraphTemplateRepo();
            $template = $templateRepo->findById($templateId);
            if (!$template) {
                return $this->jsonError(['msg' => '模板不存在']);
            }
            
            // 获取当前用户
            $userId = $this->getAuthUser()['id'] ?? 0;
            
            // 应用模板到课程
            $result = $templateRepo->applyToCourse($template, $courseId, $userId);
            
            return $this->jsonSuccess([
                'msg' => '应用模板成功',
                'data' => [
                    'nodes_created' => count($result['nodes']),
                    'relations_created' => count($result['relations']),
                    'nodes' => $result['nodes'],
                    'relations' => $result['relations']
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Apply template error: ' . $e->getMessage());
            return $this->jsonError(['msg' => '应用模板失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 创建模板（API）
     * 
     * @Post("/template/create", name="admin.knowledge_graph.template_create")
     */
    public function createTemplateAction()
    {
        try {
            $name = $this->request->getPost('name', 'string');
            $category = $this->request->getPost('category', 'string');
            $description = $this->request->getPost('description', 'string');
            $difficultyLevel = $this->request->getPost('difficulty_level', 'string');
            $tags = $this->request->getPost('tags', 'string');
            $nodes = $this->request->getPost('nodes');
            $relations = $this->request->getPost('relations');
            
            // 验证必填字段
            if (empty($name)) {
                return $this->jsonError(['msg' => '模板名称不能为空']);
            }
            
            if (empty($nodes)) {
                return $this->jsonError(['msg' => '节点数据不能为空']);
            }
            
            // 解析JSON数据
            if (is_string($nodes)) {
                $nodes = json_decode($nodes, true);
            }
            if (is_string($relations)) {
                $relations = json_decode($relations, true);
            }
            
            $userId = $this->getAuthUser()['id'] ?? 0;
            
            $templateRepo = new KnowledgeGraphTemplateRepo();
            $template = $templateRepo->create([
                'name' => $name,
                'category' => $category ?: \App\Models\KnowledgeGraphTemplate::CATEGORY_OTHER,
                'description' => $description,
                'difficulty_level' => $difficultyLevel ?: \App\Models\KnowledgeGraphTemplate::DIFFICULTY_BEGINNER,
                'tags' => $tags,
                'nodes' => $nodes,
                'relations' => $relations ?: [],
                'is_system' => false,
                'created_by' => $userId
            ]);
            
            if ($template) {
                return $this->jsonSuccess([
                    'msg' => '创建模板成功',
                    'data' => $template->toArray()
                ]);
            }
            
            return $this->jsonError(['msg' => '创建模板失败']);
            
        } catch (\Exception $e) {
            $this->logger->error('Create template error: ' . $e->getMessage());
            return $this->jsonError(['msg' => '创建模板失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新模板（API）
     * 
     * @Post("/template/{id:[0-9]+}/update", name="admin.knowledge_graph.template_update")
     */
    public function updateTemplateAction($id)
    {
        try {
            $templateRepo = new KnowledgeGraphTemplateRepo();
            $template = $templateRepo->findById($id);
            
            if (!$template) {
                return $this->jsonError(['msg' => '模板不存在']);
            }
            
            // 只有创建者可以编辑
            $userId = $this->getAuthUser()['id'] ?? 0;
            if ($template->created_by != $userId && !$template->is_system) {
                return $this->jsonError(['msg' => '无权编辑此模板']);
            }
            
            $data = [];
            $fields = ['name', 'category', 'description', 'difficulty_level', 'tags'];
            foreach ($fields as $field) {
                $value = $this->request->getPost($field);
                if ($value !== null) {
                    $data[$field] = $value;
                }
            }
            
            // 更新节点和关系数据
            $nodes = $this->request->getPost('nodes');
            if ($nodes !== null) {
                $data['nodes'] = is_string($nodes) ? json_decode($nodes, true) : $nodes;
            }
            
            $relations = $this->request->getPost('relations');
            if ($relations !== null) {
                $data['relations'] = is_string($relations) ? json_decode($relations, true) : $relations;
            }
            
            $data['updated_by'] = $userId;
            
            if ($templateRepo->update($template, $data)) {
                return $this->jsonSuccess(['msg' => '更新模板成功']);
            }
            
            return $this->jsonError(['msg' => '更新模板失败']);
            
        } catch (\Exception $e) {
            $this->logger->error('Update template error: ' . $e->getMessage());
            return $this->jsonError(['msg' => '更新模板失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 删除模板（API）
     * 
     * @Post("/template/{id:[0-9]+}/delete", name="admin.knowledge_graph.template_delete")
     */
    public function deleteTemplateAction($id)
    {
        try {
            $templateRepo = new KnowledgeGraphTemplateRepo();
            $template = $templateRepo->findById($id);
            
            if (!$template) {
                return $this->jsonError(['msg' => '模板不存在']);
            }
            
            // 系统模板不能删除
            if ($template->is_system) {
                return $this->jsonError(['msg' => '系统模板不能删除']);
            }
            
            // 只有创建者可以删除
            $userId = $this->getAuthUser()['id'] ?? 0;
            if ($template->created_by != $userId) {
                return $this->jsonError(['msg' => '无权删除此模板']);
            }
            
            if ($templateRepo->delete($template)) {
                return $this->jsonSuccess(['msg' => '删除模板成功']);
            }
            
            return $this->jsonError(['msg' => '删除模板失败']);
            
        } catch (\Exception $e) {
            $this->logger->error('Delete template error: ' . $e->getMessage());
            return $this->jsonError(['msg' => '删除模板失败: ' . $e->getMessage()]);
        }
    }
}

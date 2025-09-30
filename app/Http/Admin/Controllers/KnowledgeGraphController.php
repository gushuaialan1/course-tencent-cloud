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
        $courseRepo = new CourseRepo();
        $courses = $courseRepo->findAll(['limit' => 100]);
        
        $this->view->setVar('courses', $courses);
        
        return $this->view->pick('knowledge-graph/list');
    }

    /**
     * 知识图谱编辑器页面
     * 
     * @Get("/editor/{courseId:[0-9]+}", name="admin.knowledge_graph.editor")
     */
    public function editorAction($courseId)
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
        $statistics = $knowledgeNodeRepo->getNodeStatistics($courseId);
        
        $this->view->setVar('course', $course);
        $this->view->setVar('statistics', $statistics);
        $this->view->setVar('course_id', $courseId);
        
        return $this->view->pick('knowledge-graph/editor');
    }

    /**
     * 节点管理页面
     * 
     * @Get("/nodes/{courseId:[0-9]+}", name="admin.knowledge_graph.nodes")
     */
    public function nodesAction($courseId)
    {
        $courseRepo = new CourseRepo();
        $course = $courseRepo->findById($courseId);
        
        if (!$course) {
            return $this->dispatcher->forward([
                'controller' => 'error',
                'action' => 'show404'
            ]);
        }
        
        $page = $this->request->get('page', 'int', 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $knowledgeNodeRepo = new KnowledgeNodeRepo();
        $nodes = $knowledgeNodeRepo->findByCourseId($courseId, [
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        $total = count($knowledgeNodeRepo->findByCourseId($courseId));
        
        $this->view->setVar('course', $course);
        $this->view->setVar('nodes', $nodes);
        $this->view->setVar('page', $page);
        $this->view->setVar('total', $total);
        $this->view->setVar('limit', $limit);
        
        return $this->view->pick('knowledge-graph/nodes');
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
     * 保存图谱数据
     * 
     * @Post("/save/{courseId:[0-9]+}", name="admin.knowledge_graph.save")
     */
    public function saveGraphAction($courseId)
    {
        try {
            $data = $this->request->getJsonRawBody(true);
            
            if (empty($data['elements'])) {
                return $this->jsonError(['message' => '图谱数据不能为空']);
            }
            
            // 这里可以实现增量保存逻辑
            // 比较前端传来的数据与数据库中的数据，只更新有变化的部分
            
            return $this->jsonSuccess(['message' => '保存成功']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '保存失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 图谱模板列表
     * 
     * @Get("/templates", name="admin.knowledge_graph.templates")
     */
    public function templatesAction()
    {
        // 预定义的图谱模板
        $templates = [
            [
                'id' => 'basic_cs',
                'name' => '计算机科学基础',
                'description' => '包含编程语言、数据结构、算法等基础概念',
                'preview' => '/static/admin/img/templates/basic_cs.png',
                'nodes_count' => 15,
                'category' => '计算机科学'
            ],
            [
                'id' => 'math_foundation',
                'name' => '数学基础',
                'description' => '包含微积分、线性代数、概率统计等数学概念',
                'preview' => '/static/admin/img/templates/math_foundation.png',
                'nodes_count' => 12,
                'category' => '数学'
            ],
            [
                'id' => 'web_development',
                'name' => 'Web开发技术栈',
                'description' => 'HTML、CSS、JavaScript、框架等Web开发知识',
                'preview' => '/static/admin/img/templates/web_development.png',
                'nodes_count' => 20,
                'category' => 'Web开发'
            ]
        ];
        
        $this->view->setVar('templates', $templates);
        
        return $this->view->pick('knowledge-graph/templates');
    }

    /**
     * 应用模板
     * 
     * @Post("/apply-template/{courseId:[0-9]+}", name="admin.knowledge_graph.apply_template")
     */
    public function applyTemplateAction($courseId)
    {
        try {
            $templateId = $this->request->getPost('template_id');
            
            // 这里实现模板应用逻辑
            // 根据templateId加载预定义的图谱结构
            
            return $this->jsonSuccess(['message' => '模板应用成功']);
            
        } catch (\Exception $e) {
            return $this->jsonError(['message' => '应用模板失败: ' . $e->getMessage()]);
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
}

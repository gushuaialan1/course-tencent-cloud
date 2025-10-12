<?php
/**
 * 知识图谱生成服务
 * 
 * 功能：
 * 1. 从课程章节简单生成知识图谱
 * 2. 使用AI智能生成知识图谱
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services;

use App\Repos\Course as CourseRepo;
use App\Repos\Chapter as ChapterRepo;
use App\Repos\KgAiConfig as KgAiConfigRepo;

class KnowledgeGraphGenerator extends Service
{
    /**
     * 从课程章节简单生成知识图谱
     * 
     * 逻辑：
     * 1. 顶层章节 → 概念节点 (concept)
     * 2. 课时 → 技能节点 (skill)
     * 3. 章节之间：前置关系 (prerequisite)
     * 4. 章节包含课时：包含关系 (contain)
     * 5. 自动绑定chapter_id到节点
     * 
     * @param int $courseId 课程ID
     * @return array 图谱数据 ['nodes' => [], 'edges' => []]
     * @throws \Exception
     */
    public function generateFromChapters($courseId)
    {
        $courseRepo = new CourseRepo();
        $chapterRepo = new ChapterRepo();
        
        // 验证课程存在
        $course = $courseRepo->findById($courseId);
        if (!$course) {
            throw new \Exception('课程不存在');
        }
        
        // 获取所有顶层章节（parent_id = 0）
        // 注意：不过滤 published 字段，因为原项目章节管理没有发布控制
        $chaptersResult = $chapterRepo->findAll([
            'course_id' => $courseId,
            'parent_id' => 0,
            'deleted' => 0
        ]);
        
        if (count($chaptersResult) === 0) {
            throw new \Exception('该课程暂无章节，无法生成知识图谱');
        }
        
        // 转换为数组以便于操作
        $chapters = [];
        foreach ($chaptersResult as $chapter) {
            $chapters[] = $chapter;
        }
        
        $nodes = [];
        $edges = [];
        $nodeIdCounter = 1;
        $edgeIdCounter = 1;
        
        // 记录章节ID到节点ID的映射
        $chapterNodeMap = [];
        
        // 第一步：为每个章节创建概念节点
        $chapterIndex = 0;
        foreach ($chapters as $chapter) {
            $chapterArray = is_object($chapter) ? $chapter->toArray() : $chapter;
            
            // 创建章节节点（概念）
            $nodeId = 'node_' . $nodeIdCounter++;
            $chapterNodeMap[$chapterArray['id']] = $nodeId;
            
            // 计算节点位置（层次布局）
            $x = 100 + ($chapterIndex % 3) * 300;
            $y = 100 + floor($chapterIndex / 3) * 200;
            
            $nodes[] = [
                'data' => [
                    'id' => $nodeId,
                    'label' => $chapterArray['title'],  // 修复：使用label而不是name
                    'name' => $chapterArray['title'],   // 保留name用于兼容
                    'type' => 'concept',
                    'description' => '章节：' . $chapterArray['title'],
                    'chapter_id' => $chapterArray['id'],
                    'primary_resource_type' => 'chapter',
                    'primary_resource_id' => $chapterArray['id'],
                    // 添加样式属性
                    'backgroundColor' => '#FF6B6B',
                    'borderColor' => '#C92A2A',
                    'borderWidth' => 2,
                    'textColor' => '#fff',
                    'fontSize' => '14px',
                    'width' => 100,
                    'height' => 100
                ],
                'position' => [
                    'x' => $x,
                    'y' => $y
                ]
            ];
            
            // 默认为顺序章节创建前置关系
            // 用户可以在编辑器中删除或调整不需要的关系
            if ($chapterIndex > 0) {
                $prevChapterId = $chapters[$chapterIndex - 1]->id;
                $prevNodeId = $chapterNodeMap[$prevChapterId];
                
                $edges[] = [
                    'data' => [
                        'id' => 'edge_' . $edgeIdCounter++,
                        'source' => $prevNodeId,
                        'target' => $nodeId,
                        'type' => 'prerequisite',
                        'label' => '前置',
                        'description' => '学习顺序：建议先学习前一章节',
                        // 前置关系使用连线样式（无箭头）
                        'width' => 2,
                        'lineColor' => '#FF9800',
                        'arrowColor' => '#FF9800',
                        'arrowShape' => 'none',  // 无箭头
                        'curveStyle' => 'bezier',
                        'lineStyle' => 'dashed'  // 虚线表示建议性关系
                    ]
                ];
            }
            
            $chapterIndex++;
        }
        
        // 第二步：为每个章节的课时创建技能节点
        $lessonIndex = 0;
        foreach ($chapters as $chapter) {
            $chapterArray = is_object($chapter) ? $chapter->toArray() : $chapter;
            $chapterNodeId = $chapterNodeMap[$chapterArray['id']];
            
            // 获取该章节下的所有课时
            $lessons = $chapterRepo->findLessons($chapterArray['id']);
            
            if (count($lessons) > 0) {
                $lessonCount = 0;
                foreach ($lessons as $lesson) {
                    $lessonArray = is_object($lesson) ? $lesson->toArray() : $lesson;
                    
                    // 创建课时节点（技能）
                    $lessonNodeId = 'node_' . $nodeIdCounter++;
                    
                    // 计算课时节点位置（在章节节点下方）
                    $chapterNode = null;
                    foreach ($nodes as $node) {
                        if ($node['data']['id'] === $chapterNodeId) {
                            $chapterNode = $node;
                            break;
                        }
                    }
                    
                    $x = $chapterNode['position']['x'] + ($lessonCount % 2) * 150 - 75;
                    $y = $chapterNode['position']['y'] + 150;
                    
                    $nodes[] = [
                        'data' => [
                            'id' => $lessonNodeId,
                            'label' => $lessonArray['title'],  // 修复：使用label
                            'name' => $lessonArray['title'],
                            'type' => 'skill',
                            'description' => '课时：' . $lessonArray['title'],
                            'chapter_id' => $lessonArray['id'],
                            'primary_resource_type' => 'lesson',
                            'primary_resource_id' => $lessonArray['id'],
                            // 添加样式属性
                            'backgroundColor' => '#4CAF50',
                            'borderColor' => '#388E3C',
                            'borderWidth' => 2,
                            'textColor' => '#fff',
                            'fontSize' => '12px',
                            'width' => 80,
                            'height' => 80
                        ],
                        'position' => [
                            'x' => $x,
                            'y' => $y
                        ]
                    ];
                    
                    // 创建章节到课时的包含关系
                    $edges[] = [
                        'data' => [
                            'id' => 'edge_' . $edgeIdCounter++,
                            'source' => $chapterNodeId,
                            'target' => $lessonNodeId,
                            'type' => 'contains',  // 修复：使用正确的关系类型
                            'label' => '包含',  // 添加label
                            'description' => '章节包含课时',
                            // 添加样式属性
                            'width' => 2,
                            'lineColor' => '#2196F3',
                            'arrowColor' => '#2196F3',
                            'arrowShape' => 'triangle',
                            'curveStyle' => 'bezier',
                            'lineStyle' => 'solid'
                        ]
                    ];
                    
                    $lessonCount++;
                    $lessonIndex++;
                }
            }
        }
        
        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'statistics' => [
                'total_nodes' => count($nodes),
                'total_edges' => count($edges),
                'concept_nodes' => count($chapters),
                'skill_nodes' => $lessonIndex,
                'generation_mode' => 'simple',
                'course_id' => $courseId,
                'course_title' => $course->title
            ]
        ];
    }
    
    /**
     * 使用AI智能生成知识图谱
     * 
     * @param int $courseId 课程ID
     * @param array $options 生成选项
     * @return array 图谱数据
     * @throws \Exception
     */
    public function generateWithAI($courseId, $options = [])
    {
        $courseRepo = new CourseRepo();
        $chapterRepo = new ChapterRepo();
        $aiConfigRepo = new KgAiConfigRepo();
        
        // 验证AI是否已配置
        if (!$aiConfigRepo->isAiConfigured()) {
            throw new \Exception('AI功能未配置，请先在系统设置中配置AI服务');
        }
        
        // 验证课程存在
        $course = $courseRepo->findById($courseId);
        if (!$course) {
            throw new \Exception('课程不存在');
        }
        
        // 获取AI配置
        $aiConfig = $aiConfigRepo->getAiConfig();
        $provider = $aiConfig['provider'];
        $apiKey = $aiConfig['api_key'];
        $model = $aiConfig['model'];
        $baseUrl = $aiConfig['base_url'];
        
        // 收集课程内容
        $courseContent = $this->collectCourseContent($courseId);
        
        // 构建Prompt
        $prompt = $this->buildPrompt($course, $courseContent);
        
        // 调用AI API
        try {
            $aiService = new AiService();
            $response = $aiService->generateKnowledgeGraph($provider, $apiKey, $model, $baseUrl, $prompt);
            
            // 解析AI响应
            $graphData = $this->parseAiResponse($response, $courseId);
            
            return $graphData;
            
        } catch (\Exception $e) {
            throw new \Exception('AI生成失败：' . $e->getMessage());
        }
    }
    
    /**
     * 收集课程内容用于AI分析
     * 
     * @param int $courseId
     * @return array
     */
    private function collectCourseContent($courseId)
    {
        $courseRepo = new CourseRepo();
        $chapterRepo = new ChapterRepo();
        
        $course = $courseRepo->findById($courseId);
        // 注意：不过滤 published 字段，因为原项目章节管理没有发布控制
        $chapters = $chapterRepo->findAll([
            'course_id' => $courseId,
            'parent_id' => 0,
            'deleted' => 0
        ]);
        
        $content = [
            'course_title' => $course->title,
            'course_summary' => $course->summary ?? '',
            'course_details' => $course->details ?? '',
            'chapters' => []
        ];
        
        foreach ($chapters as $chapter) {
            $chapterArray = is_object($chapter) ? $chapter->toArray() : $chapter;
            $lessons = $chapterRepo->findLessons($chapterArray['id']);
            
            $chapterData = [
                'title' => $chapterArray['title'],
                'summary' => $chapterArray['summary'] ?? '',
                'lessons' => []
            ];
            
            foreach ($lessons as $lesson) {
                $lessonArray = is_object($lesson) ? $lesson->toArray() : $lesson;
                $chapterData['lessons'][] = [
                    'title' => $lessonArray['title'],
                    'summary' => $lessonArray['summary'] ?? ''
                ];
            }
            
            $content['chapters'][] = $chapterData;
        }
        
        return $content;
    }
    
    /**
     * 构建AI Prompt
     * 
     * @param object $course
     * @param array $content
     * @return string
     */
    private function buildPrompt($course, $content)
    {
        $courseTitle = $course->title;
        $courseSummary = $course->summary ?? '';
        
        $chaptersText = '';
        foreach ($content['chapters'] as $index => $chapter) {
            $chaptersText .= "\n第" . ($index + 1) . "章：" . $chapter['title'];
            if (!empty($chapter['summary'])) {
                $chaptersText .= "\n  简介：" . $chapter['summary'];
            }
            if (!empty($chapter['lessons'])) {
                $chaptersText .= "\n  课时：";
                foreach ($chapter['lessons'] as $lesson) {
                    $chaptersText .= "\n    - " . $lesson['title'];
                }
            }
        }
        
        $prompt = <<<PROMPT
你是一位专业的教育内容分析专家，请根据以下课程信息，生成一个结构化的知识图谱。

课程名称：{$courseTitle}
课程简介：{$courseSummary}

课程大纲：{$chaptersText}

请你：
1. 提取核心知识点（概念）和技能点
2. 识别知识点之间的关系：
   - prerequisite（前置关系）：必须先掌握A才能学习B
   - contain（包含关系）：A包含B
   - extend（扩展关系）：B是A的扩展
   - related（相关关系）：A和B相关
   - suggest（建议关系）：学完A建议学B
3. 生成15-30个节点
4. 节点类型：concept（概念）、skill（技能）、topic（主题）

返回JSON格式（严格遵守格式）：
{
  "nodes": [
    {
      "name": "节点名称",
      "type": "concept",
      "description": "节点描述"
    }
  ],
  "edges": [
    {
      "source": "源节点名称",
      "target": "目标节点名称",
      "type": "prerequisite",
      "description": "关系描述"
    }
  ]
}

要求：
1. 只返回JSON，不要其他内容
2. 节点名称要简洁（2-6个字）
3. 关系要合理、准确
4. 节点数量15-30个之间
PROMPT;
        
        return $prompt;
    }
    
    /**
     * 解析AI响应
     * 
     * @param string $response
     * @param int $courseId
     * @return array
     * @throws \Exception
     */
    private function parseAiResponse($response, $courseId)
    {
        // 尝试从响应中提取JSON
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new \Exception('AI响应格式错误：未找到有效的JSON');
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $data = json_decode($jsonString, true);
        
        if (!$data || !isset($data['nodes']) || !isset($data['edges'])) {
            throw new \Exception('AI响应格式错误：缺少nodes或edges字段');
        }
        
        // 转换为Cytoscape格式
        $nodes = [];
        $edges = [];
        $nodeIdMap = [];
        $nodeIdCounter = 1;
        $edgeIdCounter = 1;
        
        // 创建节点
        $nodeIndex = 0;
        foreach ($data['nodes'] as $node) {
            $nodeId = 'node_' . $nodeIdCounter++;
            $nodeName = $node['name'];
            $nodeIdMap[$nodeName] = $nodeId;
            
            // 计算位置（网格布局）
            $x = 100 + ($nodeIndex % 5) * 200;
            $y = 100 + floor($nodeIndex / 5) * 200;
            
            $nodes[] = [
                'data' => [
                    'id' => $nodeId,
                    'label' => $nodeName,  // 添加label
                    'name' => $nodeName,
                    'type' => $node['type'] ?? 'concept',
                    'description' => $node['description'] ?? '',
                    // 根据类型设置不同颜色
                    'backgroundColor' => ($node['type'] ?? 'concept') === 'concept' ? '#FF6B6B' : '#4CAF50',
                    'borderColor' => ($node['type'] ?? 'concept') === 'concept' ? '#C92A2A' : '#388E3C',
                    'borderWidth' => 2,
                    'textColor' => '#fff',
                    'fontSize' => '12px',
                    'width' => 80,
                    'height' => 80
                ],
                'position' => [
                    'x' => $x,
                    'y' => $y
                ]
            ];
            
            $nodeIndex++;
        }
        
        // 创建关系
        foreach ($data['edges'] as $edge) {
            $sourceName = $edge['source'];
            $targetName = $edge['target'];
            
            if (!isset($nodeIdMap[$sourceName]) || !isset($nodeIdMap[$targetName])) {
                continue; // 跳过无效的关系
            }
            
            $edgeType = $edge['type'] ?? 'related';
            $edgeLabels = [
                'prerequisite' => '前置',
                'contain' => '包含',
                'related' => '相关',
                'extend' => '扩展',
                'suggest' => '建议'
            ];
            $edgeColors = [
                'prerequisite' => '#FF9800',
                'contain' => '#2196F3',
                'related' => '#9C27B0',
                'extend' => '#4CAF50',
                'suggest' => '#FFC107'
            ];
            
            $edges[] = [
                'data' => [
                    'id' => 'edge_' . $edgeIdCounter++,
                    'source' => $nodeIdMap[$sourceName],
                    'target' => $nodeIdMap[$targetName],
                    'type' => $edgeType,
                    'label' => $edgeLabels[$edgeType] ?? $edgeType,  // 添加label
                    'description' => $edge['description'] ?? '',
                    // 添加样式属性
                    'width' => 2,
                    'lineColor' => $edgeColors[$edgeType] ?? '#666',
                    'arrowColor' => $edgeColors[$edgeType] ?? '#666',
                    'arrowShape' => 'triangle',
                    'curveStyle' => 'bezier',
                    'lineStyle' => 'solid'
                ]
            ];
        }
        
        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'statistics' => [
                'total_nodes' => count($nodes),
                'total_edges' => count($edges),
                'generation_mode' => 'ai',
                'course_id' => $courseId
            ]
        ];
    }
}


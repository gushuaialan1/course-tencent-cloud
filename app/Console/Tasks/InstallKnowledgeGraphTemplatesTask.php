<?php
/**
 * 安装知识图谱预置模板任务
 * 
 * 运行方法: php console.php InstallKnowledgeGraphTemplates main
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Console\Tasks;

use App\Repos\KnowledgeGraphTemplate as KnowledgeGraphTemplateRepo;
use App\Models\KnowledgeGraphTemplate as KnowledgeGraphTemplateModel;

class InstallKnowledgeGraphTemplatesTask extends Task
{
    /**
     * 主任务
     */
    public function mainAction()
    {
        $this->successPrint('开始安装知识图谱预置模板...');
        
        $templateRepo = new KnowledgeGraphTemplateRepo();
        
        // 获取预置模板数据
        $templates = $this->getTemplateData();
        
        $successCount = 0;
        $skipCount = 0;
        
        foreach ($templates as $templateData) {
            // 检查模板是否已存在
            $existing = KnowledgeGraphTemplateModel::findFirst([
                'conditions' => 'name = :name: AND is_system = 1',
                'bind' => ['name' => $templateData['name']]
            ]);
            
            if ($existing) {
                $this->errorPrint("模板 [{$templateData['name']}] 已存在，跳过");
                $skipCount++;
                continue;
            }
            
            // 创建模板
            $template = $templateRepo->create($templateData);
            
            if ($template) {
                $this->successPrint("模板 [{$templateData['name']}] 创建成功 (节点数: {$template->node_count}, 关系数: {$template->relation_count})");
                $successCount++;
            } else {
                $this->errorPrint("模板 [{$templateData['name']}] 创建失败");
            }
        }
        
        $this->successPrint("安装完成！成功: {$successCount}, 跳过: {$skipCount}");
    }
    
    /**
     * 获取预置模板数据
     *
     * @return array
     */
    private function getTemplateData(): array
    {
        return [
            $this->getComputerScienceTemplate(),
            $this->getMathTemplate(),
            $this->getWebDevelopmentTemplate()
        ];
    }
    
    /**
     * 计算机科学基础模板（15个节点）
     *
     * @return array
     */
    private function getComputerScienceTemplate(): array
    {
        $nodes = [
            // 第1层：基础概念
            ['id' => 1, 'name' => '计算思维', 'type' => 'concept', 'description' => '计算机科学的核心思维方式', 'position_x' => 400, 'position_y' => 50, 'weight' => 1.0, 'sort_order' => 1, 'properties' => [], 'style_config' => []],
            
            // 第2层：编程基础
            ['id' => 2, 'name' => '编程语言基础', 'type' => 'topic', 'description' => '程序设计语言的基本概念', 'position_x' => 200, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 2, 'properties' => [], 'style_config' => []],
            ['id' => 3, 'name' => '数据类型', 'type' => 'concept', 'description' => '基本数据类型和数据结构', 'position_x' => 400, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 3, 'properties' => [], 'style_config' => []],
            ['id' => 4, 'name' => '控制结构', 'type' => 'concept', 'description' => '程序的控制流程', 'position_x' => 600, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 4, 'properties' => [], 'style_config' => []],
            
            // 第3层：数据结构
            ['id' => 5, 'name' => '数组与链表', 'type' => 'topic', 'description' => '线性数据结构', 'position_x' => 150, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 5, 'properties' => [], 'style_config' => []],
            ['id' => 6, 'name' => '栈与队列', 'type' => 'topic', 'description' => '特殊的线性结构', 'position_x' => 300, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 6, 'properties' => [], 'style_config' => []],
            ['id' => 7, 'name' => '树与图', 'type' => 'topic', 'description' => '非线性数据结构', 'position_x' => 450, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 7, 'properties' => [], 'style_config' => []],
            ['id' => 8, 'name' => '哈希表', 'type' => 'topic', 'description' => '快速查找的数据结构', 'position_x' => 600, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 8, 'properties' => [], 'style_config' => []],
            
            // 第4层：算法
            ['id' => 9, 'name' => '算法复杂度', 'type' => 'concept', 'description' => '时间和空间复杂度分析', 'position_x' => 200, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 9, 'properties' => [], 'style_config' => []],
            ['id' => 10, 'name' => '排序算法', 'type' => 'skill', 'description' => '各种排序方法', 'position_x' => 350, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 10, 'properties' => [], 'style_config' => []],
            ['id' => 11, 'name' => '搜索算法', 'type' => 'skill', 'description' => '查找和搜索技术', 'position_x' => 500, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 11, 'properties' => [], 'style_config' => []],
            ['id' => 12, 'name' => '动态规划', 'type' => 'skill', 'description' => '高级算法设计技术', 'position_x' => 650, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 12, 'properties' => [], 'style_config' => []],
            
            // 第5层：应用
            ['id' => 13, 'name' => '面向对象编程', 'type' => 'topic', 'description' => 'OOP编程范式', 'position_x' => 250, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 13, 'properties' => [], 'style_config' => []],
            ['id' => 14, 'name' => '软件工程', 'type' => 'topic', 'description' => '软件开发方法论', 'position_x' => 450, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 14, 'properties' => [], 'style_config' => []],
            ['id' => 15, 'name' => '算法应用', 'type' => 'skill', 'description' => '算法在实际问题中的应用', 'position_x' => 650, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 15, 'properties' => [], 'style_config' => []],
        ];
        
        $relations = [
            // 计算思维 -> 编程基础
            ['from_node_id' => 1, 'to_node_id' => 2, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '思维基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 1, 'to_node_id' => 3, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '概念基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 1, 'to_node_id' => 4, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '逻辑基础', 'properties' => [], 'style_config' => []],
            
            // 编程基础 -> 数据结构
            ['from_node_id' => 2, 'to_node_id' => 5, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '语言基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 3, 'to_node_id' => 5, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '数据类型', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 3, 'to_node_id' => 6, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '数据类型', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 3, 'to_node_id' => 7, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '数据类型', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 3, 'to_node_id' => 8, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '数据类型', 'properties' => [], 'style_config' => []],
            
            // 数据结构 -> 算法
            ['from_node_id' => 5, 'to_node_id' => 9, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '结构基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 5, 'to_node_id' => 10, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '排序需要数组', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 7, 'to_node_id' => 11, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '搜索需要树图', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 9, 'to_node_id' => 12, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '复杂度分析', 'properties' => [], 'style_config' => []],
            
            // 算法 -> 应用
            ['from_node_id' => 2, 'to_node_id' => 13, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '编程基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 10, 'to_node_id' => 15, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '算法基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 11, 'to_node_id' => 15, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '算法基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 13, 'to_node_id' => 14, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'OOP是工程基础', 'properties' => [], 'style_config' => []],
            
            // 相关关系
            ['from_node_id' => 5, 'to_node_id' => 6, 'relation_type' => 'related', 'weight' => 0.8, 'description' => '都是线性结构', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 10, 'to_node_id' => 11, 'relation_type' => 'related', 'weight' => 0.8, 'description' => '都是基础算法', 'properties' => [], 'style_config' => []],
        ];
        
        return [
            'name' => '计算机科学基础',
            'category' => KnowledgeGraphTemplateModel::CATEGORY_CS,
            'description' => '涵盖计算机科学的核心概念，包括编程基础、数据结构和算法，适合计算机专业入门课程',
            'difficulty_level' => KnowledgeGraphTemplateModel::DIFFICULTY_BEGINNER,
            'tags' => '计算机,编程,数据结构,算法',
            'nodes' => $nodes,
            'relations' => $relations,
            'is_system' => true,
            'created_by' => 0,
            'sort_order' => 1
        ];
    }
    
    /**
     * 数学基础模板（12个节点）
     *
     * @return array
     */
    private function getMathTemplate(): array
    {
        $nodes = [
            // 第1层
            ['id' => 1, 'name' => '数学思维', 'type' => 'concept', 'description' => '数学的基本思维方式', 'position_x' => 400, 'position_y' => 50, 'weight' => 1.0, 'sort_order' => 1, 'properties' => [], 'style_config' => []],
            
            // 第2层：基础
            ['id' => 2, 'name' => '集合论', 'type' => 'topic', 'description' => '现代数学的基础', 'position_x' => 250, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 2, 'properties' => [], 'style_config' => []],
            ['id' => 3, 'name' => '逻辑学', 'type' => 'topic', 'description' => '数学推理的基础', 'position_x' => 550, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 3, 'properties' => [], 'style_config' => []],
            
            // 第3层：代数
            ['id' => 4, 'name' => '代数基础', 'type' => 'topic', 'description' => '变量和方程', 'position_x' => 150, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 4, 'properties' => [], 'style_config' => []],
            ['id' => 5, 'name' => '线性代数', 'type' => 'topic', 'description' => '向量和矩阵', 'position_x' => 300, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 5, 'properties' => [], 'style_config' => []],
            
            // 第3层：分析
            ['id' => 6, 'name' => '微积分', 'type' => 'topic', 'description' => '连续变化的数学', 'position_x' => 500, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 6, 'properties' => [], 'style_config' => []],
            ['id' => 7, 'name' => '概率统计', 'type' => 'topic', 'description' => '随机现象的数学', 'position_x' => 650, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 7, 'properties' => [], 'style_config' => []],
            
            // 第4层：应用
            ['id' => 8, 'name' => '数值分析', 'type' => 'skill', 'description' => '数值计算方法', 'position_x' => 200, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 8, 'properties' => [], 'style_config' => []],
            ['id' => 9, 'name' => '优化理论', 'type' => 'skill', 'description' => '最优化问题', 'position_x' => 400, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 9, 'properties' => [], 'style_config' => []],
            ['id' => 10, 'name' => '数据分析', 'type' => 'skill', 'description' => '数据的统计分析', 'position_x' => 600, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 10, 'properties' => [], 'style_config' => []],
            
            // 第5层：高级应用
            ['id' => 11, 'name' => '机器学习数学', 'type' => 'skill', 'description' => '机器学习的数学基础', 'position_x' => 300, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 11, 'properties' => [], 'style_config' => []],
            ['id' => 12, 'name' => '金融数学', 'type' => 'skill', 'description' => '金融领域的数学应用', 'position_x' => 500, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 12, 'properties' => [], 'style_config' => []],
        ];
        
        $relations = [
            // 数学思维 -> 基础
            ['from_node_id' => 1, 'to_node_id' => 2, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '思维基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 1, 'to_node_id' => 3, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '思维基础', 'properties' => [], 'style_config' => []],
            
            // 基础 -> 代数和分析
            ['from_node_id' => 2, 'to_node_id' => 4, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '集合是代数基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 4, 'to_node_id' => 5, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '代数基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 2, 'to_node_id' => 6, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '集合是微积分基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 2, 'to_node_id' => 7, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '集合是概率基础', 'properties' => [], 'style_config' => []],
            
            // 代数和分析 -> 应用
            ['from_node_id' => 5, 'to_node_id' => 8, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '线性代数应用', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 6, 'to_node_id' => 8, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '微积分应用', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 5, 'to_node_id' => 9, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '优化需要线性代数', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 6, 'to_node_id' => 9, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '优化需要微积分', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 7, 'to_node_id' => 10, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '统计是数据分析基础', 'properties' => [], 'style_config' => []],
            
            // 应用 -> 高级应用
            ['from_node_id' => 5, 'to_node_id' => 11, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'ML需要线性代数', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 7, 'to_node_id' => 11, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'ML需要概率统计', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 9, 'to_node_id' => 11, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'ML需要优化', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 7, 'to_node_id' => 12, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '金融需要统计', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 6, 'to_node_id' => 12, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '金融需要微积分', 'properties' => [], 'style_config' => []],
            
            // 相关关系
            ['from_node_id' => 2, 'to_node_id' => 3, 'relation_type' => 'related', 'weight' => 0.9, 'description' => '密切相关', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 4, 'to_node_id' => 6, 'relation_type' => 'related', 'weight' => 0.7, 'description' => '都涉及变量', 'properties' => [], 'style_config' => []],
        ];
        
        return [
            'name' => '数学基础',
            'category' => KnowledgeGraphTemplateModel::CATEGORY_MATH,
            'description' => '涵盖高等数学核心内容，包括代数、分析和应用数学，适合理工科专业',
            'difficulty_level' => KnowledgeGraphTemplateModel::DIFFICULTY_INTERMEDIATE,
            'tags' => '数学,微积分,线性代数,概率统计',
            'nodes' => $nodes,
            'relations' => $relations,
            'is_system' => true,
            'created_by' => 0,
            'sort_order' => 2
        ];
    }
    
    /**
     * Web开发技术栈模板（20个节点）
     *
     * @return array
     */
    private function getWebDevelopmentTemplate(): array
    {
        $nodes = [
            // 第1层：基础
            ['id' => 1, 'name' => 'Web开发概览', 'type' => 'concept', 'description' => 'Web开发的整体架构', 'position_x' => 400, 'position_y' => 50, 'weight' => 1.0, 'sort_order' => 1, 'properties' => [], 'style_config' => []],
            
            // 第2层：前端基础
            ['id' => 2, 'name' => 'HTML', 'type' => 'topic', 'description' => '网页结构标记语言', 'position_x' => 150, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 2, 'properties' => [], 'style_config' => []],
            ['id' => 3, 'name' => 'CSS', 'type' => 'topic', 'description' => '网页样式设计', 'position_x' => 300, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 3, 'properties' => [], 'style_config' => []],
            ['id' => 4, 'name' => 'JavaScript', 'type' => 'topic', 'description' => '网页交互编程', 'position_x' => 450, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 4, 'properties' => [], 'style_config' => []],
            
            // 第2层：后端基础
            ['id' => 5, 'name' => 'HTTP协议', 'type' => 'concept', 'description' => 'Web通信协议', 'position_x' => 600, 'position_y' => 150, 'weight' => 1.0, 'sort_order' => 5, 'properties' => [], 'style_config' => []],
            
            // 第3层：前端进阶
            ['id' => 6, 'name' => 'DOM操作', 'type' => 'skill', 'description' => '文档对象模型操作', 'position_x' => 100, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 6, 'properties' => [], 'style_config' => []],
            ['id' => 7, 'name' => 'AJAX', 'type' => 'skill', 'description' => '异步数据请求', 'position_x' => 250, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 7, 'properties' => [], 'style_config' => []],
            ['id' => 8, 'name' => 'ES6+', 'type' => 'topic', 'description' => '现代JavaScript', 'position_x' => 400, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 8, 'properties' => [], 'style_config' => []],
            
            // 第3层：后端进阶
            ['id' => 9, 'name' => 'RESTful API', 'type' => 'concept', 'description' => 'API设计规范', 'position_x' => 550, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 9, 'properties' => [], 'style_config' => []],
            ['id' => 10, 'name' => '数据库', 'type' => 'topic', 'description' => '数据存储与管理', 'position_x' => 700, 'position_y' => 250, 'weight' => 1.0, 'sort_order' => 10, 'properties' => [], 'style_config' => []],
            
            // 第4层：前端框架
            ['id' => 11, 'name' => 'Vue.js', 'type' => 'skill', 'description' => '渐进式前端框架', 'position_x' => 150, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 11, 'properties' => [], 'style_config' => []],
            ['id' => 12, 'name' => 'React', 'type' => 'skill', 'description' => 'UI组件库', 'position_x' => 300, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 12, 'properties' => [], 'style_config' => []],
            
            // 第4层：后端框架
            ['id' => 13, 'name' => 'Node.js', 'type' => 'topic', 'description' => 'JavaScript服务器端', 'position_x' => 450, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 13, 'properties' => [], 'style_config' => []],
            ['id' => 14, 'name' => 'PHP', 'type' => 'topic', 'description' => '服务器端脚本语言', 'position_x' => 600, 'position_y' => 350, 'weight' => 1.0, 'sort_order' => 14, 'properties' => [], 'style_config' => []],
            
            // 第5层：工具和部署
            ['id' => 15, 'name' => 'Webpack', 'type' => 'skill', 'description' => '模块打包工具', 'position_x' => 200, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 15, 'properties' => [], 'style_config' => []],
            ['id' => 16, 'name' => 'Git', 'type' => 'skill', 'description' => '版本控制系统', 'position_x' => 350, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 16, 'properties' => [], 'style_config' => []],
            ['id' => 17, 'name' => 'Docker', 'type' => 'skill', 'description' => '容器化部署', 'position_x' => 500, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 17, 'properties' => [], 'style_config' => []],
            ['id' => 18, 'name' => 'CI/CD', 'type' => 'concept', 'description' => '持续集成与部署', 'position_x' => 650, 'position_y' => 450, 'weight' => 1.0, 'sort_order' => 18, 'properties' => [], 'style_config' => []],
            
            // 第6层：高级主题
            ['id' => 19, 'name' => '性能优化', 'type' => 'skill', 'description' => 'Web性能优化技术', 'position_x' => 300, 'position_y' => 550, 'weight' => 1.0, 'sort_order' => 19, 'properties' => [], 'style_config' => []],
            ['id' => 20, 'name' => '安全防护', 'type' => 'skill', 'description' => 'Web安全最佳实践', 'position_x' => 500, 'position_y' => 550, 'weight' => 1.0, 'sort_order' => 20, 'properties' => [], 'style_config' => []],
        ];
        
        $relations = [
            // 第1层 -> 第2层
            ['from_node_id' => 1, 'to_node_id' => 2, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Web基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 1, 'to_node_id' => 3, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Web基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 1, 'to_node_id' => 4, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Web基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 1, 'to_node_id' => 5, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Web基础', 'properties' => [], 'style_config' => []],
            
            // 第2层 -> 第3层（前端）
            ['from_node_id' => 2, 'to_node_id' => 6, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'HTML是DOM基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 4, 'to_node_id' => 6, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'JS操作DOM', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 4, 'to_node_id' => 7, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'AJAX基于JS', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 5, 'to_node_id' => 7, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'AJAX使用HTTP', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 4, 'to_node_id' => 8, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'ES6是JS进化', 'properties' => [], 'style_config' => []],
            
            // 第2层 -> 第3层（后端）
            ['from_node_id' => 5, 'to_node_id' => 9, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'REST基于HTTP', 'properties' => [], 'style_config' => []],
            
            // 第3层 -> 第4层（前端框架）
            ['from_node_id' => 6, 'to_node_id' => 11, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Vue需要DOM知识', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 8, 'to_node_id' => 11, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Vue使用ES6', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 6, 'to_node_id' => 12, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'React需要DOM知识', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 8, 'to_node_id' => 12, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'React使用ES6', 'properties' => [], 'style_config' => []],
            
            // 第3层 -> 第4层（后端框架）
            ['from_node_id' => 8, 'to_node_id' => 13, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Node基于JS', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 9, 'to_node_id' => 13, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Node实现REST', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 9, 'to_node_id' => 14, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'PHP实现REST', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 10, 'to_node_id' => 13, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Node连接数据库', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 10, 'to_node_id' => 14, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'PHP连接数据库', 'properties' => [], 'style_config' => []],
            
            // 第4层 -> 第5层
            ['from_node_id' => 11, 'to_node_id' => 15, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Webpack打包Vue', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 12, 'to_node_id' => 15, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Webpack打包React', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 16, 'to_node_id' => 18, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Git是CI/CD基础', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 17, 'to_node_id' => 18, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'Docker支持CI/CD', 'properties' => [], 'style_config' => []],
            
            // 第5层 -> 第6层
            ['from_node_id' => 11, 'to_node_id' => 19, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '框架优化', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 15, 'to_node_id' => 19, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '打包优化', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 9, 'to_node_id' => 20, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => 'API安全', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 14, 'to_node_id' => 20, 'relation_type' => 'prerequisite', 'weight' => 1.0, 'description' => '后端安全', 'properties' => [], 'style_config' => []],
            
            // 相关关系
            ['from_node_id' => 2, 'to_node_id' => 3, 'relation_type' => 'related', 'weight' => 0.9, 'description' => '前端三剑客', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 3, 'to_node_id' => 4, 'relation_type' => 'related', 'weight' => 0.9, 'description' => '前端三剑客', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 11, 'to_node_id' => 12, 'relation_type' => 'related', 'weight' => 0.8, 'description' => '都是前端框架', 'properties' => [], 'style_config' => []],
            ['from_node_id' => 13, 'to_node_id' => 14, 'relation_type' => 'related', 'weight' => 0.7, 'description' => '都是后端语言', 'properties' => [], 'style_config' => []],
        ];
        
        return [
            'name' => 'Web全栈开发技术栈',
            'category' => KnowledgeGraphTemplateModel::CATEGORY_CS,
            'description' => '完整的Web开发技术体系，涵盖前端、后端、工具链和部署，适合全栈开发课程',
            'difficulty_level' => KnowledgeGraphTemplateModel::DIFFICULTY_INTERMEDIATE,
            'tags' => 'Web开发,前端,后端,全栈,JavaScript,框架',
            'nodes' => $nodes,
            'relations' => $relations,
            'is_system' => true,
            'created_by' => 0,
            'sort_order' => 3
        ];
    }
}


<?php
/**
 * 评分器工厂类
 * 
 * 负责注册和获取题型对应的评分器
 * 
 * @copyright 2024 酷瓜云课堂扩展
 * @author 开发团队
 */

namespace App\Services\Assignment\Graders;

class GraderFactory
{
    /**
     * 所有注册的评分器
     *
     * @var QuestionGraderInterface[]
     */
    private $graders = [];

    /**
     * 构造函数 - 注册所有评分器
     */
    public function __construct()
    {
        $this->graders = [
            new SingleChoiceGrader(),
            new MultipleChoiceGrader(),
            new EssayGrader(),
            new CodeGrader(),
            new FileUploadGrader(),
        ];
    }

    /**
     * 根据题型获取对应的评分器
     *
     * @param string $questionType
     * @return QuestionGraderInterface
     * @throws \Exception
     */
    public function getGrader(string $questionType): QuestionGraderInterface
    {
        foreach ($this->graders as $grader) {
            if ($grader->supports($questionType)) {
                return $grader;
            }
        }

        throw new \Exception("不支持的题型: {$questionType}");
    }

    /**
     * 检查题型是否支持
     *
     * @param string $questionType
     * @return bool
     */
    public function supports(string $questionType): bool
    {
        foreach ($this->graders as $grader) {
            if ($grader->supports($questionType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取所有支持的题型
     *
     * @return array
     */
    public function getSupportedTypes(): array
    {
        $types = [];
        foreach ($this->graders as $grader) {
            // 通过检查supports方法获取支持的类型
            $supportedTypes = [
                'single_choice',
                'multiple_choice',
                'essay',
                'code',
                'file_upload'
            ];
            
            foreach ($supportedTypes as $type) {
                if ($grader->supports($type)) {
                    $types[] = $type;
                }
            }
        }

        return array_unique($types);
    }
}


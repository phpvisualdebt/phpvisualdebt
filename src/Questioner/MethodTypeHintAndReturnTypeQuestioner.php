<?php declare(strict_types=1);
namespace PHPVisualDebt\Questioner;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PHPVisualDebt\FileQuestions;

class MethodTypeHintAndReturnTypeQuestioner extends AbstractQuestioner implements NodeVisitor
{
    const VISUAL_DEBT = 1;

    /** @var Node\Stmt\Class_[]|Node\Stmt\Trait_[] */
    private $declarations = [];
    /** @var int */
    private $sentenceCount = 0;

    public function beforeTraverse(array $nodes)
    {
        // TODO: Implement beforeTraverse() method.
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Class_ ||
            $node instanceof Node\Stmt\Interface_ ||
            $node instanceof Node\Stmt\Trait_
        ) {
            $methods = $node->getMethods();
            foreach ($methods as $index => $classMethod) {
                $hasTypehints = false;
                foreach ($classMethod->getParams() as $param) {
                    if ($param->getType() !== 'array') {
                        $hasTypehints = true;
                    }
                }
                if ($classMethod->getReturnType() || $hasTypehints) {
                    /** @var Node\Name $name */
                    $name = $node->namespacedName;
                    $this->declarations[$name->toString()] = $methods;
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
        // TODO: Implement leaveNode() method.
    }

    public function afterTraverse(array $nodes)
    {
        // TODO: Implement afterTraverse() method.
    }

    /**
     * @return FileQuestions[]
     */
    public function getFileQuestions() : array
    {
        $questions = [];
        foreach ($this->declarations as $type => $methods) {
            /** @var Node\Stmt\ClassMethod[] $methods */
            foreach ($methods as $index => $classMethod) {
                foreach ($classMethod->getParams() as $param) {
                    if ($param->type !== 'array' && !($param->type instanceof Node\Name\FullyQualified)) {
                        $questions[] = new Question(
                            "Do you mind removing a <keyword>{$param->type}</keyword> typehint in <name>{$type}::{$classMethod->name}</name>",
                            self::VISUAL_DEBT,
                            $param->getLine()
                        );
                    }
                }
                if (
                    $classMethod->getReturnType() &&
                    $classMethod->getReturnType() !== 'array' &&
                    !($classMethod->getReturnType() instanceof Node\Name\FullyQualified)
                ) {
                    $typeName = $classMethod->getReturnType();
                    if ($typeName instanceof Node\NullableType) {
                        $questions[] = new Question(
                            "Rly? Nullable return type <keyword>{$typeName->type->toString()}</keyword> in <name>{$type}::{$classMethod->name}</name>",
                            self::VISUAL_DEBT,
                            $classMethod->getLine()
                        );
                        continue;
                    }
                    switch ($this->sentenceCount++ % 3) {
                        case 1:
                            $questions[] = new Question(
                                "Why? Return type <keyword>{$classMethod->getReturnType()}</keyword> again in <name>{$type}::{$classMethod->name}</name>",
                                self::VISUAL_DEBT,
                                $classMethod->getLine()
                            );
                            break;
                        case 2:
                            $questions[] = new Question(
                                "Why again? Return type <keyword>{$classMethod->getReturnType()}</keyword> is for dummies in <name>{$type}::{$classMethod->name}</name>",
                                self::VISUAL_DEBT,
                                $classMethod->getLine()
                            );
                            break;
                        default:
                            $questions[] = new Question(
                                "Gonna remove return type <keyword>{$classMethod->getReturnType()}</keyword> right? in <name>{$type}::{$classMethod->name}</name>",
                                self::VISUAL_DEBT,
                                $classMethod->getLine()
                            );
                    }
                }
            }
        }
        $this->declarations = [];

        return [
            new FileQuestions($this->fileInfo, $questions),
        ];
    }

    public function isSingleFileMode(): bool
    {
        return true;
    }

    public function isPostAnalysisMode(): bool
    {
        return false;
    }
}

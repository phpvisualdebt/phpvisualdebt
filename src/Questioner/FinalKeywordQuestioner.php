<?php declare(strict_types=1);
namespace PHPVisualDebt\Questioner;

use PhpParser\Node;
use PhpParser\NodeVisitor;
use PHPVisualDebt\FileQuestions;

class FinalKeywordQuestioner extends AbstractQuestioner implements NodeVisitor
{
    const VISUAL_DEBT = 1;

    /** @var Node\Stmt\Class_[]|Node\Stmt\Trait_[] */
    private $finals = [];

    public function beforeTraverse(array $nodes)
    {
        // TODO: Implement beforeTraverse() method.
    }

    public function enterNode(Node $node)
    {
        if (
            $node instanceof Node\Stmt\Class_ ||
            $node instanceof Node\Stmt\Trait_
        ) {
            if (!$node->isFinal()) {
                return;
            }
            /** @var Node\Name $name */
            $name = $node->namespacedName;
            $this->finals[$name->toString()] = $node;
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
        foreach ($this->finals as $final) {
            $type = 'class';
            if ($final instanceof Node\Stmt\Trait_) {
                $type = 'trait';
            }
            /** @var Node\Name $name */
            $name = $final->namespacedName;
            $questions[] = new Question(
                "Come on! I'm not your daddy! " .
                "Do you really need a <keyword>final</keyword> keyword in {$type} <name>{$name}</name>",
                self::VISUAL_DEBT,
                $final->getLine()
            );
        }
        $this->finals = [];

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

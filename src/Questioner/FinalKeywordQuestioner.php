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
            switch ($this->sentenceCount++ % 3) {
                case 1:
                    $questions[] = new Question(
                        "Really? A <keyword>final</keyword> keyword in {$type} <name>{$name}</name> " .
                        "Ohhhhh... come on! I'm not your daddy!",
                        self::VISUAL_DEBT,
                        $final->getLine()
                    );
                    break;
                case 2:
                    $questions[] = new Question(
                        "Again? A <keyword>final</keyword> keyword in {$type} <name>{$name}</name> " .
                        'WTF?!',
                        self::VISUAL_DEBT,
                        $final->getLine()
                    );
                    break;
                default:
                    $questions[] = new Question(
                        "Do you really need a <keyword>final</keyword> keyword in {$type} <name>{$name}</name>",
                        self::VISUAL_DEBT,
                        $final->getLine()
                    );
                    break;
            }
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

<?php declare(strict_types=1);
namespace PHPVisualDebt\Questioner;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeVisitor;
use PHPVisualDebt\FileQuestions;

class InterfaceQuestioner extends AbstractQuestioner implements NodeVisitor
{
    const VISUAL_DEBT = 1;

    /** @var Node\Stmt\Interface_[] */
    protected $interfaces = [];
    /** @var Node\Stmt\Class_[] */
    protected $classes = [];
    /** @var \SplFileInfo[] */
    protected $fileClasses = [];

    public function beforeTraverse(array $nodes)
    {
        // TODO: Implement beforeTraverse() method.
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Interface_) {
            /** @var Name $name */
            $name = $node->namespacedName;
            $this->interfaces[$name->toString()] = $node;
        }
        if ($node instanceof Node\Stmt\Class_ && \count($node->implements) > 0) {
            /** @var Name $name */
            $name = $node->namespacedName;
            $this->classes[$name->toString()] = $node;
            $this->fileClasses[$name->toString()] = $this->fileInfo;
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

    public function getFileQuestions() : array
    {
        $filesQuestions = [];
        $implements = [];
        foreach ($this->classes as $class) {
            /** @var Name\FullyQualified $implement */
            foreach ($class->implements as $implement) {
                if (!\array_key_exists($implement->toString(), $implements)) {
                    $implements[$implement->toString()] = 0;
                }
                $implements[$implement->toString()]++;
            }
        }
        foreach ($this->classes as $class) {
            /** @var Name $name */
            $name = $class->namespacedName;
            /** @var Name\FullyQualified $implement */
            foreach ($class->implements as $implement) {
                if (
                    \array_key_exists($name->toString(), $this->fileClasses) &&
                    \array_key_exists($implement->toString(), $this->interfaces) &&
                    \array_key_exists($implement->toString(), $implements) &&
                    1 === $implements[$implement->toString()]
                ) {
                    $filesQuestions[] = new FileQuestions(
                        $this->fileClasses[$name->toString()],
                        [
                            new Question(
                                "Is there any justification for interface <name>{$implement}</name> usage?",
                                self::VISUAL_DEBT,
                                $implement->getLine()
                            ),
                        ]
                    );
                }
            }
        }

        return $filesQuestions;
    }

    public function isSingleFileMode(): bool
    {
        return false;
    }

    public function isPostAnalysisMode(): bool
    {
        return true;
    }
}

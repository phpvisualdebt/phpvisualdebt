<?php declare(strict_types=1);
namespace PHPVisualDebt;

use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PHPVisualDebt\Exception\ParseException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class StaticAnalyzer
{
    /**
     * @var Parser
     */
    private $parser;
    /**
     * @var NodeTraverser
     */
    private $traverser;
    /**
     * @var array|Questioner\AbstractQuestioner[]
     */
    protected $questioners = [];

    public function __construct(Parser $parser, NodeTraverser $traverser)
    {
        $this->parser = $parser;
        $this->traverser = $traverser;
    }

    public function addQuestioner(Questioner\AbstractQuestioner $questioner)
    {
        $this->questioners[\get_class($questioner)] = $questioner;
    }

    public function analyze($directory) : array
    {
        $filesQuestions = [];
        /** @var \SplFileInfo[] $files */
        $files = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
            '/\.php$/'
        );
        $this->traverser->addVisitor(new NameResolver());
        foreach ($this->questioners as $questioner) {
            $this->traverser->addVisitor($questioner);
        }
        foreach ($files as $file) {
            try {
                $stmts = $this->parser->parse(\file_get_contents($file->getPathname()));
                foreach ($this->questioners as $questioner) {
                    $questioner->setCurrentFileInfo($file);
                }
                $this->traverser->traverse($stmts);
            } catch (Error $error) {
                throw new ParseException($error->getMessage());
            } finally {
                foreach ($this->questioners as $questioner) {
                    if ($questioner->isSingleFileMode()) {
                        foreach ($questioner->getFileQuestions() as $fileQuestions) {
                            $filesQuestions[] = $fileQuestions;
                        }
                    }
                }
            }
        }
        foreach ($this->questioners as $questioner) {
            if ($questioner->isPostAnalysisMode()) {
                foreach ($questioner->getFileQuestions() as $fileQuestions) {
                    $filesQuestions[] = $fileQuestions;
                }
            }
        }
//dump($filesQuestions);
        return $filesQuestions;
    }
}

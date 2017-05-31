<?php declare(strict_types=1);
namespace PHPVisualDebt\Questioner;

use PHPVisualDebt\FileQuestions;

interface Questioner
{
    /**
     * @return FileQuestions[]
     */
    public function getFileQuestions() : array;
    public function isSingleFileMode() : bool;
    public function isPostAnalysisMode() : bool;
}

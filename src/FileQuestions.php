<?php declare(strict_types=1);
namespace PHPVisualDebt;

use PHPVisualDebt\Questioner\Question;

final class FileQuestions
{
    /** @var \SplFileInfo */
    private $fileInfo;
    /** @var Question[] */
    private $questions = [];

    public function __construct(\SplFileInfo $fileInfo, array $questions)
    {
        $this->fileInfo = $fileInfo;
        $this->questions = $questions;
    }

    public function getFileInfo(): \SplFileInfo
    {
        return $this->fileInfo;
    }

    /**
     * @return Question[]
     */
    public function getQuestions() : array
    {
        return $this->questions;
    }
}

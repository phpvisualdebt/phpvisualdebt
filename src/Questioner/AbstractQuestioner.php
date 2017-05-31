<?php declare(strict_types=1);
namespace PHPVisualDebt\Questioner;

use PHPVisualDebt\FileQuestions;

abstract class AbstractQuestioner implements Questioner
{
    const SINGLE_FILE_MODE = 1;
    const POST_ANALYSIS_MODE = 2;
    /** @var array|Question[] */
    protected $questions = [];
    /** @var \SplFileInfo */
    protected $fileInfo;

    /**
     * @return FileQuestions[]
     */
    public function getFileQuestions() : array
    {
        return [
            new FileQuestions($this->fileInfo, $this->questions),
        ];
    }

    public function setCurrentFileInfo(\SplFileInfo $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }
}

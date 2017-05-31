<?php declare(strict_types=1);
namespace PHPVisualDebt\Questioner;

final class Question
{
    /**
     * @var string
     */
    private $question;
    /**
     * @var int
     */
    private $line;
    /**
     * @var int
     */
    private $debt;

    public function __construct(string $question, int $debt = 1, int $line = 0)
    {
        $this->question = $question;
        $this->debt = $debt;
        $this->line = $line;
    }

    public function getQuestion() : string
    {
        return $this->question;
    }

    public function getDebt() : int
    {
        return $this->debt;
    }

    public function getLine() : int
    {
        return $this->line;
    }
}

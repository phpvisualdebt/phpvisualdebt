<?php
namespace PHPVisualDebt\Command;

use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPVisualDebt\FileQuestions;
use PHPVisualDebt\Questioner\FinalKeywordQuestioner;
use PHPVisualDebt\Questioner\InterfaceQuestioner;
use PHPVisualDebt\Questioner\MethodTypeHintAndReturnTypeQuestioner;
use PHPVisualDebt\StaticAnalyzer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this->setName('check');
        $this->addArgument('dir', InputArgument::OPTIONAL, 'Directory to search for source codes', 'src/');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getArgument('dir');

        $analyzer = new StaticAnalyzer(
            (new ParserFactory)->create(ParserFactory::PREFER_PHP7),
            new NodeTraverser()
        );
        $analyzer->addQuestioner(new InterfaceQuestioner());
        $analyzer->addQuestioner(new FinalKeywordQuestioner());
        $analyzer->addQuestioner(new MethodTypeHintAndReturnTypeQuestioner());
        $groupedFilesQuestions = [];


        /** @var FileQuestions $fileQuestions */
        foreach ($analyzer->analyze(\realpath($dir)) as $fileQuestions) {
            if (0 === \count($fileQuestions->getQuestions())) {
                continue;
            }
            $groupedFilesQuestions[$fileQuestions->getFileInfo()->getPathname()][] = $fileQuestions;
        }
        $formatter = $output->getFormatter();
        if (!$formatter->hasStyle('keyword')) {
            $formatter->setStyle('keyword', new OutputFormatterStyle('cyan'));
        }
        if (!$formatter->hasStyle('name')) {
            $formatter->setStyle('name', new OutputFormatterStyle('magenta'));
        }
        if (!$formatter->hasStyle('accent')) {
            $formatter->setStyle('accent', new OutputFormatterStyle('yellow', null, ['bold']));
        }
        if (!$formatter->hasStyle('debt')) {
            $formatter->setStyle('debt', new OutputFormatterStyle('white', null, ['bold']));
        }
        if (!$formatter->hasStyle('vd')) {
            $formatter->setStyle('vd', new OutputFormatterStyle('red', null, ['bold']));
        }

        $output->writeln('<accent>Questioning Everything</accent> in directory: <comment>' . \rtrim($dir, DIRECTORY_SEPARATOR) . '</comment>');

        foreach ($groupedFilesQuestions as $fileName => $filesQuestions) {
            $fileName = \str_replace(realpath($dir) . DIRECTORY_SEPARATOR, '', $fileName);
            $visualDebt = 0;
            /** @var FileQuestions $fileQuestions */
            foreach ($filesQuestions as $fileQuestions) {
                foreach ($fileQuestions->getQuestions() as $question) {
                    $visualDebt += $question->getDebt();
                }
            }
            $output->writeln("\n<info>Found some questions in file: <comment>{$fileName}</comment></info>");
            $output->writeln("<vd>VisualDebt:</vd> <debt>{$visualDebt}</debt> point" . ($visualDebt > 1 ? 's' : ''));
            /** @var FileQuestions $fileQuestions */
            foreach ($filesQuestions as $fileQuestions) {
                foreach ($fileQuestions->getQuestions() as $question) {
                    $output->writeln("<accent>Question:</accent> {$question->getQuestion()} on line <comment>{$question->getLine()}</comment>");
                    $visualDebt += $question->getDebt();
                }
            }
        }

        return 0;
    }
}

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
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this->setName('check');
        $this->addArgument(
            'dir',
            InputArgument::OPTIONAL,
            'Directory to search for source codes',
            'src/'
        );
        $this->addOption(
            'question',
            null,
            InputOption::VALUE_OPTIONAL,
            'Kind of questions to ask (comma separated list: <comment>final,typehint,interface</comment> or <comment>everything</comment>)',
            'everything'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getArgument('dir');
        $question = \explode(',', $input->getOption('question'));
        $everything = false;
        if (in_array('everything', $question)) {
            $everything = true;
            $question = ['final', 'typehint', 'interface'];
        }

        $analyzer = new StaticAnalyzer(
            (new ParserFactory)->create(ParserFactory::PREFER_PHP7),
            new NodeTraverser()
        );
        if (\in_array('interface', $question)) {
            $analyzer->addQuestioner(new InterfaceQuestioner());
        }
        if (\in_array('final', $question)) {
            $analyzer->addQuestioner(new FinalKeywordQuestioner());
        }
        if (\in_array('typehint', $question)) {
            $analyzer->addQuestioner(new MethodTypeHintAndReturnTypeQuestioner());
        }
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
            $formatter->setStyle('vd', new OutputFormatterStyle('blue', null, ['bold']));
        }
        if ($everything) {
            $output->writeln(
                '<accent>Question Everything</accent> in directory: <comment>' .
                \rtrim($dir, DIRECTORY_SEPARATOR) .
                '</comment>'
            );
        } else {
            $output->writeln(
                '<accent>Question Almost-Everything</accent> in directory: <comment>' .
                \rtrim($dir, DIRECTORY_SEPARATOR) .
                '</comment> like <comment>' .
                \implode(', ', $question) .
                '</comment>' . PHP_EOL . 'My advise: <accent>Question Everything!</accent>'
            );
        }

        $filesVisualDebt = [];
        foreach ($groupedFilesQuestions as $fileName => $filesQuestions) {
            $fileName = \str_replace(realpath($dir) . DIRECTORY_SEPARATOR, '', $fileName);
            $visualDebt = 0;
            /** @var FileQuestions $fileQuestions */
            foreach ($filesQuestions as $fileQuestions) {
                foreach ($fileQuestions->getQuestions() as $question) {
                    $visualDebt += $question->getDebt();
                }
            }
            $filesVisualDebt[$fileName] = $visualDebt;
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

        $summary = new Table($output);
        $summary->setHeaders(['File', 'VisualDebt']);
        $summaryVisualDebt = 0;
        foreach ($filesVisualDebt as $fileName => $visualDebt) {
            $summary->addRow([$fileName, $visualDebt]);
            $summaryVisualDebt += $visualDebt;
        }
        if ($summaryVisualDebt > 0) {
            $output->writeln(
                PHP_EOL . '<info>You have VisualDebt in <comment>' .
                \count($groupedFilesQuestions) .
                '</comment> files</info>'
            );
            $summary->addRow(new TableSeparator());

            $summary->addRow(['<comment>Summary</comment>', $summaryVisualDebt]);
            $summary->render();

            return 1;
        }

        return 0;
    }
}

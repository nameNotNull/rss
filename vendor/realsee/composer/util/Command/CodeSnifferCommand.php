<?php
/**
 * User: chenyi chenyi@lianjia.com
 * Date: 2017/4/17
 * Time: 15:56
 */

namespace MobileApi\Util\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CodeSnifferCommand extends Command
{
    protected $rawCSCommand = '%PHP_PATH% %VENDOR%/bin/phpcs --standard=%CONFIG% %FILES% --ignore=%IGNORE_FILES%';
    protected $rawCBFCommand = '%PHP_PATH% %VENDOR%/bin/phpcbf --standard=%CONFIG% --no-patch %FILES%';
    protected $glue = '!o!';

    /**
     * configure
     */
    protected function configure()
    {
        $this->setName('code-sniffer')
            ->setAliases(['cs'])
            ->setDescription('CodeSniffer detection based on PSR2')
            ->setHelp('Input class name or file path')
            ->addArgument('target', InputArgument::REQUIRED | InputArgument::IS_ARRAY)
            ->addOption('ignore', '-i', InputOption::VALUE_OPTIONAL, 'ignore files,reg rules : */User/*,*/Common/*', '')
            ->addOption('config', '-c', InputOption::VALUE_OPTIONAL, 'rule files', __DIR__ . '/Config/CodeSniffer/yaf.xml');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $consoleIOStyle = new SymfonyStyle($input, $output);

        $targetFileInfo = [];
        foreach ($input->getArgument('target') as $fileInfo) {
            if (substr($fileInfo, -1) == '/') {
                $targetFileInfo = array_merge($targetFileInfo, $this->getDirContents($fileInfo));
            } else {
                $targetFileInfo[] = $this->getFile($fileInfo);
            }
        }

        $commandParamsMap = [
            '%PHP_PATH%'     => PHP_BINARY,
            '%VENDOR%'       => VENDOR_PATH,
            '%CONFIG%'       => $input->getOption('config'),
            '%FILES%'        => implode(' ', $targetFileInfo),
            '%IGNORE_FILES%' => $input->getOption('ignore'),
        ];

        $consoleIOStyle->title('Start detection!');

        //run code sniffer command
        $CSCommand = $this->getCommand($commandParamsMap, $this->rawCSCommand);
        exec($CSCommand, $CSResult);

        if (empty($CSResult)) {
            $this->isOK($consoleIOStyle);
        }

        //parse code sniffer command result
        $CSResultStringType = implode($this->glue, $CSResult);

        $autoFixInfo = [];
        preg_match_all('/PHPCBF CAN FIX THE (\d*) MARKED SNIFF VIOLATIONS AUTOMATICALLY/', $CSResultStringType, $autoFixInfo);

        $CSResult = explode($this->glue, str_replace($autoFixInfo[0], array_map(function ($autoFixNum) {
            return "There are $autoFixNum problems that can be automatically repaired!";
        }, $autoFixInfo[1]), $CSResultStringType));
        $output->writeln($CSResult);

        //figure out total issue
        $totalIssueInfo = [];
        preg_match_all('/FOUND (\d*) ERROR[S]?|(\d*) WARNING[S]?/', $CSResultStringType, $totalIssueInfo);

        //auto repair
        $autoFixNum = 0;
        if (!empty($autoFixInfo[1])) {
            if ($consoleIOStyle->confirm('Whether automatic repair (default yes)', true)) {
                $output->writeln('Automatic repair start!');

                $CBFResult  = [];
                $CBFCommand = $this->getCommand($commandParamsMap, $this->rawCBFCommand);
                exec($CBFCommand, $CBFResult);

                //parse code sniffer bf command result
                $CBFResultStringType = implode($this->glue, array_values($CBFResult));

                $autoFixedInfo = [];
                preg_match_all('/Fixing file: (\d*)\/(\d*) violation[s]? remaining \[made \d* passe[s]?\]... DONE in \d*ms/', $CBFResultStringType, $autoFixedInfo);

                if (!empty($autoFixedInfo[0])) {
                    $autoFixNum = array_sum($autoFixedInfo[2]) - array_sum($autoFixedInfo[1]);
                }

                $CBFResult = array_splice($CBFResult, -3);
                $output->writeln($CBFResult);
            }
        }

        $totalIssueNum = 0;
        if (!empty($totalIssueInfo[0])) {
            array_shift($totalIssueInfo);
            $totalIssueNum = array_sum(array_map('array_sum', $totalIssueInfo));
        };

        if ($totalIssueNum > 0) {
            if ($totalIssueNum - $autoFixNum === 0) {
                $output->writeln('All problems have been automatically repaired. Please ADD and commit again');
            } else {
                $output->writeln('Please commit again after check');
            }
            exit(1);
        }

        $this->isOK($consoleIOStyle);
    }

    /**
     * Just OK
     *
     * @param SymfonyStyle $consoleIOStyle
     */
    protected function isOK(SymfonyStyle $consoleIOStyle)
    {
        $consoleIOStyle->writeln('perfect!');
        exit(0);
    }

    /**
     * Build command base commandParamsMap
     *
     * @param array  $commandParamsMap
     * @param string $rawCommand
     *
     * @return string
     */
    protected function getCommand(array $commandParamsMap, $rawCommand = '')
    {
        return str_replace(array_keys($commandParamsMap), array_values($commandParamsMap), $rawCommand);
    }

    /**
     * get all files in folder and sub folder
     *
     * @param $dir
     *
     * @return array
     */
    protected function getDirContents($dir)
    {
        $result = [];
        $files  = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);

            if (!is_dir($path)) {
                $result[] = $path;
            } else if ($value != "." && $value != "..") {
                $result = array_merge($result, $this->getDirContents($path));
            }
        }

        return $result;
    }


    /**
     * parse file name
     *
     * @param $fileInfo
     *
     * @return string
     */
    protected function getFile($fileInfo)
    {
        if (strpos($fileInfo, '/_') === false) {
            $fileInfo = explode('_', $fileInfo);
            $fileType = array_pop($fileInfo);
        } else {
            $fileType = $fileInfo;
        }

        switch ($fileType) {
            case 'Controller':
                $result = CONTROLLER_PATH;
                break;
            case 'Model':
                $result = MODEL_PATH;
                break;
            default:
                $result = ROOT_PATH;
        }

        if ($result !== ROOT_PATH) {
            foreach ($fileInfo as $info) {
                $result .= '/' . $info;
            }
            $result = $result . '.php';
        } else {
            $result = ROOT_PATH . '/' . $fileType;
        }

        if (!is_file($result)) {
            echo PHP_EOL . $result . ' not find.' . PHP_EOL;
            exit(1);
        }

        return $result;
    }
}

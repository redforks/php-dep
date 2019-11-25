<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/composer-dep.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

$application = new Application();

class DepCommand extends Command {
  protected static $defaultName = 'dep';

  protected function configure() {
    $this
      ->setDescription('Create composer dependency graph')
      ->addOption('reverse', 'r', InputOption::VALUE_NONE, 'Reverse dependency')
      ->addArgument('outputFile', InputArgument::REQUIRED);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $outputFile = $input->getArgument('outputFile');
    $ts = new Filesystem();
    $reverse = $input->getOption('reverse');
    $dotContent = dump($reverse);
    $tmpFile = $ts->tempnam('/tmp', 'php-dep');
    $ts->dumpFile($tmpFile, $dotContent);

    $output = [];
    $exitCode = 0;
    exec("dot -Tpng -o$outputFile $tmpFile", $output, $exitCode);
    if ($exitCode != 0) {
      echo join("\n", $output);
    }
    return $exitCode;
  }
}

$application->add(new DepCommand());
$application->run();

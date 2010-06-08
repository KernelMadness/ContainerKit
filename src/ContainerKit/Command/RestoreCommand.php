<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Restore containers
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class RestoreCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('name', InputArgument::REQUIRED, 'Archive file name'),
      ))
      ->setName('restore')
      ->setDescription('Restore containers')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $name = $input->getArgument('name');
    echo "  WARNING!\n";
    echo "  Selected archive must be created on system with same config and storage filesystem layout\n";
    echo "  Otherwise restore operation may damage your system!\n";
    if (readline("Proceed[Y/n]?") != 'n')
      $this->application->getController()->restore($name, function($message) {
          echo \Console_Color::convert(" %g>>%n ") . $message ."\n";
        }
      );
    else
      echo "Aborted\n";
  }

}

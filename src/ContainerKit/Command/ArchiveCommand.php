<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Archive containers
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class ArchiveCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::REQUIRED, 'Containers selector'),
      ))
      ->setName('archive')
      ->setDescription('Archive containers')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $selector = $input->getArgument('selector');
    echo "  Archive operation requires all selected containers to be stopped\n";
    echo "  Any running containers will be stopped before archiving\n";
    echo "  Archives will be created in current working directory\n";
    if (readline("Proceed[Y/n]?") != 'n')
      $this->application->getController()->archive($selector, function($message) {
          echo \Console_Color::convert(" %g>>%n ") . $message ."\n";
        }
      );
    else
      echo "Aborted\n";
  }

  public function getAutocompleteValues() {
    $containers = $this->application->getController()->getContainers();
    $ret = array();
    foreach ($containers as $container) {
      $ret[] = $container->getName();
    }
    return $ret;
  }
}

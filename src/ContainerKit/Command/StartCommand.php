<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Start containers
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class StartCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::REQUIRED, 'Containers selector'),
      ))
      ->setName('start')
      ->setDescription('Start containers')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $selector = $input->getArgument('selector');
    $this->application->getController()->start($selector, function($message) {
        echo \Console_Color::convert(" %g>>%n ") . $message ."\n";
      }
    );
  }

  public function getAutocompleteValues() {
    $containers = $this->application->getController()->getContainers();
    $ret = array();
    foreach ($containers as $container) {
      if ($container->isStopped())
        $ret[] = $container->getName();
    }
    return $ret;
  }
}

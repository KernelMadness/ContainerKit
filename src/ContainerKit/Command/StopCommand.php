<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Stop containers
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class StopCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::REQUIRED, 'Containers selector'),
      new InputOption('hard', null, InputOption::PARAMETER_NONE, 'Perform hard stop'),
      ))
      ->setName('stop')
      ->setDescription('Stop containers')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $selector = $input->getArgument('selector');
    $hard = $input->getOption('hard');
    $this->application->getController()->stop($selector, $hard, function($message) {
        echo \Console_Color::convert(" %g>>%n ") . $message ."\n";
      }
    );
  }

  public function getAutocompleteValues() {
    $containers = $this->application->getController()->getContainers();
    $ret = array();
    foreach ($containers as $container) {
      if ($container->isRunning())
        $ret[] = $container->getName();
    }
    return $ret;
  }
}

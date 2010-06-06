<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Connect to container console
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class ConsoleCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('name', InputArgument::REQUIRED, 'Container name'),
      ))
      ->setName('console')
      ->setDescription('Connect to container console')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $name = $input->getArgument('name');
    $container = $this->application->getController()->getContainer($name);
    if (!$container) {
      $output->writeln("<error>Container $name does not exists</error>");
    } else {
      $ex = $this->application->getController()->getExecutable('lxc-console');
      \ContainerKit\Console\Launcher::launch("$ex -n $name");
    }
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

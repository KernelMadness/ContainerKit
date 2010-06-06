<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Edit container configuration
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class EditCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('name', InputArgument::REQUIRED, 'Container name'),
      ))
      ->setName('edit')
      ->setDescription('Edit container configuration')
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
      $editor = $this->application->getController()->getExecutable('editor');
      \ContainerKit\Console\Launcher::launch("$editor {$container->getConfigPath()}");
    }
  }

  public function getAutocompleteValues() {
    return $this->application->getController()->getContainersList();
  }
}

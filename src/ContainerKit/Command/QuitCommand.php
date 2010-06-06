<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Quit from ContainerKit shell
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class QuitCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setName('quit')
      ->setDescription('Quit from ContainerKit shell')
      ->setAliases(array('exit'))
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    exit();
  }
}

<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;
use ContainerKit\Console\Formatter;

/**
 * Remove ip from allowed list
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class IpRemoveCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('name', InputArgument::REQUIRED, 'Container name'),
      new InputArgument('ip', InputArgument::REQUIRED, 'Ip to remove'),
      ))
      ->setName('ip-remove')
      ->setDescription('Remove ip from allowed list')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $name = $input->getArgument('name');
    $ip = $input->getArgument('ip');
    $container = $this->application->getController()->getContainer($name);
    if (!$container)
      throw new \Exception('Container does not exists!');
    $container->removeAllowedIp($ip);
  }
}

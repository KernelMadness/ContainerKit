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
 * Allow container to use specified ip
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class IpAddCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('name', InputArgument::REQUIRED, 'Container name'),
      new InputArgument('ip', InputArgument::REQUIRED, 'Ip to add'),
      ))
      ->setName('ip-add')
      ->setDescription('Allow container to use specified ip')
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
    $container->addAllowedIp($ip);
  }
}

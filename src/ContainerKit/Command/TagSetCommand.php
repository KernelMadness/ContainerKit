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
 * Tag operations
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class TagSetCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::REQUIRED, 'Container selector'),
      new InputArgument('tag', InputArgument::REQUIRED, 'Tag to set'),
      ))
      ->setName('tag-set')
      ->setDescription('Set tag to containers')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $selector = $input->getArgument('selector');
    $tag = $input->getArgument('tag');

    $containers = $this->controller->selectContainers($selector);
    array_walk($containers, function(&$container, $key, $tag) {
        $container->removeTag($tag);
      }, $tag);

  }
}

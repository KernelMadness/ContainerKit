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
class TagListCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::REQUIRED, 'Container selector'),
      ))
      ->setName('tag-list')
      ->setDescription('Show container tags by selector')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $selector = $input->getArgument('selector');
    if ($selector == ':all')
      $containers = $this->application->getController()->getContainers();
    else
      $containers = $this->application->getController()->selectContainers($this->params[3]);
    $l = Formatter::calculateNamelength($containers) + 1;
    $FORMAT = "%{$l}s %5s\n";
    printf($FORMAT, 'Name', 'Tags');
    array_walk($containers, function(&$container, $key) use ($FORMAT) {
        vprintf($FORMAT, array($container->getName(), implode(', ', $container->getTags())));
      }
    );
  }
}

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
 * Show containers tags
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
      new InputArgument('selector', InputArgument::OPTIONAL, 'Container selector'),
      ))
      ->setName('tag-list')
      ->setDescription('Show containers tags')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $selector = $input->getArgument('selector');
    if (!$selector)
      $containers = $this->application->getController()->getContainers();
    else
      $containers = $this->application->getController()->selectContainers($selector);
    $l = Formatter::calculateNamelength($containers) + 1;
    $FORMAT = "%{$l}s %s\n";
    printf($FORMAT, 'Name', 'Tags');
    array_walk($containers, function(&$container, $key) use ($FORMAT) {
        $tags = $container->getTags();
        if (empty ($tags))
          vprintf($FORMAT, array($container->getName(), '<none>'));
        foreach ($tags as $key => $tag) {
        if ($key === 0)
          vprintf($FORMAT, array($container->getName(), $tag));
        else
          vprintf($FORMAT, array('', $tag));
        }
      }
    );
  }
}
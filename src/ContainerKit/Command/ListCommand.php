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
 * Show containers list(little brother of Stat command)
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class ListCommand extends Command
{
  /**
   * @see Command
   */
  protected function configure()
  {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::OPTIONAL, 'Containers selector'),
      ))
      ->setName('list')
      ->setDescription('Show containers list')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    if ($selector = $input->getArgument('selector'))
      $containers = $this->application->getController()->selectContainers($selector);
    else
      $containers = $this->application->getController()->getContainers();
    $namelength = Formatter::calculateNamelength($containers) + 1;
    $FORMAT="%2s %{$namelength}s\n";
    printf($FORMAT, ' ', 'Name');
    foreach ($containers as $container) {
      $r = array(
        'state' => '',
        'name' => '',
      );
      $r['name'] = $container->getName();
      $state = $container->getState();
      if ($state == 'RUNNING')
        $r['state'] = \Console_Color::convert(' %g>>%n');
      else if ($state == 'STOPPED')
        $r['state'] = \Console_Color::convert(' %b--%n');
      vprintf($FORMAT, $r);
    }
  }
}

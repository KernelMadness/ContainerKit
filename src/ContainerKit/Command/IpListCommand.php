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
 * Show allowed ip addrs
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class IpListCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputArgument('selector', InputArgument::OPTIONAL, 'Container selector'),
      ))
      ->setName('ip-list')
      ->setDescription('Show allowed ip addrs')
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
    printf($FORMAT, 'Name', 'IP');
    array_walk($containers, function(&$container, $key) use ($FORMAT) {
        $ips = $container->getAllowedIps();
        if (empty ($ips))
          vprintf($FORMAT, array($container->getName(), '<none>'));
        foreach ($ips as $key => $ip) {
        if ($key === 0)
          vprintf($FORMAT, array($container->getName(), $ip));
        else
          vprintf($FORMAT, array('', $ip));
        }
      }
    );
  }
}

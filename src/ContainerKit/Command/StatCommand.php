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
 * Show stats
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class StatCommand extends Command
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
      ->setName('stat')
      ->setDescription('Show stats')
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
    $FORMAT="%2s %{$namelength}s %6s %8s %12s %12s %12s %18s %10s %10s\n";
    printf($FORMAT, ' ', 'Name', 'Tasks', 'Rss', 'User time', 'System time',
      'Uptime', 'IP', 'Upload', 'Download');
    foreach ($containers as $container) {
      $r = array(
        'state' => '',
        'name' => '',
        'tasks' => 'n/a',
        'rss'   => 'n/a',
        'usertime' => 'n/a',
        'systemtime' => 'n/a',
        'uptime' => 'n/a',
        'ip' => 'n/a',
        'upload' => 'n/a',
        'download' => 'n/a',
      );
      $r['name'] = $container->getName();
      $state = $container->getState();
      if ($state == 'RUNNING')
        $r['state'] = \Console_Color::convert(' %g>>%n');
      else if ($state == 'STOPPED')
        $r['state'] = \Console_Color::convert(' %b--%n');

      if ($state == 'RUNNING') {
        $r['tasks'] = count($container->getTasks());
        $r['rss'] = Formatter::formatBytes($container->getRss());
        $r['uptime'] = Formatter::formatTime($container->getUptime());
        $times = $container->getCpuTimes();
        $r['systemtime'] = Formatter::formatTime($times['system']);
        $r['usertime'] = Formatter::formatTime($times['user']);
        $r['ip'] = $container->getIp();
        $traffic = $container->getTraffic();
        $r['upload'] = Formatter::formatBytes($traffic['upload']);
        $r['download'] = Formatter::formatBytes($traffic['download']);
      }
      vprintf($FORMAT, $r);
    }
  }
}

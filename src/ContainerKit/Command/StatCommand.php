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
      ->setName('stat')
      ->setDescription('Show stats')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
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
        $memstat = $container->getMemstat();
        $r['tasks'] = $memstat['tasks'];
        $r['rss'] = Formatter::formatBytes($memstat['rss']);
        $times = $container->getTimes();
        $r['uptime'] = Formatter::formatTime($times['uptime']);
        $r['systemtime'] = Formatter::formatTime($times['system']);
        $r['usertime'] = Formatter::formatTime($times['user']);
        $netstat = $container->getNetstat();
        if (isset($netstat['ip'])) $r['ip'] = $netstat['ip'];
        if (isset($netstat['upload'])) $r['upload'] = Formatter::formatBytes($netstat['upload']);
        if (isset($netstat['download'])) $r['download'] = Formatter::formatBytes($netstat['download']);
      }
      vprintf($FORMAT, $r);
    }
  }
}

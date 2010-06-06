<?php

namespace ContainerKit;

/**
 * Console frontend to ContainerKit
 *
 * @author kmad
 */
class Console {

  private $params;

  /**
   *
   * @var Controller
   */
  private $controller;

  public function __construct() {
    $this->controller = new Controller();
  }

  /**
   * Parse arguments from command line and execute actions
   */
  public function process($params) {
    $this->params = $params;
    $command = isset($params[1]) ? $params[1] : '';
    switch ($command) {
      case 'stat':
        $this->stat();
        break;
      case 'start':

      default:
        $this->usage();
    }
  }

  private function stat() {
    $containers = $this->controller->getContainers();
    $namelength = 0;
    foreach ($containers as $container) {
      if (($l = strlen($container->getName())) > $namelength)
        $namelength = $l;
    }
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
        $r['rss'] = $this->formatBytes($memstat['rss']);
        $times = $container->getTimes();
        $r['uptime'] = $this->formatTime($times['uptime']);
        $r['systemtime'] = $this->formatTime($times['system']);
        $r['usertime'] = $this->formatTime($times['user']);
        $netstat = $container->getNetstat();
        if (isset($netstat['ip'])) $r['ip'] = $netstat['ip'];
        if (isset($netstat['upload'])) $r['upload'] = $this->formatBytes($netstat['upload']);
        if (isset($netstat['download'])) $r['download'] = $this->formatBytes($netstat['download']);
      }
      vprintf($FORMAT, $r);
    }
  }

  private function start() {
    
  }

  private function stop() {

  }

  private function create() {

  }

  private function tag() {

  }

  private function usage() {
    echo <<<EOF
  Usage: {$this->params[0]} <command> [options]\n
  Available commands:
    [stat] - show containers statistics
    [start] - start container
    [stop] - stop container

EOF;
  }

  private function formatBytes($bytes) {
    $ext = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    for($unitCount = 0; $bytes > 1024; $unitCount++) $bytes /= 1024;
    return round($bytes,1) . $ext[$unitCount];
  }

  private function formatTime($time) {
    $minute = 60;
    $hour = 3600;
    $day = 86400;
    $ret = '';
    if ($time > $day) {
      $ret .= floor($time / $day) . 'd';
      $time %= $day;
    }
    if ($time > $hour) {
      $ret .= floor($time / $hour) . 'h';
      $time %= $hour;
    }
    if ($time > $minute) {
      $ret .= floor($time / $minute) . 'm';
      $time %= $minute;
    }
    $ret .= $time . 's';
    return $ret;
  }

}
?>

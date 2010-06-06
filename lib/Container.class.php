<?php

namespace ContainerKit;

/**
 * Object that represents single container
 *
 * @author kmad
 */
class Container {
  
  private $cache = array();

  /**
   *
   * @var string
   */
  private $name;

  /**
   *
   * @var Controller
   */
  private $controller;

  private function  __construct($name, Controller $controller) {
    $this->name = $name;
    $this->controller = $controller;
  }


  static public function load($name, Controller $controller) {
    return new Container($name, $controller);
  }

  public function getName() {
    return $this->name;
  }

  public function getState() {
    preg_match(
            "/'(.*)' is (.*)/",
            $this->controller->launchExecutable('lxc-info', "-n $this->name"),
            $matches);
    return $matches[2];
  }

  public function getTimes() {
    preg_match("/(.*?)\..*/", file_get_contents('/proc/uptime'), $matches);
    $uptime = $matches[1];
    if (!isset($this->cache['tasks']))
      $this->cache['tasks'] = file($this->getCgroupEntryPath('tasks'), \FILE_IGNORE_NEW_LINES);
    $initpid = $this->cache['tasks'][0];
    $v = explode(' ', file_get_contents("/proc/$initpid/stat"));
    $times = array('uptime' => $uptime - floor($v[21] / 100));
    $stat = file($this->getCgroupEntryPath('cpuacct.stat'), \FILE_IGNORE_NEW_LINES);
    $user = explode(' ', $stat[0]);
    $system = explode(' ', $stat[1]);
    $times['user'] = floor($user[1] / 100);
    $times['system'] = floor($system[1] / 100);
    return $times;
  }

  public function getMemstat() {
    preg_match("/total_rss (.*)/", $this->getCgroupEntryContent('memory.stat'), $matches);
    $memstat = array('rss' => $matches[1]);
    $memstat['tasks'] = count(isset($this->cache['tasks']) ? $this->cache['tasks'] : ($this->cache['tasks'] = file($this->getCgroupEntryPath('tasks'), \FILE_IGNORE_NEW_LINES)));
    return $memstat;
  }

  public function getNetstat() {
    $ret = array();
    if (file_exists($v = $this->controller->getRoot('storage') . "/$this->name/etc/network/interfaces")) {
      preg_match("/^address\s*(.*)$/mi", file_get_contents($v), $matches);
      if(isset($matches[1]))
        $ret['ip'] = $matches[1];
    }

    $config = file_get_contents($this->controller->getRoot('config') . "/$this->name/config");
    preg_match('/lxc\.network\.veth\.pair.*?=.*?(.*)/', $config, $matches);
    if ($dev = trim($matches[1])) {
//      $v = `ifconfig $matches[1]`;
//      preg_match("/\((.*)\).*\((.*)\)/s", $v, $matches);
//      $ret['upload'] = $matches[1];
//      $ret['download'] = $matches[2];
      $ret['upload'] = trim(file_get_contents("/sys/class/net/$dev/statistics/rx_bytes"));
      $ret['download'] = trim(file_get_contents("/sys/class/net/$dev/statistics/tx_bytes"));
    }

    return $ret;
  }

  /**
   * Get cgroup entry content
   *
   * @param string $name Entry name
   * @return string Entry content
   */
  private function getCgroupEntryContent($name) {
    return file_get_contents($this->getCgroupEntryPath($name));
  }

  /**
   * Get path to cgroup entry
   *
   * @param string $name Entry name
   * @return string Absolute path to cgroup entry
   */
  private function getCgroupEntryPath($name) {
    return $this->controller->getRoot('cgroup') . 
            '/' .
            $this->name .
            '/' .
            $name;
  }

}
?>

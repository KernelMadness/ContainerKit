<?php

namespace ContainerKit\Core;

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
    if (!is_dir($controller->getRoot('storage') . '/' . $name))
      return false;
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
    if (!isset($matches[2]))
      return $this->getState();
    return $matches[2];
  }

  public function getTimes() {
    preg_match("/(.*?)\..*/", file_get_contents('/proc/uptime'), $matches);
    $uptime = $matches[1];
    $initpid = $this->getInitPid();
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

  public function getIp() {
    if (file_exists($v = $this->controller->getRoot('storage') . "/$this->name/etc/network/interfaces")) {
      preg_match("/^address\s*(.*)$/mi", file_get_contents($v), $matches);
      if(isset($matches[1]))
        return $matches[1];
    }
    return false;
  }

  public function getNetstat() {
    $ret = array();
    $ret['ip'] = $this->getIp();

    if ($dev = $this->getVeth()) {
      $ret['upload'] = trim(file_get_contents("/sys/class/net/$dev/statistics/rx_bytes"));
      $ret['download'] = trim(file_get_contents("/sys/class/net/$dev/statistics/tx_bytes"));
    }

    return $ret;
  }

  public function getVeth() {
    $config = file_get_contents($this->controller->getRoot('config') . "/$this->name/config");
    preg_match('/lxc\.network\.veth\.pair.*?=.*?(.*)/', $config, $matches);
    if ($dev = trim($matches[1]))
      return $dev;
    return false;
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
  
  /**
   * Get path to tags file
   * 
   * @return string
   */
  private function getTagsFile() {
    return $this->controller->getRoot('config') . '/' . $this->name . '/tags';
  }

  /**
   * Get array of tags for this container
   *
   * @return array[string]
   */
  public function getTags() {
    if (file_exists($f = $this->getTagsFile()))
      return file($f, \FILE_IGNORE_NEW_LINES);
    else
      return array();
  }

  /**
   * Set tag to this container
   *
   * @param string $tag
   */
  public function setTag($tag) {
    $tags = $this->getTags();
    if (!in_array($tag, $tags)) {
      $tags[] = $tag;
      file_put_contents($this->getTagsFile(), implode("\n", $tags));
    }
  }

  /**
   * Remove tag from this container
   *
   * @param string $tag
   */
  public function removeTag($tag) {
    $tags = $this->getTags();
    if (false !== ($k = array_search($tag, $tags))) {
      unset($tags[$k]);
      file_put_contents($this->getTagsFile(), implode("\n", $tags));
    }
  }

  /**
   * Get container init pid
   *
   * @return int
   */
  public function getInitPid() {
    if (!isset($this->cache['tasks']))
      $this->cache['tasks'] = file($this->getCgroupEntryPath('tasks'), \FILE_IGNORE_NEW_LINES);
    foreach ($this->cache['tasks'] as $pid) {
      $v = file_get_contents('/proc/' . $pid . '/cmdline');
      if (substr($v, 0, 4) == 'init')
        return $pid;
    }
  }

  /**
   * Initiate container stop. Must NOT be used directly
   *
   * @param boolean $hard hard stop
   */
  public function stop($hard = false) {
    if ($hard)
      $this->controller->launchExecutable('lxc-stop', '-n ' . $this->getName());
    else
      posix_kill($this->getInitPid(), \SIGINT);
  }

  /**
   * Initiate container start. Must NOT be used directly
   */
  public function start() {
    if (file_exists($this->getLogPath()))
      unlink($this->getLogPath());
    touch($this->controller->getRoot('storage') . "/{$this->getName()}/fastboot");
    $this->controller->launchExecutable('lxc-start', "-d -o {$this->getLogPath()} -lDEBUG -n {$this->getName()}");
  }

  /**
   * Perform manual remove of veth pair device on host side if needed
   *
   * @return boolean
   */
  public function vethCleanup() {
    $veth = $this->getVeth();
    if (!$veth)
      return false;
    $out = $this->controller->launchExecutable('ip', "link show $veth");
    if (false === strpos($out, 'does not exist')) {
      $this->controller->launchExecutable('ip', 'link del ' . $veth);
      return true;
    }
    return false;
  }

  /**
   * Is container running?
   *
   * @return boolean
   */
  public function isRunning() {
    return ($this->getState() == 'RUNNING') ? true : false;
  }

  /**
   * Is container stopped?
   *
   * @return boolean
   */
  public function isStopped() {
    return ($this->getState() == 'STOPPED') ? true : false;
  }

  /**
   * Get path to log file
   *
   * @return boolean
   */
  public function getLogPath() {
    return $this->controller->getRoot('config') . '/' . $this->getName() . '/lxc.log';
  }

}
?>

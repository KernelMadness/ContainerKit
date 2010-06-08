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

  /**
   *
   * @param string $name
   * @param Controller $controller
   */
  private function  __construct($name, Controller $controller) {
    $this->name = $name;
    $this->controller = $controller;
  }

  /**
   * Load existing container
   *
   * @param string $name Container name
   * @param Controller $controller
   * @return Container
   */
  static public function load($name, Controller $controller) {
    if (!is_dir($controller->getRoot('storage') . '/' . $name))
      return false;
    return new Container($name, $controller);
  }

  /**
   * Get container name
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Get container name, e.g RUNNING, STOPPED, etc
   *
   * @return string state
   */
  public function getState() {
    preg_match(
      "/'(.*)' is (.*)/",
      $this->controller->launchExecutable('lxc-info', "-n $this->name"),
      $matches);
    if (!isset($matches[2]))
      return $this->getState();
    return $matches[2];
  }

  /**
   * Get system and user time consumed by container
   *
   * @return array[string]
   */
  public function getCpuTimes() {
    $times = array();
    $stat = file($this->getCgroupEntryPath('cpuacct.stat'), \FILE_IGNORE_NEW_LINES);
    $user = explode(' ', $stat[0]);
    $system = explode(' ', $stat[1]);
    $times['user'] = floor($user[1] / 100);
    $times['system'] = floor($system[1] / 100);
    return $times;
  }

  /**
   * Get container uptime
   *
   * @return string uptime in seconds
   */
  public function getUptime() {
    preg_match("/(.*?)\..*/", file_get_contents('/proc/uptime'), $matches);
    $uptime = $matches[1];
    $initpid = $this->getInitPid();
    $v = explode(' ', file_get_contents("/proc/$initpid/stat"));
    return ($uptime - floor($v[21] / 100));
  }

  /**
   * Get rss memory size
   *
   * @return string Resident memory size
   */
  public function getRss() {
    preg_match("/total_rss (.*)/", $this->getCgroupEntryContent('memory.stat'), $matches);
    return $matches[1];
  }

  /**
   * Get array of container task PIDs
   *
   * @return array[string] Task PIDs
   */
  public function getTasks() {
    if (!isset($this->cache['tasks']))
      $this->cache['tasks'] = file($this->getCgroupEntryPath('tasks'), \FILE_IGNORE_NEW_LINES);
    return $this->cache['tasks'];
  }

  /**
   * Get primary IP addr
   *
   * @return string IP addr
   */
  public function getIp() {
    $ips = $this->getAllowedIps();
    if (isset ($ips[0]))
      return $ips[0];
    return false;
  }

  /**
   * Get allowed ip addrs
   *
   * @return array[string] array of ip addrs
   */
  public function getAllowedIps() {
    if (file_exists($v = $this->getIpPath()))
      return file($v, \FILE_IGNORE_NEW_LINES);
    return array();
  }

  /**
   * Add IP addr to allowed list
   *
   * @param string $ip
   * @return boolean
   */
  public function addAllowedIp($ip) {
    $ips = $this->getAllowedIps();
    if (!in_array($ip, $ips)) {
      $ips[] = $ip;
      file_put_contents($this->getIpPath(), implode("\n", $ips));
      $this->reloadEbtables();
      return true;
    }
    return false;
  }

  /**
   * Remove IP addr from allowed list
   *
   * @param string $ip
   */
  public function removeAllowedIp($ip) {
    $ips = $this->getAllowedIps();
    if (false !== ($k = array_search($ip, $ips))) {
      unset($ips[$k]);
      file_put_contents($this->getIpPath(), implode("\n", $ips));
      $this->reloadEbtables();
    }
  }

  /**
   * Get traffic stat
   *
   * @return array
   */
  public function getTraffic() {
    $ret = array(
      'upload' => 0,
      'download' => 0,
      );

    if ($dev = $this->getVeth()) {
      $ret['upload'] = trim(file_get_contents("/sys/class/net/$dev/statistics/rx_bytes"));
      $ret['download'] = trim(file_get_contents("/sys/class/net/$dev/statistics/tx_bytes"));
    }

    return $ret;
  }

  /**
   * Get veth device name
   *
   * @return string
   */
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
  private function getTagsPath() {
    return $this->controller->getRoot('config') . '/' . $this->name . '/tags';
  }

  /**
   * Get array of tags for this container
   *
   * @return array[string]
   */
  public function getTags() {
    if (file_exists($f = $this->getTagsPath()))
      return file($f, \FILE_IGNORE_NEW_LINES);
    else
      return array();
  }

  /**
   * Add tag to this container
   *
   * @param string $tag
   */
  public function addTag($tag) {
    $tags = $this->getTags();
    if (!in_array($tag, $tags)) {
      $tags[] = $tag;
      file_put_contents($this->getTagsPath(), implode("\n", $tags));
      return true;
    }
    return false;
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
      file_put_contents($this->getTagsPath(), implode("\n", $tags));
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

  public function getIpPath() {
    return $this->controller->getRoot('config') . '/' . $this->getName() .  '/ip';
  }

  /**
   * Get path to config file
   * 
   * @return string Path to config file
   */
  public function getConfigPath() {
    return $this->controller->getRoot('config') . '/' . $this->name . '/config';
  }

  /**
   * Get path to storage directory
   *
   * @return string
   */
  public function getStorageDir() {
    return $this->controller->getRoot('storage') . '/' . $this->name;
  }

  /**
   * Get path to config directory
   *
   * @return string
   */
  public function getConfigDir() {
    return $this->controller->getRoot('config') . '/' . $this->name;
  }

  public function enableEbtables() {
    $ips = $this->getAllowedIps();
    $veth = $this->getVeth();
    if (!$ips || !$veth)
      return false;
    $chain = 'ck_' . $veth;
    $c = $this->controller;
    $ebtables = function($argline) use ($c) {
      $c->launchExecutable('ebtables', $argline);
    };
    $ebtables('-N ' . $chain);
    $ebtables("-A INPUT -i $veth -j $chain");
    $ebtables("-A $chain -j DROP");
    foreach ($ips as $ip) {
      $ebtables("-I $chain -p IPv4 --ip-src $ip -j RETURN");
      $ebtables("-I $chain -p arp --arp-ip-src $ip -j RETURN");
    }
    return true;
  }

  public function disableEbtables() {
    $ips = $this->getAllowedIps();
    $veth = $this->getVeth();
    if (!$ips || !$veth)
      return false;
    $chain = 'ck_' . $veth;
    $c = $this->controller;
    $ebtables = function($argline) use ($c) {
      $c->launchExecutable('ebtables', $argline);
    };
    $ebtables("-D INPUT -i $veth -j $chain");
    $ebtables('-X ' . $chain);
  }

  public function reloadEbtables() {
    if (($this->getState() !== 'STOPPED') && ($this->controller->getConfig('network', 'filtering') == 'on')) {
      $this->disableEbtables();
      $this->enableEbtables();
    }
  }

}
?>

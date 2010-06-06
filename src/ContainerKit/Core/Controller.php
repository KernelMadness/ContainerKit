<?php

namespace ContainerKit\Core;

/**
 * Collection of Containers
 *
 * @author kmad
 */
class Controller {

  private $config = array();

  private $cache = array();

  public function loadConfig($filename) {
    $this->config = \Symfony\Components\Yaml\Yaml::load($filename);
  }

  /**
   * Get array of containers
   *
   * @return array[Container]
   */
  public function getContainers() {
    $container_names = $this->getContainersList();
    $containers = array();
    foreach ($container_names as $container) {
      $containers[] = $this->getContainer($container);
    }
    return $containers;
  }

  /**
   * Get list of container names
   *
   * @return array[string]
   */
  public function getContainersList() {
    $containers = array();
    $di = new \DirectoryIterator($this->getRoot('storage'));
    foreach ($di as $object) {
      if ($object->isDir() && !$object->isDot()) {
        $filename = $object->getFilename();
        if ($filename[0] != '.') { //Is not hidden
          $containers[] = $filename;
        }
      }
    }
    sort($containers);
    return $containers;
  }

  /**
   *
   * @param string $name
   * @return Container
   */
  public function getContainer($name) {
    return Container::load($name, $this);
  }

  /**
   *
   * @param string $name
   * @return string
   */
  public function getRoot($name) {
    return isset($this->config['roots'][$name]) ? $this->config['roots'][$name] : null;
  }

  /**
   *
   * @param string $name
   * @return string
   */
  public function getExecutable($name) {
    return isset($this->config['executables'][$name]) ? $this->config['executables'][$name] : null;
  }

  /**
   * Launch executable by name
   *
   * @param string $name
   * @param string $params
   * @return string
   */
  public function launchExecutable($name, $params) {
    $ex = $this->getExecutable($name);
    return `$ex $params 2>&1`;
//    $handle = popen("$ex $params", 'r');
//    $ret = '';
//    while ($data = fread($handle, 4096))
//      $ret .= $data;
//    pclose($handle);
//    return $ret;
  }

  /**
   * Get value from controller cache
   *
   * @param string $key
   * @return string
   */
  public function getCache($key) {
    return isset($this->cache[$key]) ? $this->cache[$key] : false;
  }

  /**
   * Put value in controller cache
   *
   * @param string $key
   * @param string $value
   */
  public function setCache($key, $value) {
    $this->cache[$key] = $value;
  }

  /**
   * Select containers by tag or name
   *
   * @param string $selector tag or name
   * @return array[Container]
   */
  public function selectContainers($selector) {
    $ret = array();
    if ($selector[0] == ':') { //Selector is tag
      $tag = substr($selector, 1);
      $containers = $this->getContainers();
      foreach ($containers as $container) {
        if (in_array($tag, $container->getTags()))
          $ret[] = $container;

      }
    } else {
      if (false !== ($c = $this->getContainer($selector)))
        $ret[] = $c;
    }

    return $ret;
  }

  public function stop($selector, $console_callback = null) {
    if ($console_callback == null)
      $console_callback = function() {};
    $containers = $this->selectContainers($selector);
    foreach ($containers as $key => $container) {
      if (!$container->isRunning())
        unset($containers[$key]);
    }
    $threads = array();
    $threads_count = $this->config['general']['threads'];
    while (!empty($containers) || !empty ($threads)) {
      if ((count($threads) < $threads_count) && !empty($containers)) {
        $container = array_shift($containers);
        $threads[$container->getName()] = array(
          'timestamp' => time(),
          'object' => $container,
          );
        $container->stop();
        $console_callback("Stopping {$container->getName()}");
      }
      foreach ($threads as $name => $properties) {
        if ($properties['object']->getState() == 'STOPPED') {
          unset($threads[$name]);
          $console_callback($name . ' stopped');
          if ($properties['object']->vethCleanup())
            $console_callback('Veth device cleanup performed for ' . $properties['object']->getName());
          continue;
        }
        if (($properties['timestamp'] + $this->config['general']['stop_timeout']) < time()) {
          $properties['object']->stop(true);
          unset($threads[$name]);
          $console_callback("Timeout reached while stopping $name. Hard stop used.");
          if ($properties['object']->vethCleanup())
            $console_callback('Veth device cleanup performed for ' . $properties['object']->getName());
        }
      }
      if (!empty($threads))
        usleep(500000);
    }
  }

  public function start($selector, $console_callback = null) {
    if ($console_callback == null)
      $console_callback = function() {};
    $containers = $this->selectContainers($selector);
    foreach ($containers as $key => $container) {
      if (!$container->isStopped())
        unset($containers[$key]);
    }
    $threads = array();
    $threads_count = $this->config['general']['threads'];
    while (!empty($containers) || !empty ($threads)) {
      if ((count($threads) < $threads_count) && !empty($containers)) {
        $container = array_shift($containers);
        $threads[$container->getName()] = array(
          'timestamp' => time(),
          'object' => $container,
          );
        $container->start();
        $console_callback("Starting {$container->getName()}");
      }
      foreach ($threads as $name => $properties) {
        if (false !== strpos(file_get_contents($properties['object']->getLogPath()), 'ERROR')) {
          $console_callback(\Console_Color::convert("%RError occured while starting $name. Check {$properties['object']->getLogPath()}%n"));
          unset($threads[$name]);
          continue;
        }
        if ($properties['object']->getState() == 'RUNNING') {
          unset($threads[$name]);
          $console_callback($name . ' started');
          continue;
        }
        if (($properties['timestamp'] + $this->config['general']['start_timeout']) < time()) {
          unset($threads[$name]);
          $console_callback("Timeout reached while starting $name");
        }
      }
      if (!empty($threads))
        usleep(500000);
    }
  }

  public function restart($selector, $console_callback = null) {
    $this->stop($selector, $console_callback);
    $this->start($selector, $console_callback);
  }

}
?>

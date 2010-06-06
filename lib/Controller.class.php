<?php

namespace ContainerKit;

/**
 * Collection of Containers
 *
 * @author kmad
 */
class Controller {

  private $roots = array(
    'config' => '/var/lib/lxc',
    'cgroup' => '/cgroup',
    'storage' => '/var/containers',
    );

  private $executables = array(
    'lxc-start' => '/usr/bin/lxc-start',
    'lxc-stop' => '/usr/bin/lxc-stop',
    'lxc-info' => '/usr/bin/lxc-info',
  );

  private $cache = array();

  /**
   *
   * @param array $roots
   * @param array $executables
   */
  public function  __construct($roots = null, $executables = null) {
    $this->roots = $roots ?: $this->roots;
    $this->executables = $executables ?: $this->executables;
  }

  /**
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
    return isset($this->roots[$name]) ? $this->roots[$name] : null;
  }

  /**
   *
   * @param string $name
   * @return string
   */
  public function getExecutable($name) {
    return isset($this->executables[$name]) ? $this->executables[$name] : null;
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
    return `$ex $params`;
  }

  public function getCache($key) {
    return isset($this->cache[$key]) ? $this->cache[$key] : false;
  }

  public function setCache($key, $value) {
    $this->cache[$key] = $value;
  }

}
?>

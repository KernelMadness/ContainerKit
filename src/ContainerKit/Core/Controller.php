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

  /**
   * Load configuration from file or array
   *
   * @param string|array $config
   */
  public function loadConfig($filename) {
    if (is_array($filename))
      $this->config = $filename;
    else
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

  /**
   * Stop containers
   *
   * @param string $selector Containers selector
   * @param boolean $hard Just stop container without waiting for processes to finish
   * @param \Closure $console_callback Callback for console output
   */
  public function stop($selector, $hard = false, $console_callback = null) {
    if ($console_callback == null)
      $console_callback = function() {};
    $containers = $this->selectContainers($selector);
    foreach ($containers as $key => $container) {
      if (!$container->isRunning())
        unset($containers[$key]);
    }
    if ($hard) {
      foreach ($containers as $container) {
        $console_callback("Performing hard stop for '{$container->getName()}'");
        $container->stop(true);
      }
      return;
    }
    $threads = array();
    $threads_count = $this->config['general']['stop_threads'];
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

  /**
   * Start containers
   * 
   * @param string $selector Containers selector
   * @param \Closure $console_callback Callback for console output
   */
  public function start($selector, $console_callback = null) {
    if ($console_callback == null)
      $console_callback = function() {};
    $containers = $this->selectContainers($selector);
    foreach ($containers as $key => $container) {
      if (!$container->isStopped())
        unset($containers[$key]);
    }
    $threads = array();
    $threads_count = $this->config['general']['start_threads'];
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

  /**
   * Restart containers
   *
   * @param string $selector Containers selector
   * @param \Closure $console_callback Callback for console output
   */
  public function restart($selector, $console_callback = null) {
    $this->stop($selector, false, $console_callback);
    $this->start($selector, $console_callback);
  }

  /**
   * Create container
   *
   * @param array[string] $params Creation parameters
   * @param \Closure $console_callback Callback for console output
   */
  public function create($params, $console_callback = null) {
    if ($console_callback == null)
      $console_callback = function() {};
    assert_options(\ASSERT_CALLBACK, function() {
      throw new \Exception('Not enough arguments');
    }
    );
    assert_options(\ASSERT_WARNING, 0);
    assert(isset($params['name']));
    assert(isset($params['ip']));
    assert(isset($params['template']));

    $root = $this->getRoot('storage');
    $croot = $root . '/' . $params['name'];

    if (!is_dir("$root/.templates/{$params['template']}"))
      throw new \Exception('Template does not exists');

    $console_callback("Creating root filesystem");
    $this->launchExecutable('cp', "-a $root/.templates/{$params['template']} $root/{$params['name']}");
    $console_callback("Writing configuration files");
    file_put_contents(
      $croot . '/etc/resolv.conf',
      $this->config['network']['resolv.conf']);
    $search = array('%address%', '%netmask%', '%gateway%');
    $replace = array($params['ip'], $this->config['network']['netmask'], $this->config['network']['gateway']);
    file_put_contents(
      $croot . '/etc/network/interfaces',
      str_replace($search, $replace, file_get_contents($croot . '/etc/network/interfaces')));
    file_put_contents(
      $croot . '/etc/hostname',
      str_replace('%name%', $params['name'], $this->config['network']['hostname'])."\n");
    $console_callback("Writing host-side configuration");
    mkdir($this->getRoot('config') . "/{$params['name']}");
    file_put_contents(
      $this->getRoot('config') . "/{$params['name']}/config",
      str_replace('%name%', $params['name'], file_get_contents(\CONFIG_DIR . '/container.config.template')));
    if (isset ($params['tag'])) {
      $container = $this->getContainer($params['name']);
      $container->setTag($params['tag']);
    }
  }

  public function destroy($selector, $console_callback = null) {
    $this->stop($selector, true, $console_callback);
    $containers = $this->selectContainers($selector);
    foreach ($containers as $container) {
      $console_callback("Purging '{$container->getName()}' filesystem");
      $this->launchExecutable('rm', '-rf ' . $this->getRoot('storage') . "/{$container->getName()}");
      $console_callback("Purging '{$container->getName()}' configuration");
      $this->launchExecutable('rm', '-rf ' . $this->getRoot('config') . "/{$container->getName()}");
    }
  }

  /**
   * Find first unused IP in range
   *
   * @return string|boolean
   */
  public function findFreeIp() {
    $ips = array();
    foreach ($this->getContainers() as $container) {
      if ($ip = $container->getIp())
        $ips[] = $ip;
    }
    list($start_ip, $end_ip) = explode('-', $this->config['network']['range']);
    $start_ip = ip2long($start_ip);
    $end_ip = ip2long($end_ip);
    for ($i = $start_ip; $i <= $end_ip; $i++) {
      if (!in_array(long2ip($i), $ips))
        return long2ip($i);
    }
    return false;
  }

  /**
   * Get available container templates
   *
   * @return array[string]
   */
  public function getTemplates() {
    $templates = array();
    $di = new \DirectoryIterator($this->getRoot('storage') . '/.templates');
    foreach ($di as $object) {
      if ($object->isDir() && !$object->isDot()) {
        $filename = $object->getFilename();
        $templates[] = $filename;
      }
    }
    sort($templates);
    return $templates;
  }

  /**
   * Get config parameter
   *
   * @param string $section Config section
   * @param string $key Section key
   * @return string
   */
  public function getConfig($section, $key) {
    return $this->config[$section][$key];
  }

}
?>

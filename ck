#!/usr/bin/php
<?php

const CONFIG_DIR = "/etc/containerkit";
const SRC_DIR = "/usr/share/containerkit";
const CONTAINERKIT_VERSION = '1.0-alpha2';

require SRC_DIR.'/vendor/Symfony/Foundation/UniversalClassLoader.php';

$loader = new Symfony\Foundation\UniversalClassLoader();
$loader->registerNamespaces(array(
  'ContainerKit' =>  SRC_DIR,
  'Symfony' => SRC_DIR.'/vendor',
));

$loader->registerPrefixes(array(
  'Console_' => SRC_DIR.'/vendor',
));

$loader->register();

$controller = new ContainerKit\Core\Controller();
$controller->loadConfig(CONFIG_DIR.'/config.yml');

$application = new ContainerKit\Console\Application($controller);
$application->run();

<?php

namespace ContainerKit\Console;

use Symfony\Components\Console\Application as BaseApplication;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Foundation\Kernel;
use ContainerKit\Core\Controller;

/**
 *
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class Application extends BaseApplication {

  /**
   *
   * @var Controller
   */
  private $controller;

  /**
   * Constructor.
   */
  public function __construct(Controller $controller) {
    $this->controller = $controller;

    parent::__construct('ContainerKit', \CONTAINERKIT_VERSION);

    $this->definition->addOption(new InputOption('--shell', '-s', InputOption::PARAMETER_NONE, 'Launch the shell.'));

    $this->registerCommands();
  }

  /**
   * Runs the current application.
   *
   * @param InputInterface  $input  An Input instance
   * @param OutputInterface $output An Output instance
   *
   * @return integer 0 if everything went fine, or an error code
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    if (true === $input->hasParameterOption(array('--shell', '-s'))) {
      $shell = new Shell($this);
      $shell->run();

      return 0;
    }

    return parent::doRun($input, $output);
  }

  protected function registerCommands() {
    $commandDir = realpath(dirname(__FILE__) . '/../Command');
    foreach (new \DirectoryIterator($commandDir) as $file)
      if ($file->isFile() && !$file->isDot()) {
        $class = 'ContainerKit\\Command\\' . $file->getBasename('.php');
        $this->addCommand(new $class());
      }
  }

  /**
   *
   * @return Controller
   */
  public function getController() {
    return $this->controller;
  }
}

<?php

namespace ContainerKit\Command;

use Symfony\Components\Console\Input\InputArgument;
use Symfony\Components\Console\Input\InputOption;
use Symfony\Components\Console\Input\InputInterface;
use Symfony\Components\Console\Output\OutputInterface;
use Symfony\Components\Console\Output\Output;
use Symfony\Components\Console\Command\Command;

/**
 * Create new container
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class CreateCommand extends Command {
  /**
   * @see Command
   */
  protected function configure() {
    $this
      ->setDefinition(array(
      new InputOption('name', null, InputOption::PARAMETER_OPTIONAL, 'Container name'),
      new InputOption('ip', null, InputOption::PARAMETER_OPTIONAL, 'Container ip'),
      new InputOption('template', null, InputOption::PARAMETER_OPTIONAL, 'Container template'),
      new InputOption('tag', null, InputOption::PARAMETER_OPTIONAL, 'Container tag'),
      ))
      ->setName('create')
      ->setDescription('Create new container')
    ;
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $options = $input->getOptions();

    $ask = function($what, $default = null, $values = array()) {
      while (true) {
        $q = "\n" . $what;
        if ($default !== null)
          $q .= " [$default]: ";
        else {
          $q .= ": ";
        }
        $v = readline($q);
        if (!empty ($values)) {
          if (in_array($v, $values))
            return $v;
        }
        else {
          if ($v !== '')
            return $v;
        }
        if ($default !== null)
          return $default;
      }
    };


    foreach ($options as $key => &$value) {
      if (!$value) {
        switch ($key) {
          case 'ip':
            $ip = $this->application->getController()->findFreeIp();
            if (!$ip)
              throw new \Exception('No free ip left');
            $options[$key] = $ask('IP', $ip);
            break;
          case 'template':
            $default = $this->application->getController()->getConfig('general','default_template');
            $templates = $this->application->getController()->getTemplates();
            $templates_rev = array_flip($templates);
            $what = "Select template\n";
            foreach ($templates as $key => $value) {
              $what .= " $key) $value\n";
            }
            $what .= 'Template';
            $i = (int)$ask($what, $templates_rev[$default], $templates_rev);
            $options[$key] = $templates[$i];
            break;
          case 'name':
            $options[$key] = $ask('Name');
            break;
          default:
            break; 
        }
      }
    }

    $this->application->getController()->create($options, function($message) {
        echo \Console_Color::convert(" %g>>%n ") . $message ."\n";
      });
  }

}

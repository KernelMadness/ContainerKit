<?php

namespace ContainerKit\Console;

/**
 * Tool to launch external interactive programs
 */
class Launcher {

  static public function launch($cmd) {
    $arr_pipes = array();

    $descriptorspec = array(
      0 => array("file", "php://stdin", "r"),   // stdin is a file that the child will read from
      1 => array("file", "php://stdout", "w"),  // stdout is a file that the child will write to
      2 => array("file", "/dev/null", "w")      // stderr is a file that the child will write to
    );

    $process = @proc_open($cmd, $descriptorspec, $arr_pipes);

    if (is_resource($process)) {
      return proc_close($process);
    }
  }

}
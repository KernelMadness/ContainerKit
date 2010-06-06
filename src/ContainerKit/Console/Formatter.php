<?php

namespace ContainerKit\Console;

/**
 * Utils to format output
 *
 * @author kmad
 */
class Formatter {
  
  static public function formatBytes($bytes) {
    $ext = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    for($unitCount = 0; $bytes > 1024; $unitCount++) $bytes /= 1024;
    return round($bytes,1) . $ext[$unitCount];
  }

  static public function formatTime($time) {
    $minute = 60;
    $hour = 3600;
    $day = 86400;
    $ret = '';
    if ($time > $day) {
      $ret .= floor($time / $day) . 'd';
      $time %= $day;
    }
    if ($time > $hour) {
      $ret .= floor($time / $hour) . 'h';
      $time %= $hour;
    }
    if ($time > $minute) {
      $ret .= floor($time / $minute) . 'm';
      $time %= $minute;
    }
    $ret .= $time . 's';
    return $ret;
  }

  static public function calculateNamelength($containers) {
    $namelength = 0;
    foreach ($containers as $container) {
      if (($l = strlen($container->getName())) > $namelength)
        $namelength = $l;
    }
    return $namelength;
  }
}
?>

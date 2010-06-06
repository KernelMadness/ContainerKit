<?php

namespace ContainerKit\Console;

use Symfony\Components\Console\Shell as BaseShell;

/**
 * 
 *
 * @package    ContainerKit
 * @author     Denis.Rizaev <denis.rizaev@trueoffice.ru>
 */
class Shell extends BaseShell
{
  /**
   * Returns the shell header.
   *
   * @return string The header string
   */
  protected function getHeader()
  {
    return <<<EOF
<info>
 _____             _        _                 _   ___ _
/  __ \           | |      (_)               | | / (_) |
| /  \/ ___  _ __ | |_ __ _ _ _ __   ___ _ __| |/ / _| |_
| |    / _ \| '_ \| __/ _` | | '_ \ / _ \ '__|    \| | __|
| \__/\ (_) | | | | || (_| | | | | |  __/ |  | |\  \ | |_
 \____/\___/|_| |_|\__\__,_|_|_| |_|\___|_|  \_| \_/_|\__|



</info>
EOF
    .parent::getHeader();
  }
}

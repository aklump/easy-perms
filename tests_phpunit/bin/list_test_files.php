#!/usr/bin/env php
<?php
/**
 * @file
 * Prints out a list of all files, as a test-writing helper.
 *
 * $1 - "test" to export as a variable, otherwise omit for a list.
 */

use AKlump\EasyPerms\Helpers\GetFileList;

require_once __DIR__ . '/../../vendor/autoload.php';

$base = __DIR__ . '/../files/';
$list = (new GetFileList())("{$base}");
$list = array_map(fn($file) => str_replace($base, '', $file), $list);
$list = array_filter($list);

switch ($argv[1] ?? '') {
  case 'test':
    $list = var_export($list, TRUE);
    $list = preg_replace('/\d+ => /', '', $list);
    $list = str_replace(['array (', ')'], ['[', ']'], $list);
    print $list;
    break;
  default:
    print implode(PHP_EOL, $list);
    break;
}
print PHP_EOL;

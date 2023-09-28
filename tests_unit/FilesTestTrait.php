<?php

namespace AKlump\EasyPerms\Tests;


use Symfony\Component\Filesystem\Filesystem;

trait FilesTestTrait {

  public function resetPermissions() {
    $filesystem = new Filesystem();
    $base_path = $this->getBasePath();
    $filesystem->chmod($base_path, octdec('0777'), 0000, TRUE);
  }

  public function getBasePath(): string {
    return __DIR__ . '/files';
  }

}

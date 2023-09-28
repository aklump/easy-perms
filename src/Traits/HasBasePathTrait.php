<?php

namespace AKlump\EasyPerms\Traits;


use Symfony\Component\Filesystem\Path;

trait HasBasePathTrait {

  private string $basePath;

  public function __construct(string $base_path) {
    $this->setBasePath($base_path);
  }

  public function getBasePath(): string {
    return $this->basePath;
  }

  public function setBasePath(string $base_path): self {
    if (empty($base_path)) {
      throw new \InvalidArgumentException(sprintf('$base_path cannot be empty.'));
    }
    $this->basePath = Path::normalize($base_path);

    return $this;
  }

}

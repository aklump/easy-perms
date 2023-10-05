<?php

namespace AKlump\EasyPerms;

use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface {

  private array $bucket = [];

  /**
   * @inheritDoc
   */
  public function get($key, $default = NULL) {
    return $this->bucket[$key] ?? $default;
  }

  /**
   * @inheritDoc
   */
  public function set($key, $value, $ttl = NULL) {
    $this->bucket[$key] = $value;
  }

  /**
   * @inheritDoc
   */
  public function delete($key) {
    unset($this->bucket[$key]);
  }

  /**
   * @inheritDoc
   */
  public function clear() {
    $this->bucket = [];
  }

  /**
   * @inheritDoc
   */
  public function getMultiple($keys, $default = NULL) {
    $return = $default;
    foreach ($keys as $key) {
      $return[$key] = $this->get($key, $default[$key] ?? NULL);
    }
  }

  /**
   * @inheritDoc
   */
  public function setMultiple($values, $ttl = NULL) {
    foreach ($values as $key => $value) {
      $this->set($key, $value);
    }
  }

  /**
   * @inheritDoc
   */
  public function deleteMultiple($keys) {
    foreach ($keys as $key) {
      $this->delete($key);
    }
  }

  /**
   * @inheritDoc
   */
  public function has($key) {
    return array_key_exists($key, $this->bucket);
  }

}

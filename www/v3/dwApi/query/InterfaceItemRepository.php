<?php
namespace dwApi\query;

/**
 * Interface InterfaceItemRepository
 */
interface InterfaceItemRepository {
  public function read();
  public function single_read();
  public function update();
  public function single_update();
  public function delete();
  public function single_delete();
  public function create();
}
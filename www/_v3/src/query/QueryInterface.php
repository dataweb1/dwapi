<?php
namespace dwApi\query;


/**
 * Interface QueryInterface
 */
interface QueryInterface extends BaseQueryInterface {

  /**
   * Read.
   * @return mixed
   */
  public function read();


  /**
   * Single read.
   * @return mixed
   */
  public function single_read();


  /**
   * Update.
   * @return mixed
   */
  public function update();


  /**
   * Single update.
   * @return mixed
   */
  public function single_update();


  /**
   * Delete.
   * @return mixed
   */
  public function delete();


  /**
   * Single update.
   * @return mixed
   */
  public function single_delete();


  /**
   * Create.
   * @return mixed
   */
  public function create();
}
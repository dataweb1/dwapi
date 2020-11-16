<?php
namespace dwApi\query;

/**
 * Interface InterfaceQueryRepository
 */
interface QueryInterface {

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


  /**
   * getResult.
   * @param null $element
   * @return mixed
   */
  public function getResult($element = NULL);


  /**
   * setResult.
   * @param $element
   * @param $value
   * @return mixed
   */
  public function setResult($element, $value);


  /**
   * getDebug.
   * @return mixed
   */
  public function getDebug();


  /**
   * getEntityType.
   * @return mixed
   */
  public function getEntityType();
}
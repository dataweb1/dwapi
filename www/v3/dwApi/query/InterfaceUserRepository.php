<?php
namespace dwApi\query;

/**
 * Interface InterfaceUserRepository
 */
interface InterfaceUserRepository {
  public function login();
  public function logout($user_id);
}
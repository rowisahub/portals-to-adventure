<?php
namespace PTA\API\REST\utils;

class Constants
{
  public const USER = 'user';
  public const ADMIN = 'admin';
  public const EDITOR = 'editor';
  public const USER_PERMS = [
    self::USER,
    self::ADMIN,
    self::EDITOR
  ];
}
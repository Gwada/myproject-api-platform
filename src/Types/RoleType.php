<?php
namespace App\Types;

final class RoleType
{
    public const ROLE_ADMIN  = 'ROLE_ADMIN';
    public const ROLE_USER   = 'ROLE_USER';

    public static function getRoleForLevel(string $level) : string
    {
        switch ($level) {
            case 'ADMIN': return static::ROLE_ADMIN;
            case 'USER':  return static::ROLE_USER;
            default:
                throw new \Exception(sprintf('Level %s is not supported', $level));
        }
    }

    public static function getLevelForRole(string $role) : string
    {
        switch ($role) {
            case static::ROLE_ADMIN: return 'ADMIN';
            case static::ROLE_USER:  return 'USER';
            default:
                throw new \Exception(sprintf('Role name %s is not supported', $role));
        }
    }
}
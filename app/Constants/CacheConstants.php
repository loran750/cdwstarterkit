<?php

namespace App\Constants;

class CacheConstants
{
    public const USER_IS_ONLINE_PREFIX = 'user-is-online-';

    public static function getCacheKeyForOnlineUser(int | string $userId): string
    {
        return self::USER_IS_ONLINE_PREFIX . $userId;
    }
}

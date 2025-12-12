<?php

namespace App\Services;

use App\Constants\CacheConstants;
use App\Models\User;

class OnlineStatusService
{
    public function isUserOnline(int $userId): bool
    {
        $cacheKey = CacheConstants::getCacheKeyForOnlineUser($userId);
        return cache()->has($cacheKey);
    }

    public static function onlineUsers()
    {
        return User::isOnline();
    }
}

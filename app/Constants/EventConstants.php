<?php

namespace App\Constants;

use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

final class EventConstants
{
    // drop here the events in the system as string constants with the crud operations
    final public const MODELS = [
        User::class,
        Product::class,
        Subscription::class,
        Order::class,
    ];


    final public static function getAllEvents(): array
    {
        foreach (self::MODELS as $modelClass) {
            $modelName = strtolower(class_basename($modelClass));

            $events[$modelName . '.created'] = __(':model Created', ['model' => Str::title($modelName)]);
            $events[$modelName . '.updated'] = __(':model Updated', ['model' => Str::title($modelName)]);
            $events[$modelName . '.deleted'] = __(':model Deleted', ['model' => Str::title($modelName)]);
        }
        return $events;
    }
}

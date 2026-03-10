<?php

use App\Events\OrderCompleted;
use Illuminate\Contracts\Events\Dispatcher;

return function ($app, Dispatcher $events): void {
    $events->listen(OrderCompleted::class, function (OrderCompleted $e): void {
        \Log::info('Plugin example: Order completed', ['order_id' => $e->order->id]);
    });
};

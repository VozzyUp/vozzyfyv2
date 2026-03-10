<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Services\PanelPushService;
use Illuminate\Support\Facades\Log;

class SendPanelPushOnOrderCompleted
{
    public function __construct(
        protected PanelPushService $panelPushService
    ) {}

    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;

        try {
            $productName = $order->product?->name ?? 'Produto';
            $amount = number_format((float) $order->amount, 2, ',', '.');
            $title = 'Nova venda!';
            $body = "{$productName} - R$ {$amount}";
            $url = url('/vendas?order=' . $order->id);

            $this->panelPushService->sendToTenant(
                $order->tenant_id,
                $title,
                $body,
                $url
            );
        } catch (\Throwable $e) {
            Log::warning('SendPanelPushOnOrderCompleted: falha ao enviar push', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

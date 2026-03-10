<?php

namespace App\Listeners;

use App\Events\PixGenerated;
use App\Services\PanelPushService;
use Illuminate\Support\Facades\Log;

class SendPanelPushOnPixGenerated
{
    public function __construct(
        protected PanelPushService $panelPushService
    ) {}

    public function handle(PixGenerated $event): void
    {
        $order = $event->order;

        try {
            $productName = $order->product?->name ?? 'Produto';
            $amount = number_format((float) $order->amount, 2, ',', '.');
            $title = 'PIX gerado!';
            $body = "{$productName} - R$ {$amount} - Aguardando pagamento";
            $url = url('/vendas');

            $this->panelPushService->sendToTenant(
                $order->tenant_id,
                $title,
                $body,
                $url
            );
        } catch (\Throwable $e) {
            Log::warning('SendPanelPushOnPixGenerated: falha ao enviar push', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

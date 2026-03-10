<?php

namespace App\Console\Commands;

use App\Events\CartAbandoned;
use App\Models\CheckoutSession;
use Illuminate\Console\Command;

class FireAbandonedCartWebhooks extends Command
{
    protected $signature = 'checkout:fire-abandoned-cart-webhooks
                            {--hours=2 : Idade mínima em horas para considerar abandonado}
                            {--tenant= : Filtrar por tenant_id (opcional)}';

    protected $description = 'Dispara eventos CartAbandoned para sessões de checkout não convertidas (form_started ou form_filled).';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $tenantId = $this->option('tenant') !== null ? (int) $this->option('tenant') : null;

        $query = CheckoutSession::query()
            ->whereIn('step', [CheckoutSession::STEP_FORM_STARTED, CheckoutSession::STEP_FORM_FILLED])
            ->whereNull('abandoned_webhook_fired_at')
            ->where('created_at', '<=', now()->subHours($hours));

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        $sessions = $query->with('product')->get();
        $count = 0;

        foreach ($sessions as $session) {
            if ($session->tenant_id === null) {
                continue;
            }
            event(new CartAbandoned($session));
            $session->update(['abandoned_webhook_fired_at' => now()]);
            $count++;
        }

        $this->info("CartAbandoned disparado para {$count} sessão(ões).");

        return self::SUCCESS;
    }
}

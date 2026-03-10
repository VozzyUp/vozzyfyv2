<?php

namespace App\Services;

use App\Models\PanelPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Illuminate\Support\Facades\Log;

class PanelPushService
{
    public function sendToTenant(?int $tenantId, string $title, string $body, ?string $url = null): int
    {
        $vapidPublic = config('getfy.pwa.vapid_public');
        $vapidPrivate = config('getfy.pwa.vapid_private');

        if (! $vapidPublic || ! $vapidPrivate) {
            return 0;
        }

        $subscriptions = PanelPushSubscription::where('tenant_id', $tenantId)->get();
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $subject = 'mailto:' . (config('mail.from.address') ?: 'noreply@' . parse_url(config('app.url'), PHP_URL_HOST));
        $auth = [
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ];

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        $sent = 0;
        try {
            $webPush = new WebPush($auth);
            foreach ($subscriptions as $sub) {
                $keys = $sub->keys ?? [];
                $authKey = $keys['auth'] ?? '';
                $p256dh = $keys['p256dh'] ?? '';
                if (! $sub->endpoint || ! $authKey || ! $p256dh) {
                    continue;
                }
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys' => [
                        'auth' => $authKey,
                        'p256dh' => $p256dh,
                    ],
                ]);
                try {
                    $report = $webPush->sendOneNotification($subscription, $payload);
                    if ($report->isSuccess()) {
                        $sent++;
                    } elseif ($report->isSubscriptionExpired()) {
                        $sub->delete();
                    }
                } catch (\Throwable $e) {
                    Log::warning('PanelPushService: falha ao enviar para subscription', [
                        'subscription_id' => $sub->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('PanelPushService: erro ao enviar push', ['message' => $e->getMessage()]);
        }

        return $sent;
    }
}

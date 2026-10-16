<?php

namespace App\Http\Controllers;

use App\Settings\SecretStore;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ResendWebhookController extends Controller
{
    public function __invoke(Request $request, SecretStore $secrets)
    {
        $secret = $secrets->get('resend_webhook_secret');
        abort_unless($secret && str_starts_with($secret, 'whsec_'), 503);
        $id = $request->header('svix-id');
        $time = $request->header('svix-timestamp');
        abort_unless(is_string($id) && strlen($id) < 255 && ctype_digit((string) $time) && abs(time() - (int) $time) <= 300, 401);
        $key = base64_decode(substr($secret, 6), true);
        abort_unless($key, 401);
        $signature = base64_encode(hash_hmac('sha256', $id.'.'.$time.'.'.$request->getContent(), $key, true));
        $valid = false;
        foreach (explode(' ', $request->header('svix-signature', '')) as $candidate) {
            if (str_starts_with($candidate, 'v1,') && hash_equals($signature, substr($candidate, 3))) {
                $valid = true;
            }
        }
        abort_unless($valid, 401);
        $data = $request->validate(['type' => 'required|string', 'created_at' => 'required|date', 'data.email_id' => 'required|string|max:255']);
        DB::transaction(function () use ($id, $data) {
            if (! DB::table('webhook_receipts')->insertOrIgnore(['id' => $id, 'created_at' => now()])) {
                return;
            }
            $status = match ($data['type']) {
                'email.delivered' => 'delivered','email.bounced' => 'bounced','email.complained' => 'complained','email.failed' => 'failed',default => null
            };
            if (! $status) {
                return;
            }
            $time = Carbon::parse($data['created_at'])->utc();
            DB::table('email_logs')->where('provider_id', $data['data']['email_id'])->where(fn ($query) => $query->whereNull('provider_updated_at')->orWhere('provider_updated_at', '<', $time))->update(['status' => $status, 'provider_updated_at' => $time]);
        });

        return response()->noContent();
    }
}

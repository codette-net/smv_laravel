<?php

namespace App\Http\Controllers;

use App\Payments\PaymentGatewayException;
use App\Payments\Stripe\ProcessStripeWebhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, ProcessStripeWebhook $processor): Response
    {
        $secret = trim((string) config('payments.stripe.webhook_secret'));
        if ($secret === '') {
            Log::error('Stripe webhook rejected because no signing secret is configured.');

            return response('Webhook unavailable.', 503);
        }

        $signature = $request->header('Stripe-Signature');
        if (! is_string($signature) || $signature === '') {
            return response('Invalid webhook signature.', 400);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('Invalid webhook payload.', 400);
        }

        try {
            $processor->handle($event);
        } catch (PaymentGatewayException $exception) {
            Log::warning('Stripe webhook reconciliation failed.', [
                'stripe_event_id' => $event->id,
                'stripe_event_type' => $event->type,
                'reason' => $exception->getMessage(),
            ]);

            return response('Webhook could not be reconciled.', 422);
        }

        return response('Received.', 200);
    }
}

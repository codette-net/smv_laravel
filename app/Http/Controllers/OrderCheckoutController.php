<?php

namespace App\Http\Controllers;

use App\Actions\Payments\StartPayment;
use App\Models\Order;
use App\Payments\PaymentGatewayException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class OrderCheckoutController extends Controller
{
    public function show(Order $order): View
    {
        Gate::authorize('pay', $order);

        return view('account.orders.show', [
            'order' => $this->loadOrder($order),
        ]);
    }

    public function start(Request $request, Order $order, StartPayment $startPayment): RedirectResponse
    {
        Gate::authorize('pay', $order);

        try {
            $payment = $startPayment->execute($request->user(), $order);
        } catch (PaymentGatewayException|InvalidArgumentException $exception) {
            report($exception);

            return to_route('account.orders.show', $order)
                ->with('payment_error', 'Betalen kon niet worden gestart. Probeer het later opnieuw of neem contact op met SMV.');
        }

        $redirectUrl = $payment->redirectUrl();
        if ($redirectUrl !== null && $this->isTrustedStripeCheckoutUrl($redirectUrl)) {
            return redirect()->away($redirectUrl);
        }

        return to_route('account.orders.show', $order)
            ->with('payment_status', 'De betalingspoging is gestart. Vernieuw de pagina om de actuele status te bekijken.');
    }

    public function success(Order $order): View
    {
        Gate::authorize('pay', $order);

        return view('account.orders.success', [
            'order' => $this->loadOrder($order),
        ]);
    }

    public function cancelled(Order $order): View
    {
        Gate::authorize('pay', $order);

        return view('account.orders.cancelled', [
            'order' => $this->loadOrder($order),
        ]);
    }

    private function loadOrder(Order $order): Order
    {
        return $order->load([
            'company',
            'orderItems.vacancy',
            'payments' => fn ($query) => $query->latest('id'),
            'operations' => fn ($query) => $query->latest('id'),
        ]);
    }

    private function isTrustedStripeCheckoutUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        return $scheme === 'https'
            && is_string($host)
            && ($host === 'stripe.com' || str_ends_with($host, '.stripe.com'));
    }
}

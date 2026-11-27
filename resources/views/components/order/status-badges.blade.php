@props(['order', 'size' => 'xs'])

@php($paymentStatus = \App\Support\Commerce\OrderPresentation::paymentStatus($order))

<div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    <x-ui.badge :size="$size" :variant="$order->status->badgeVariant()">Bestelling: {{ $order->status->getLabel() }}</x-ui.badge>
    <x-ui.badge :size="$size" :variant="$paymentStatus['variant']">Betaling: {{ $paymentStatus['label'] }}</x-ui.badge>
    @if (\App\Support\Commerce\OrderPresentation::requiresPlacementReview($order))
        <x-ui.badge :size="$size" variant="warning">Plaatsing: controle nodig</x-ui.badge>
    @endif
</div>

<?php

namespace App\Actions\Orders;

use App\Enums\AdvertisingPackage;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Commerce\PackageCatalogue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateOrder
{
    public function __construct(private readonly PackageCatalogue $catalogue) {}

    /**
     * Create an auditable order from the currently offered products.
     *
     * Each item accepts a Package, an AdvertisingPackage, or its stable slug/value,
     * plus an optional Company-owned Vacancy and quantity. VAT defaults to the
     * selected package configuration and may be explicitly overridden for a supported
     * tax treatment.
     *
     * @param  array<int, array{package: Package|AdvertisingPackage|string, vacancy?: Vacancy|null, quantity?: int, vat_rate_basis_points?: int|null}>  $items
     */
    public function execute(
        User $purchaser,
        Company $company,
        array $items,
        ?OrderStatus $status = null,
    ): Order {
        if ($company->user_id !== $purchaser->id) {
            throw new InvalidArgumentException('The purchaser must own the selected company.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('An order must contain at least one item.');
        }

        return DB::transaction(function () use ($purchaser, $company, $items, $status): Order {
            $snapshots = collect($items)->map(fn (array $item): array => $this->snapshotItem($item, $company))->values();

            $currency = $snapshots->pluck('currency')->unique()->sole();
            $subtotal = $snapshots->sum('subtotal_cents');
            $vat = $snapshots->sum('vat_cents');
            $vatRates = $snapshots->pluck('vat_rate_basis_points')->filter(fn ($rate): bool => $rate !== null)->unique();

            $order = Order::create([
                'user_id' => $purchaser->id,
                'company_id' => $company->id,
                'order_number' => $this->uniqueOrderNumber(),
                'status' => $status ?? OrderStatus::Draft,
                'subtotal_cents' => $subtotal,
                'vat_cents' => $vat,
                'total_cents' => $subtotal + $vat,
                'currency' => $currency,
                'vat_rate_basis_points' => $vatRates->count() === 1 ? $vatRates->sole() : null,
            ]);

            foreach ($snapshots as $snapshot) {
                $order->orderItems()->create([
                    'package_id' => $snapshot['package_id'],
                    'vacancy_id' => $snapshot['vacancy_id'],
                    'product_code' => $snapshot['product_code'],
                    'title_snapshot' => $snapshot['title_snapshot'],
                    'price_cents' => $snapshot['price_cents'],
                    'currency' => $snapshot['currency'],
                    'vat_rate_basis_points' => $snapshot['vat_rate_basis_points'],
                    'quantity' => $snapshot['quantity'],
                    'subtotal_cents' => $snapshot['subtotal_cents'],
                    'vat_cents' => $snapshot['vat_cents'],
                    'total_cents' => $snapshot['subtotal_cents'] + $snapshot['vat_cents'],
                ]);
            }

            return $order->load(['orderItems.package', 'orderItems.vacancy', 'company', 'user']);
        });
    }

    /** @param array{package: Package|AdvertisingPackage|string, vacancy?: Vacancy|null, quantity?: int, vat_rate_basis_points?: int|null} $item */
    private function snapshotItem(array $item, Company $company): array
    {
        $product = $this->resolveProduct($item['package'] ?? null);
        $quantity = (int) ($item['quantity'] ?? 1);
        $vatRate = array_key_exists('vat_rate_basis_points', $item)
            ? $item['vat_rate_basis_points']
            : $product['vat_rate_basis_points'];

        if ($quantity < 1) {
            throw new InvalidArgumentException('Order item quantity must be at least one.');
        }

        if ($vatRate !== null && $vatRate < 0) {
            throw new InvalidArgumentException('VAT rate cannot be negative.');
        }

        $vacancy = $item['vacancy'] ?? null;
        if ($vacancy !== null && $vacancy->company_id !== $company->id) {
            throw new InvalidArgumentException('The vacancy must belong to the selected company.');
        }

        $subtotal = $product['price_cents'] * $quantity;
        $vat = $vatRate === null ? 0 : intdiv(($subtotal * $vatRate) + 5000, 10000);

        return [
            'package_id' => $product['package_id'],
            'vacancy_id' => $vacancy?->id,
            'product_code' => $product['product_code'],
            'title_snapshot' => $product['title_snapshot'],
            'price_cents' => $product['price_cents'],
            'currency' => $product['currency'],
            'vat_rate_basis_points' => $vatRate,
            'quantity' => $quantity,
            'subtotal_cents' => $subtotal,
            'vat_cents' => $vat,
        ];
    }

    /** @return array{package_id: int|null, product_code: string, title_snapshot: string, price_cents: int, currency: string, vat_rate_basis_points: int|null} */
    private function resolveProduct(Package|AdvertisingPackage|string|null $product): array
    {
        if ($product instanceof Package) {
            if (! $product->isAvailableForCheckout()) {
                throw new InvalidArgumentException('The selected package is not available for purchase.');
            }

            return [
                'package_id' => $product->exists ? $product->id : null,
                'product_code' => $product->slug,
                'title_snapshot' => $product->name,
                'price_cents' => $product->price_cents,
                'currency' => strtoupper($product->currency),
                'vat_rate_basis_points' => $product->vat_rate_basis_points,
            ];
        }

        $package = $product instanceof AdvertisingPackage
            ? $product
            : AdvertisingPackage::tryFrom((string) $product);

        $catalogPackage = $package !== null
            ? $this->catalogue->find($package)
            : (is_string($product) ? $this->catalogue->find($product) : null);

        if ($catalogPackage === null) {
            throw new InvalidArgumentException('The selected package is not available for purchase.');
        }

        return $this->resolveProduct($catalogPackage);
    }

    private function uniqueOrderNumber(): string
    {
        do {
            $number = 'SMV-'.Str::upper(Str::random(12));
        } while (Order::withTrashed()->where('order_number', $number)->exists());

        return $number;
    }
}

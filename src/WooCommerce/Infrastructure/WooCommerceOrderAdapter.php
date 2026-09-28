<?php

declare(strict_types=1);

namespace Kaanbal\WooCommerce\Infrastructure;

final class WooCommerceOrderAdapter
{
    public function __construct(private readonly object $order)
    {
    }

    public function id(): int
    {
        return (int) $this->order->get_id();
    }

    public function customerId(): int
    {
        return (int) $this->order->get_customer_id();
    }

    /** @return list<array{order_item_id: int, product_id: int}> */
    public function items(): array
    {
        $items = array();

        foreach ($this->order->get_items('line_item') as $order_item_id => $item) {
            $product_id = (int) $item->get_product_id();

            if (0 === $product_id) {
                continue;
            }

            $items[] = array(
                'order_item_id' => (int) $order_item_id,
                'product_id'    => $product_id,
            );
        }

        return $items;
    }

    /** @return list<int> */
    public function productIds(): array
    {
        return array_values(array_unique(array_column($this->items(), 'product_id')));
    }
}

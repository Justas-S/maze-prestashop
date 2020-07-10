<?php

namespace Maze\MazeTv\Message;

use Maze\MazeTv\Repository\MazeStreamerRepository;
use Cart;
use Currency;
use Link;
use Customer;
use Configuration;
use Order;
use Image;
use Product;

class PayloadBuilder
{
    /** @var MazeStreamerRepository */
    private $streamerRepository;

    /**
     * @var string
     */
    private $linkProtocol;

    public function __construct(MazeStreamerRepository $streamerRepository)
    {
        $this->streamerRepository = $streamerRepository;
        $this->linkProtocol = (Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
    }

    /**
     * @see https://trello.com/c/XuF6lzcr/42-tvtwitch-atributikospartneriailt-integracijos-specifikacija
     */
    public function buildMessagesForOrder($orderId): array
    {
        $streamers = $this->streamerRepository->findAll();

        /** @var \Cart */
        $cart = Cart::getCartByOrderId($orderId);
        /** @var array<array> */
        $products = $this->filterByManufacturers($streamers, $cart->getProducts());

        $messages = [];
        foreach ($this->groupProductsByStreamer($streamers, $products) as $streamerKey => $products) {
            $messages[] = [
                'streamerKey' => $streamerKey,
                'orderId' => $orderId,
                'currency' => Currency::getCurrencyInstance((int) $cart->id_currency)->iso_code,
                'orderStatus' => $this->formatOrderStatus($orderId),
                // TODO add cart price
                'products' => array_map(function ($product) use ($cart) {
                    return [
                        'name' => $product['name'],
                        'description' => $product['description_short'],
                        'quantity' => $product['cart_quantity'],
                        'totalPrice' => Product::getPriceStatic($product['id_product'], true, null, 2, null, false, true, $product['cart_quantity'], false, $cart->id_customer, $cart->id),
                        'url' => (new Link())->getProductLink($product['id_product']),
                        'imageUrl' => $this->getProductCoverImageUrl($product['id_product'], $product['link_rewrite'])
                    ];
                }, $products),
                'customer' => [
                    'name' => $this->getCustomerName($cart->id_customer),
                ]
            ];
        }

        return $messages;
    }

    private function getCustomerName($idCustomer)
    {
        $customer = new Customer($idCustomer);
        if ($customer->firstname) {
            return $customer->firstname . ' ' . $customer->lastname;
        }
        return null;
    }

    private function formatOrderStatus($orderId)
    {
        $order = (new Order($orderId));

        switch ($order->getCurrentState()) {
            case Configuration::get('PS_OS_PAYMENT'):
            case Configuration::get('PS_OS_OUTOFSTOCK_PAID'):
                return 'paid';
            case Configuration::get('PS_OS_CANCELED'):
                return 'cancelled';
            default:
                return null;
        }
    }

    private function getProductCoverImageUrl($idProduct, $linkRewrite)
    {

        $link = new Link($this->linkProtocol, $this->linkProtocol);
        $coverImage = Image::getCover($idProduct);
        if ($coverImage && isset($coverImage['id_image'])) {
            return $link->getImageLink($linkRewrite, $coverImage['id_image']);
        }
        return null;
    }

    private function groupProductsByStreamer($streamers, $products)
    {
        $grouped = [];
        foreach ($products as $product) {
            foreach ($streamers as $streamer) {
                if ($streamer->getManufacturerId() == $product['id_manufacturer']) {
                    if (!array_key_exists($streamer->getKey(), $grouped)) {
                        $grouped[$streamer->getKey()] = [];
                    }
                    $grouped[$streamer->getKey()][] = $product;
                }
            }
        }
        return $grouped;
    }

    private function filterByManufacturers($manufacturers, $products)
    {
        // Filter products that are associated with streamers
        return array_filter($products, function ($product) use ($manufacturers) {
            foreach ($manufacturers as $manufacturer) {
                if ($manufacturer->getManufacturerId() == $product['id_manufacturer']) {
                    return true;
                }
            }
            return false;
        });
    }
}

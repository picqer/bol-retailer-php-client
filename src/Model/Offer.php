<?php
namespace Picqer\BolRetailer\Model;

/**
 * @property string    $offerId
 * @property string    $ean
 * @property string    $referenceCode
 * @property bool      $onHoldByRetailer
 * @property string    $unknownProductTitle
 * @property Pricing[] $pricing
 * @property Stock     $stock
 * @property array     $fulfilment
 * @property array     $store
 * @property array     $condition
 * @property array     $notPublishableReasons
 */
class Offer extends AbstractModel
{
    protected function getStock(): ?Stock
    {
        return new Stock($this->data['stock']);
    }

    protected function getPricing(): array
    {
        return array_map(function (array $data) {
            return new Pricing($data);
        }, $this->data['pricing']['bundlePrices'] ?? []);
    }
}

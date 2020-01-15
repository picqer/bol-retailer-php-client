<?php
namespace Picqer\BolRetailer\Tests\Model;

use Picqer\BolRetailer\Model\Offer;
use Picqer\BolRetailer\Model\Stock;
use Picqer\BolRetailer\Model\Pricing;

class OfferTest extends \PHPUnit\Framework\TestCase
{
    private $offer;

    public function setup(): void
    {
        $this->offer = new Offer(
            json_decode(file_get_contents(__DIR__ . '/../Fixtures/json/offer.json'), true)
        );
    }

    public function testContainsStock()
    {
        $this->assertInstanceOf(Stock::class, $this->offer->stock);
    }

    public function testContainsPricing()
    {
        $this->assertIsArray($this->offer->pricing);
        $this->assertCount(1, $this->offer->pricing);
        $this->assertInstanceOf(Pricing::class, $this->offer->pricing[0]);
    }
}

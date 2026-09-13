<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Utils;

use CAMOO\Http\ServerRequest;
use CAMOO\Utils\Cart;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cart::class)]
class CartTest extends TestCase
{
    private ServerRequest $request;

    public function setUp(): void
    {
        try {
            \Camoo\Cache\Cache::config('_camoo_hosting_conf', ['engine' => 'File', 'path' => TMP . 'cache/']);
        } catch (\Throwable) {
            // Already configured
        }
        $this->request = new ServerRequest();
    }

    public function testCartCreateAndBasicOperations(): void
    {
        $cart = Cart::create($this->request);
        $this->assertInstanceOf(Cart::class, $cart);

        $cart->setUserId(123);
        $this->assertSame(0, $cart->count());
        $this->assertEquals(0.00, $cart->getTotalPrice());

        $cart->addItem('item1', ['price' => 10.50, 'name' => 'Book']);
        $this->assertTrue($cart->has('item1'));
        $this->assertSame('Book', $cart->get('item1')['name']);
        $this->assertSame('Book', $cart->item1['name']);
        $this->assertSame(1, $cart->count());
        $this->assertEquals(10.50, $cart->getTotalPrice());

        $items = iterator_to_array($cart->getIterator());
        $this->assertArrayHasKey('item1', $items);

        $cart->refresh(true);
        $cart->addRequest($this->request);

        $cart->removeItem('item1');
        $this->assertFalse($cart->has('item1'));
        $this->assertSame(0, $cart->count());

        $cart->delete();
        $this->assertSame(0, $cart->count());
    }

    public function testCartMultiDimensionalItemAndSleep(): void
    {
        $cart = Cart::create($this->request);
        $cart->delete();

        $cart['multi'] = [
            ['price' => 5.00],
            ['price' => 15.00],
        ];

        $this->assertSame(2, $cart->count());
        $this->assertEquals(20.00, $cart->getTotalPrice());

        $cart[] = ['price' => 7.50];

        $this->assertSame(['count', 'data', 'total_price', 'user'], $cart->__sleep());

        unset($cart['multi']);
        $this->assertSame(1, $cart->count());
    }
}

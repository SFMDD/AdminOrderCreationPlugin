<?php

namespace spec\Sylius\AdminOrderCreationPlugin\Provider;

use Doctrine\Common\Collections\Collection;
use PhpSpec\ObjectBehavior;
use Sylius\AdminOrderCreationPlugin\Entity\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;

final class AvailableShippingMethodsListProviderSpec extends ObjectBehavior
{
    function let(ShippingMethodsResolverInterface $shippingMethodsResolver)
    {
        $this->beConstructedWith($shippingMethodsResolver);
    }

    function it_provides_supported_shipping_methods_list_for_order_shipment(
        ShippingMethodsResolverInterface $shippingMethodsResolver,
        OrderInterface $order,
        Collection $shipmentsCollection,
        ShipmentInterface $shipment,
        ShippingMethodInterface $freeShippingMethod,
        ShippingMethodInterface $dhlShippingMethod
    ) {
        $order->getShipments()->willReturn($shipmentsCollection);
        $shipmentsCollection->get(1)->willReturn($shipment);
        $shippingMethodsResolver->getSupportedMethods($shipment)->willReturn([
            $freeShippingMethod,
            $dhlShippingMethod
        ]);;

        $freeShippingMethod->getCode()->willReturn('FREE');
        $freeShippingMethod->getName()->willReturn('Free');

        $dhlShippingMethod->getCode()->willReturn('DHL');
        $dhlShippingMethod->getName()->willReturn('DHL');

        $this($order, 1)->shouldReturn(['FREE' => 'Free', 'DHL' => 'DHL']);
    }
}

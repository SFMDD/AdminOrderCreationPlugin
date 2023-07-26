<?php

declare(strict_types=1);

namespace Sylius\AdminOrderCreationPlugin\Controller;

use Sylius\AdminOrderCreationPlugin\Factory\OrderFactoryInterface;
use Sylius\AdminOrderCreationPlugin\Form\Type\NewOrderType;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Twig\Environment;

final class OrderPreviewAction
{
    /** @var OrderFactoryInterface */
    private $orderFactory;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var OrderProcessorInterface */
    private $orderProcessor;

    /** @var Environment */
    private $twig;

    public function __construct(
        OrderFactoryInterface $orderFactory,
        FormFactoryInterface $formFactory,
        OrderProcessorInterface $orderProcessor,
        Environment $twig
    ) {
        $this->orderFactory = $orderFactory;
        $this->formFactory = $formFactory;
        $this->orderProcessor = $orderProcessor;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        $customerId = $request->attributes->get('customerId');
        $channelCode = $request->attributes->get('channelCode');

        $order = $this->orderFactory->createForCustomerAndChannel($customerId, $channelCode);

        $form = $this->formFactory->create(NewOrderType::class, $order);
        $form->handleRequest($request)->getData();

        $errors = $form->getErrors(true, true);
        $count = sizeof(array_filter(iterator_to_array($errors), fn(FormError $error) => $error->getMessageTemplate() === 'sylius.cart_item.not_available'));

        if(($form->isSubmitted() && !$form->isValid()) && (!empty($errors) && sizeof($errors) > $count)) {
            return new Response($this->twig->render('@SyliusAdminOrderCreationPlugin/Order/create.html.twig', [
                'form' => $form->createView(),
            ]));
        }

        $order = $form->getData();

        $this->orderProcessor->process($order);
        foreach($order->getItems() as $item) {
            $item->recalculateAdjustmentsTotal();
        }
        $this->orderProcessor->process($order);

        return new Response($this->twig->render('@SyliusAdminOrderCreationPlugin/Order/preview.html.twig', [
            'form' => $form->createView(),
        ]));
    }
}

<?php

namespace CSBill\PaymentBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\PaymentBundle\Entity\Payment;
use CSBill\PaymentBundle\Entity\PaymentDetails;
use CSBill\PaymentBundle\Entity\Status;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Event\PaymentEvents;
use CSBill\PaymentBundle\Repository\PaymentMethod;
use Symfony\Component\HttpFoundation\Request;
use CSBill\PaymentBundle\Action\Request\StatusRequest;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentController extends BaseController
{
    /**
     * @param Request $request
     * @param Invoice $invoice
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function preparePaymentAction(Request $request, Invoice $invoice)
    {
        if ('pending' !== (string) $invoice->getStatus()) {
            throw new \Exception('This invoice cannot be paid');
        }

        $paymentManager = $this->get('csbill_payment.method.manager');

        $builder = $this->createFormBuilder();

        $builder->add(
            'payment_method',
            'entity',
            array(
                'class' => 'CSBillPaymentBundle:PaymentMethod',
                'query_builder' => function (PaymentMethod $repository) {
                    $queryBuilder = $repository->createQueryBuilder('pm');
                    $queryBuilder->where('pm.enabled = 1');

                    return $queryBuilder;
                },
                'required' => true,
                'constraints' => new NotBlank()
            )
        );

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $paymentMethod = $paymentManager->getPaymentMethod($data['payment_method']->getPaymentMethod());

            $paymentName = $paymentMethod->getContext();

            $status = $this
                ->getRepository('CSBillPaymentBundle:Status')
                ->findOneBy(array('name' => Status::STATUS_NEW));

            $payment = new Payment;
            $invoice->addPayment($payment);
            $payment->setInvoice($invoice);
            $payment->setStatus($status);
            $payment->setMethod($data['payment_method']);
            $payment->setAmount($invoice->getTotal());
            $payment->setCurrency($this->container->getParameter('currency'));

            $entityManager = $this->getEm();
            $entityManager->persist($payment);
            $entityManager->flush();

            $captureToken = $this->get('payum.security.token_factory')->createCaptureToken(
                $paymentName,
                $payment->getDetails(),
                '_payments_done' // the route to redirect after capture;
            );

            return $this->redirect($captureToken->getTargetUrl());
        }

        return $this->render(
            'CSBillPaymentBundle:Payment:create.html.twig',
            array(
                'form'    => $form->createView(),
                'invoice' => $invoice
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function captureDoneAction(Request $request)
    {
        $entityManager = $this->getEm();

        /** @var \CSBill\PaymentBundle\Entity\SecurityToken $token */
        $token = $this->get('payum.security.http_request_verifier')->verify($request);

        /** @var \Payum\Core\Payment $payment */
        $payment = $this->get('payum')->getPayment($token->getPaymentName());

        $status = new StatusRequest($token);
        $payment->execute($status);

        $paymentDetails = $status->getModel()->getPayment();

        $paymentDetails->setStatus(
            $this
                ->getRepository('CSBillPaymentBundle:Status')
                ->findOneBy(array('name' => $status->getStatus()))
        );

        $entityManager->persist($paymentDetails);

        /** @var \CSBill\InvoiceBundle\Entity\Invoice $invoice */
        $invoice = $paymentDetails->getInvoice();

        if ($status->isSuccess()) {
            $invoice->setPaidDate(new \DateTime('NOW'));
            $invoice->setStatus(
                $this
                    ->getRepository('CSBillInvoiceBundle:Status')
                    ->findOneBy(array('name' => 'paid'))
            );
            $entityManager->persist($invoice);
            $this->flash('Payment success.', 'success');
        } elseif ($status->isPending()) {
            $this->flash('Payment is still pending.', 'warning');
        } else {
            $this->flash('Payment failed.', 'error');
        }

        $entityManager->flush();

        $event = new PaymentCompleteEvent($paymentDetails);
        $this->get('event_dispatcher')->dispatch(PaymentEvents::PAYMENT_COMPLETE, $event);

        return $this->redirect($this->generateUrl('_invoices_view', array('id' => $invoice->getId())));
    }
}

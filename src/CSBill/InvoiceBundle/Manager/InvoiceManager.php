<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Manager;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Item;
use CSBill\InvoiceBundle\Event\InvoiceEvents;
use CSBill\InvoiceBundle\Event\InvoicePaidEvent;
use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InvoiceManager extends ContainerAware
{
    /**
     * @var RegistryInterface
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param RegistryInterface        $doctrine
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(RegistryInterface $doctrine, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $doctrine;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return \CSBill\InvoiceBundle\Repository\InvoiceRepository
     */
    protected function getInvoiceRepository()
    {
        return $this->entityManager->getRepository('CSBillInvoiceBundle:Invoice');
    }

    /**
     * Create an invoice from a quote
     *
     * @param  Quote   $quote
     * @return Invoice
     */
    public function createFromQuote(Quote $quote)
    {
        $invoice = new Invoice;

        $em = $this->container->get('doctrine')->getManager();

        $metadata = $em->getClassMetadata(get_class($quote));

        $invoiceMetadata = $em->getClassMetadata(get_class($invoice));

        $this->copyFieldValues($quote, $invoice);

        foreach ($metadata->getAssociationNames() as $mappingField) {
            if ('status' === $mappingField) {
                continue;
            }

            if ('items' === $mappingField) {
                $items = $metadata->getFieldValue($quote, $mappingField);

                foreach ($items as $item) {
                    $invoiceItem = new Item;
                    $this->copyFieldValues($item, $invoiceItem);
                    $invoice->addItem($invoiceItem);
                }
            } else {
                $invoiceMetadata->setFieldValue(
                    $invoice,
                    $mappingField,
                    $metadata->getFieldValue($quote, $mappingField)
                );
            }
        }

        $status = $em->getRepository('CSBillInvoiceBundle:Status')->findOneByName('pending');
        $invoice->setStatus($status);

        return $invoice;
    }

    /**
     * @param  null   $status
     * @param  Client $client
     * @return int
     */
    public function getCount($status = null, Client $client = null)
    {
        return $this->getInvoiceRepository()->getCountByStatus($status, $client);
    }

    /**
     * @param  Client $client
     * @return int
     */
    public function getTotalIncome(Client $client = null)
    {
        return $this->getInvoiceRepository()->getTotalIncome($client);
    }

    /**
     * @param  Client $client
     * @return int
     */
    public function getTotalOutstanding(Client $client = null)
    {
        return $this->getInvoiceRepository()->getTotalOutstanding($client);
    }

    /**
     * Copy all the fields from one entity to another
     * @param $object
     * @param $clone
     */
    protected function copyFieldValues($object, $clone)
    {
        $em = $this->container->get('doctrine')->getManager();

        $metadata = $em->getClassMetadata(get_class($object));
        $cloneMetadata = $em->getClassMetadata(get_class($clone));

        foreach ($metadata->getFieldNames() as $field) {
            if ($cloneMetadata->hasField($field)) {
                if (in_array($field, array('created', 'updated', 'id'), true)) {
                    continue;
                }

                $cloneMetadata->setFieldValue($clone, $field, $metadata->getFieldValue($object, $field));
            }
        }
    }

    /**
     * @param Invoice $invoice
     */
    public function markPaid(Invoice $invoice)
    {
        $invoice->setPaidDate(new \DateTime('NOW'));
        $this->setInvoiceStatus($invoice, 'paid');

        $status = $this
            ->entityManager
            ->getRepository('CSBillInvoiceBundle:Status')
            ->findOneBy(array('name' => 'paid'));

        $invoice->setStatus($status);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $event = new InvoicePaidEvent();
        $event->setInvoice($invoice);
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PAID, $event);
    }
}

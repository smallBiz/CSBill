<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use CSBill\InvoiceBundle\Entity\Status;

class LoadStatus implements FixtureInterface
{
    protected $statusList = array( 'draft'     => 'default',
                                   'pending'   => 'warning',
                                   'paid'      => 'success',
                                   'overdue'   => 'danger',
                                   'cancelled' => 'inverse');

    public function load(ObjectManager $manager)
    {
        foreach ($this->statusList as $status => $label) {
            $entity = new Status;
            $entity->setName($status)
                   ->setLabel($label);

            $manager->persist($entity);
        }

        $manager->flush();
    }
}

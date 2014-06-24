<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Request;

interface StepInterface extends ContainerAwareInterface
{
    /**
     * @param  Request $request
     * @return mixed
     */
    public function handleRequest(Request $request);

    /**
     * @return bool
     */
    public function isValid();

    /**
     * @return void
     */
    public function process();

    /**
     * Initializes the current step
     * @return void
     */
    public function init();
}

<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Mailer\Events;

use Swift_Message;

interface MessageEventInterface
{
    /**
     * @return string
     */
    public function getEvent();

    /**
     * @param  Swift_Message $message
     * @return void
     */
    public function setMessage(Swift_Message $message);

    /**
     * @return Swift_Message
     */
    public function getMessage();

    /**
     * @param  string $template
     * @return void
     */
    public function setHtmlTemplate($template);

    /**
     * @return string
     */
    public function getHtmlTemplate();

    /**
     * @param  string $template
     * @return void
     */
    public function setTextTemplate($template);

    /**
     * @return string
     */
    public function getTextTemplate();
}

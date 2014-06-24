<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use CSBill\CoreBundle\Controller\BaseController;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Contact;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxController extends BaseController
{
    /**
     * Get client info
     *
     * @param  Client                                     $client
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(Client $client, $type = 'quote')
    {
        return new JsonResponse(
            array(
                "content" => $this->renderView(
                    'CSBillClientBundle:Ajax:info.html.twig',
                    array(
                        'client' => $client,
                        'type'   => $type
                    )
                )
            )
        );
    }

    /**
     * Add a new contact to a client
     *
     * @param  Request                                                       $request
     * @param  Client                                                        $client
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addcontactAction(Request $request, Client $client)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $contact = new Contact();
        $contact->setClient($client);

        $form = $this->createForm('contact', $contact);

        $response = array();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getEm();

            $entityManager->persist($contact);
            $entityManager->flush();

            return new JsonResponse(array(
                "status"    => "success",
                "content"   => $this->renderView(
                    'CSBillClientBundle:Ajax:contact_add.html.twig',
                    array(
                        'contact' => $contact
                    )
                ),
                "id"        => $contact->getId()
            ));
        } else {
            $response['status'] = 'failure';
        }

        $response["content"] = $this->renderView(
            'CSBillClientBundle:Ajax:contact_add.html.twig',
            array(
                'form' => $form->createView(),
                'client' => $client
            )
        );

        return new JsonResponse($response);
    }

    /**
     * Edits a contact
     *
     * @param  Request                                                       $request
     * @param  Contact                                                       $contact
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editcontactAction(Request $request, Contact $contact)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }

        $originalContactDetails = $contact->getDetails()->toArray();

        $form = $this->createForm('contact', $contact);

        if ($request->isMethod('POST')) {
            $contact->getDetails()->clear();
        }

        $form->handleRequest($request);

        if ($form->isValid()) {

            $entityManager = $this->getEm();

            $this->removeContactDetails($contact, $originalContactDetails);

            $entityManager->persist($contact);
            $entityManager->flush();

            return new JsonResponse(
                array(
                    "content" => $this->renderView(
                        'CSBillClientBundle:Ajax:contact_edit.html.twig',
                        array(
                            'success' => true
                        )
                    ),
                    "status" => "success"
                )
            );
        }

        return new JsonResponse(
            array(
                "content" => $this->renderView(
                    'CSBillClientBundle:Ajax:contact_edit.html.twig',
                    array(
                        'form' => $form->createView(),
                        'contact' => $contact
                    )
                )
            )
        );
    }

    /**
     * Renders a contact card
     *
     * @param  Contact                                    $contact
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactcardAction(Contact $contact)
    {
        return new JsonResponse(
            array(
                'content' => $this->renderView(
                    'CSBillClientBundle::contact_card.html.twig',
                    array(
                        'contact' => $contact
                    )
                )
            )
        );
    }

    /**
     * Deletes a contact
     *
     * @param  Contact      $contact
     * @return JsonResponse
     */
    public function deletecontactAction(Contact $contact)
    {
        $entityMnager = $this->getEm();
        $entityMnager->remove($contact);
        $entityMnager->flush();

        $this->flash($this->trans('contact_delete_success'), 'success');

        return new JsonResponse(array("status" => "success"));
    }

    /**
     * Deletes a client
     *
     * @param  Client       $client
     * @return JsonResponse
     */
    public function deleteclientAction(Client $client)
    {
        $em = $this->getEm();
        $em->remove($client);
        $em->flush();

        $this->flash($this->trans('client_delete_success'), 'success');

        return new JsonResponse(array("status" => "success"));
    }

    /**
     * @param Contact $contact
     * @param array   $originalContactDetails
     */
    private function removeContactDetails(Contact $contact, array $originalContactDetails)
    {
        $em = $this->getEm();

        foreach ($contact->getDetails() as $detail) {
            /** @var \CSBill\ClientBundle\Entity\ContactDetail $detail */
            foreach ($originalContactDetails as $key => $toDel) {
                /** @var \CSBill\ClientBundle\Entity\ContactDetail $toDel */
                if ($toDel->getId() === $detail->getId()) {
                    unset($originalContactDetails[$key]);
                }
            }
        }

        unset($detail);

        foreach ($originalContactDetails as $detail) {
            $contact->removeDetail($detail);
            $em->remove($detail);
        }
    }
}

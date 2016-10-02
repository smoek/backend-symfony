<?php

namespace ApiBundle\Controller;

use ApiBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteResource("Session", pluralize=false)
 */
class SessionController extends BaseController
{
    public function postAction($uuid, Request $request)
    {
        $group = $this->getGroupOr404($uuid);
        if ($group instanceof View){
            return $group;
        }

        $session = new Session();
        $form = $this->createForm('\ApiBundle\Form\SessionType', $session);
        $form->handleRequest($request);

        if ($form->isValid()) {

            $sessionRepository = $this->getDoctrine()->getManager()->getRepository('ApiBundle:Session');

            while (true) {
                $uuid = Uuid::uuid4();

                /* Keep on generating a UUID until no group with that UUID is found */
                $existingSessionWithUuid = $sessionRepository->findOneByUuid($uuid);
                if (!$existingSessionWithUuid) {
                    break;
                }
            }

            $session->setUuid($uuid);
            $session->setGroup($group);
            $session->setCreatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($session);
            $em->flush();

            return View::create($session, 201);
        }
    }

    public function deleteAction($groupUuid, $sessionUuid)
    {
        $group = $this->getGroupOr404($groupUuid);
        if ($group instanceof View) {
            return $group;
        }
        $session = $this->getSessionOr404($sessionUuid, $group);
        if ($session instanceof View) {
            return $session;
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($session);

        /** @var ArrayCollection $currentSessions */
        $currentSessions = $group->getSessions();
        $currentSessions->removeElement($session);
        $group->setSessions($currentSessions);

        $smoekConfirmed = $group->isSmoekVoteSuccessful();
        if ($smoekConfirmed) {
            $group->setSmoekConfirmedAt(new \DateTime());
        }

        $em->flush();

        return View::create([], 200);
    }
}

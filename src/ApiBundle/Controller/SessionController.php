<?php

namespace ApiBundle\Controller;

use ApiBundle\Entity\Group;
use ApiBundle\Entity\Session;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteResource("Session", pluralize=false)
 */
class SessionController extends BaseController
{
    /**
     * The Route needs to be declared explicitly, otherwise the ParamConverter assumes it is matched by ID, not UUID
     * @Route("/group/{uuid}/session")
     *
     * @param Group $group
     * @param Request $request
     * @return Group|\ApiBundle\Entity\Group|View|static
     */
    public function postAction(Group $group, Request $request)
    {
        $session = new Session();

        /* Manually set the form name to null, to get forms like name=smoeks instead of session[name]=smoeks */
        $form = $this->get('form.factory')->createNamedBuilder(null, '\ApiBundle\Form\SessionType', $session)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {

            $uuid = $this->findUniqueUuid();

            $session->setUuid($uuid);
            $session->setGroup($group);
            $session->setCreatedAt(new \DateTime());

            $em = $this->getDoctrine()->getManager();
            $em->persist($session);
            $em->flush();

            return View::create($session, 201);
        }

        /*
         * TODO: There's gotta be a better way to create the error message the way the API requires them. Probably
         * something with FOSRestBundles ExceptionWrapperHandler or FormErrorNormalizer
         */
        $nameErrors = $form->get('name')->getErrors(true);
        if ($nameErrors[0]->getMessage() === 'error.group.already_exists') {
            return View::create([
                'id' => 'error.group.already_exists',
                'message' => sprintf('A group with name \'%s\' already exists.', $group->getName()),
            ], 409);
        }

        return View::create($form, 400);
    }

    public function optionsAction($groupUuid)
    {

    }

    /**
     * @Route("/group/{uuid}/session/{sessionUuid}")
     * @ParamConverter("session", options={"mapping": {"sessionUuid": "uuid"}})
     *
     * @param Group $group
     * @param Session $session
     * @return static
     */
    public function deleteAction(Group $group, Session $session)
    {
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

    /**
     * Generate a UUID for the session. Keeps on generating until a UUID has been generated that does not exist yet.
     *
     * @return string The generated UUID
     */
    private function findUniqueUuid(): string
    {
        $sessionRepository = $this->getDoctrine()->getManager()->getRepository('ApiBundle:Session');

        while (true) {
            $uuid = Uuid::uuid4();

            /* Keep on generating a UUID until no group with that UUID is found */
            $existingSessionWithUuid = $sessionRepository->findOneByUuid($uuid);
            if (!$existingSessionWithUuid) {
                break;
            }
        }
        return $uuid->toString();
    }
}

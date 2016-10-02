<?php

namespace ApiBundle\Controller;

use ApiBundle\Entity\Group;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

class BaseController extends FOSRestController
{

    /**
     * Find the group or exit with a 404
     *
     * TODO: This should be done by the framework
     *
     * @param $uuid The UUID of the group to search
     * @return Group|View The group if found, or a View with an error message otherwise
     */
    function getGroupOr404($uuid)
    {
        $em = $this->getDoctrine()->getManager();

        $groupRepository = $em->getRepository('ApiBundle:Group');
        /** @var Group $group */
        $group = $groupRepository->findOneByUuid($uuid);

        if (!$group) {
            return View::create([
                'id' => 'error.group.not_found',
                'message' => sprintf('A group with UUID \'%s\' does not exist.', $uuid),
            ], 404);
        }

        return $group;
    }

    /**
     * Find the session or exit with 404
     *
     * TODO: This should be done by the framework
     *
     * @param $uuid The UUID of the session to search
     * @param Group $group The group this session must be part of
     * @return Session|View The session if found, or a View with an error message otherwise
     */
    function getSessionOr404($uuid, Group $group)
    {
        $em = $this->getDoctrine()->getManager();

        $sessionRepository = $em->getRepository('ApiBundle:Session');
        /** @var Session $session */
        $session = $sessionRepository->findOneBy([
            'uuid' => $uuid,
            'group' => $group,
        ]);

        if (!$session) {
            return View::create([
                'id' => 'error.session.not_found',
                'message' => sprintf('A session with UUID \'%s\' does not exist.', $uuid),
            ], 404);
        }

        return $session;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: georg
 * Date: 02.10.16
 * Time: 15:51
 */

namespace ApiBundle\Controller;

use ApiBundle\Entity\Group;
use ApiBundle\Entity\Session;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;

/**
 * @RouteResource("Smoek", pluralize=false)
 */
class SmoekController extends BaseController
{
    public function postAction($groupUuid, $sessionUuid)
    {
        $em = $this->getDoctrine()->getManager();

        /* TODO: Getting the group and session from the request should happen automatically */
        $groupRepository = $em->getRepository('ApiBundle:Group');
        /** @var Group $group */
        $group = $groupRepository->findOneByUuid($groupUuid);

        if (!$group) {
            return View::create([
                'id' => 'error.group.not_found',
                'message' => sprintf('A group with UUID \'%s\' does not exist.', $groupUuid),
            ], 404);
        }

        $sessionRepository = $em->getRepository('ApiBundle:Session');
        $session = $sessionRepository->findOneBy([
            'uuid' => $sessionUuid,
            'group' => $group,
        ]);

        if (!$session) {
            return View::create([
                'id' => 'error.session.not_found',
                'message' => sprintf('A session with UUID \'%s\' does not exist.', $sessionUuid),
            ], 404);
        }

        $session->setSmoek(true);

        /*
         * Record the time the Smoek vote was started, if it is not already running
         * TODO: This shouldn't happen in the controller
         */
        $smoekVoteStartedAt = $group->getSmoekRequestedAt();
        if ($smoekVoteStartedAt === null) {
            $smoekVoteStartedAt = new \DateTime();
            $group->setSmoekRequestedAt($smoekVoteStartedAt);
        }

        /* TODO: This shouldn't happen in the controller */
        $smoekConfirmed = $group->isSmoekVoteSuccessful();
        if ($smoekConfirmed) {
            $group->setSmoekConfirmedAt(new \DateTime());
        }

        $em->flush();

        return View::create([], 201);
    }

    public function deleteAction($groupUuid, $sessionUuid)
    {
        $em = $this->getDoctrine()->getManager();

        $groupRepository = $em->getRepository('ApiBundle:Group');
        /** @var Group $group */
        $group = $groupRepository->findOneByUuid($groupUuid);

        if (!$group) {
            return View::create([
                'id' => 'error.group.not_found',
                'message' => sprintf('A group with UUID \'%s\' does not exist.', $groupUuid),
            ], 404);
        }

        $sessionRepository = $em->getRepository('ApiBundle:Session');
        $session = $sessionRepository->findOneBy([
            'uuid' => $sessionUuid,
            'group' => $group,
        ]);

        if (!$session) {
            return View::create([
                'id' => 'error.session.not_found',
                'message' => sprintf('A session with UUID \'%s\' does not exist.', $sessionUuid),
            ], 404);
        }

        if ($group->getSmoekConfirmedAt() !== null) {
            return View::create([], 409);
        }

        $session->setSmoek(false);

        /*
         * Reset the Smoek Vote expiry if there are no more supporters left
         * TODO: This shouldn't happen in the controller
         */
        $supporters = $group->getSupporters();
        if (empty($supporters)) {
            $group->setSmoekRequestedAt(null);
        }

        $group->updateStatus();
        $em->flush();

        return View::create([], 200);
    }
}
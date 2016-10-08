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
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * @RouteResource("Smoek", pluralize=false)
 */
class SmoekController extends BaseController
{
    /**
     * @Route("/group/{uuid}/session/{sessionUuid}/smoek")
     * @ParamConverter("session", options={"mapping": {"sessionUuid": "uuid"}})
     */
    public function postAction(Group $group, Session $session)
    {
        $em = $this->getDoctrine()->getManager();

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

    /**
     * @Route("/group/{uuid}/session/{sessionUuid}/smoek")
     * @ParamConverter("session", options={"mapping": {"sessionUuid": "uuid"}})
     */
    public function deleteAction(Group $group, Session $session)
    {
        $em = $this->getDoctrine()->getManager();

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
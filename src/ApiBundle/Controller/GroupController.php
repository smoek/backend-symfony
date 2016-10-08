<?php

namespace ApiBundle\Controller;

use ApiBundle\Entity\Group;
use ApiBundle\Form\GroupType;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\View;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteResource("Group", pluralize=false)
 */
class GroupController extends BaseController
{
    public function postAction(Request $request)
    {
        return $this->processForm(new Group(), $request);
    }

    private function processForm(Group $group, Request $request)
    {
        /* Manually set the form name to null, to get forms like name=smoeks instead of group[name]=smoeks */
        $form = $this->get('form.factory')->createNamedBuilder(null, '\ApiBundle\Form\GroupType', $group)
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {

            $groupRepository = $this->getDoctrine()->getManager()->getRepository('ApiBundle:Group');

            /* TODO: This should not be in the controller, but at a lower level */
            while (true) {
                $uuid = Uuid::uuid4();

                /* Keep on generating a UUID until no group with that UUID is found */
                $existingGroupWithUuid = $groupRepository->findOneByUuid($uuid);
                if (!$existingGroupWithUuid) {
                    break;
                }
            }

            $group->setUuid($uuid);

            $em = $this->getDoctrine()->getManager();
            $em->persist($group);
            $em->flush();

            return View::create($group, 201);
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

    public function cgetAction()
    {
        $groupRepository = $this->getDoctrine()->getManager()->getRepository('ApiBundle:Group');
        $groups = $groupRepository->findAll();
        $data = $groups;
        $view = $this->view($data, 200)
            ->setTemplate('ApiBundle:Group:get.html.twig');

        return $this->handleView($view);
    }

    /**
     * Get a Form instance.
     *
     * @param Article|null $article
     * @param string|null $routeName
     * @return Form
     */
    protected function getForm($article = null, $routeName = null)
    {
        $options = array();
        if (null !== $routeName) {
            $options['action'] = $this->generateUrl($routeName);
        }
        if (null === $article) {
            $article = new Group();
        }
        return $this->createForm('ApiBundle\Form\GroupType', $article, $options);
    }

    /**
     * @param string $uuid
     * @return static
     */
    public function getAction(string $uuid)
    {
        $group = $this->getGroupOr404($uuid);
        return $group;
    }


}

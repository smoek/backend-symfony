<?php
/**
 * Created by PhpStorm.
 * User: georg
 * Date: 02.10.16
 * Time: 16:12
 */

namespace ApiBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

class SmoekStatus
{
    /**
     * @var bool
     */
    private $requested = false;
    /**
     * @var bool
     */
    private $confirmed = false;
    /**
     * @var Session[]
     */
    private $supporters;
    /**
     * @var \DateTime
     * @Serializer\SerializedName("expiresAt")
     */
    private $expiresAt = null;

    public function __construct(Group $group, \DateTime $expiresAt = null)
    {
        $this->supporters = $group->getSupporters();

        $this->requested = ($group->isSmoekVoteRequested());

        $this->confirmed = ($group->getSmoekConfirmedAt() !== null);

        $this->expiresAt = $expiresAt;
    }
}
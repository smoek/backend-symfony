<?php

namespace ApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinTable;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Group
 *
 * @ORM\Table(name="groupe")
 * @ORM\Entity(repositoryClass="ApiBundle\Repository\GroupRepository")
 * @UniqueEntity(fields={"name"}, message="error.group.already_exists")
 * @ORM\HasLifecycleCallbacks()
 */
class Group
{
    /**
     * How long a Smoek vote lives, starting from the first Smoek request
     */
    const SMOEK_VOTE_TIMEOUT = '10 minutes';

    /**
     * The ratio of supporters to sessions must be greater than this for the Smoek request to succeed
     */
    const SMOEK_QUOROM = 0.5;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Exclude()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false, unique=true)
     * @Serializer\SerializedName("id")
     */
    private $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var Session[]
     *
     * @ORM\OneToMany(targetEntity="Session", mappedBy="group")
     * @JoinTable(name="users_groups")
     */
    private $sessions;

    /**
     * @var SmoekStatus
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="smoek_requested_at", type="datetime", nullable=true)
     * @Serializer\Exclude()
     */
    private $smoekRequestedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="smoek_confirmed_at", type="datetime", nullable=true)
     * @Serializer\Exclude()
     */
    private $smoekConfirmedAt;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set sessions
     *
     * @param Session[] $sessions
     *
     * @return Group
     */
    public function setSessions($sessions)
    {
        $this->sessions = $sessions;

        return $this;
    }

    /**
     * Get sessions
     *
     * @return Session[]
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * Get status
     *
     * @return SmoekStatus
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @ORM\PostLoad()
     */
    public function updateStatus()
    {
        $expiresAt = $this->getSmoekRequestedAt();
        if ($expiresAt !== null) {
            $expiresAt = clone $expiresAt;
            $expiresAt->modify('+' . self::SMOEK_VOTE_TIMEOUT);
        }
        $this->status = new SmoekStatus($this, $expiresAt);
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return Group
     */
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSmoekRequestedAt()
    {
        return $this->smoekRequestedAt;
    }

    /**
     * @param \DateTime $smoekRequestedAt
     * @return Group
     */
    public function setSmoekRequestedAt(\DateTime $smoekRequestedAt = null): Group
    {
        $this->smoekRequestedAt = $smoekRequestedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSmoekConfirmedAt()
    {
        return $this->smoekConfirmedAt;
    }

    /**
     * @param \DateTime $smoekConfirmedAt
     * @return Group
     */
    public function setSmoekConfirmedAt(\DateTime $smoekConfirmedAt = null): Group
    {
        $this->smoekConfirmedAt = $smoekConfirmedAt;

        return $this;
    }

    /**
     * @return Session[]
     */
    public function getSupporters()
    {
        /** @var ArrayCollection $sessions */
        $sessions = $this->getSessions();
        $supporters = array_filter($sessions->toArray(), function(Session $session) {
            return $session->getSmoek();
        });

        return $supporters;
    }

    /**
     * Whether the Smoek quorum is currently fulfilled
     *
     * @return bool
     */
    public function isSmoekVoteSuccessful()
    {
        $supporters = $this->getSupporters();
        $numSupporters = count($supporters);
        $numSessions = count($this->getSessions());
        $isSmoekVoteSuccessful = (($numSessions > 0) && ($numSupporters / $numSessions > Group::SMOEK_QUOROM));

        return $isSmoekVoteSuccessful;
    }

    public function isSmoekVoteRequested()
    {
        $supporters = $this->getSupporters();
        $numSupporters = count($supporters);

        return ($numSupporters > 0);
    }
}


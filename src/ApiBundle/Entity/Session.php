<?php

namespace ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Session
 *
 * @ORM\Table(name="session", uniqueConstraints={
 *     @UniqueConstraint(name="group_sessionname_unique", columns={"group_id", "name"})})
 * @ORM\Entity(repositoryClass="ApiBundle\Repository\SessionRepository")
 * @UniqueEntity(fields={"group", "name"})
 * @ORM\HasLifecycleCallbacks()
 */
class Session
{
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
     * @ORM\Column(name="name", type="string", length=255, unique=false)
     */
    private $name;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="sessions")
     * @ORM\JoinColumn(name="group_id")
     */
    private $group;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false))
     * @Serializer\Exclude()
     */
    private $createdAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="smoek", type="boolean", nullable=false)
     * @Serializer\Exclude()
     */
    private $smoek = false;

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
     * @return Session
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
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
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
     * @return Session
     */
    public function setUuid(string $uuid): Session
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Session
     */
    public function setCreatedAt(\DateTime $createdAt): Session
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Set the creation time and date before persisting
     *
     * @ORM\PrePersist()
     */
    public function initCreatedAt()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * @return mixed
     */
    public function getSmoek()
    {
        return $this->smoek;
    }

    /**
     * @param mixed $smoek
     * @return Session
     */
    public function setSmoek($smoek): Session
    {
        $this->smoek = $smoek;

        return $this;
    }
}


<?php
// src/Message/MessageBundle/Entity/ThreadMetadata.php

namespace Message\MessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Entity\ThreadMetadata as BaseThreadMetadata;

/**
 * @ORM\Entity
 */
class ThreadMetadata extends BaseThreadMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="Message\MessageBundle\Entity\Thread",
     *   inversedBy="metadata"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="\UserManager\Sonata\UserBundle\Entity\User")
     * @var ParticipantInterface
     */
    protected $participant;
}

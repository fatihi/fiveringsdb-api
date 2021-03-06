<?php

namespace AppBundle\Entity;

use Alsciende\SerializerBundle\Annotation\Skizzle;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="labels", indexes={
 *          @ORM\Index(columns={"lang"})
 *     })
 *
 * @Skizzle(break="lang")
 *
 * @author Alsciende <alsciende@icloud.com>
 */
class Label
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=255, unique=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     *
     * @Skizzle\Field(type="string")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, unique=false)
     *
     * @Skizzle\Field(type="string")
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=10, unique=false)
     *
     * @Skizzle\Field(type="string")
     */
    private $lang;

    /**
     * @return string
     */
    public function getId (): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId (string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue (): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setValue (string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getLang (): string
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     *
     * @return $this
     */
    public function setLang (string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }
}

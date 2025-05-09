<?php

/**
 * src/Entity/Asociacion.php
 *
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://www.etsisi.upm.es/ ETS de Ingeniería de Sistemas Informáticos
 */

namespace TDW\ACiencia\Entity;

use DateTime;
use Doctrine\Common\Collections\{ ArrayCollection, Collection };
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionObject;

#[ORM\Entity, ORM\Table(name: "asociaciones")]
#[ORM\UniqueConstraint(name: "Asociacion_name_uindex", columns: [ "name" ])]
class Asociacion extends Element
{
    #[ORM\Column(name:'websiteUrl' ,type: 'string', length: 255, nullable: false)]
    protected string $websiteUrl;

    #[ORM\ManyToMany(targetEntity: Entity::class)]
    #[ORM\JoinTable(name: "entity_belongs_asociacion")]
    #[ORM\JoinColumn(name: "asociacion_id", referencedColumnName: "id")]
    #[ORM\InverseJoinColumn(name: "entity_id", referencedColumnName: "id")]
    protected Collection $entities;

    /**
     * Asociacion constructor.
     *
     * @param non-empty-string $name
     * @param string $websiteUrl
     * @param DateTime|null $birthDate
     * @param DateTime|null $deathDate
     * @param string|null $imageUrl
     * @param string|null $wikiUrl
     */
    public function __construct(
        string $name,
        string $websiteUrl,
        ?DateTime $birthDate = null,
        ?DateTime $deathDate = null,
        ?string $imageUrl = null,
        ?string $wikiUrl = null
    ) {
        parent::__construct($name, $birthDate, $deathDate, $imageUrl, $wikiUrl);
        $this->websiteUrl = $websiteUrl;
        $this->entities = new ArrayCollection();
    }



    public function getWebsiteUrl(): string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(string $websiteUrl): void
    {
        $this->websiteUrl = $websiteUrl;
    }



    /**
     * Gets the entities that belong to the association
     *
     * @return Collection<Entity>
     */
    public function getEntities(): Collection
    {
        return $this->entities;
    }

    /**
     * Indicates whether an entity belongs to this association
     *
     * @param Entity $entity
     *
     * @return bool
     */
    public function containsEntity(Entity $entity): bool
    {
        return $this->entities->contains($entity);
    }

    /**
     * Add an entity to this association
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function addEntity(Entity $entity): void
    {
        if (!$this->containsEntity($entity)) {
            $this->entities->add($entity);
            $entity->addAsociacion($this);
        }
    }

    /**
     * Removes an entity from this association
     *
     * @param Entity $entity
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEntity(Entity $entity): bool
    {
        return $this->entities->removeElement($entity);
    }

    /**
     * @see \Stringable
     */
    public function __toString(): string
    {
        return sprintf(

            '%s entities=%s, websiteUrl=%s)]',
            parent::__toString(),
            $this->getWebsiteUrl(),
            $this->getCodesStr($this->getEntities())
        );
    }

    /**
     * @see \JsonSerializable
     */
    #[ArrayShape(['asociacion' => "array|mixed"])]
    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionObject($this);
        $data = parent::jsonSerialize();
        $data['websiteUrl'] = $this->websiteUrl;
        $numEntities = count($this->getEntities());
        $data['entities'] = $numEntities !== 0 ? $this->getCodes($this->getEntities()) : [];

        return [strtolower($reflection->getShortName()) => $data];
    }
}

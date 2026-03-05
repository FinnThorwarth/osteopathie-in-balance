<?php
declare(strict_types=1);

namespace OsteopathieInBalance\Site\Domain\Repository;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Party\Domain\Model\Person;

/**
 * Repository for Person entities.
 * Required to satisfy Flow's aggregate root consistency rule in the
 * AbstractParty → Person → User entity hierarchy.
 *
 * @Flow\Scope("singleton")
 */
class PersonRepository extends Repository
{
    const ENTITY_CLASSNAME = Person::class;
}

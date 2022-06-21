<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function upsert(Group $entity): void
    {
        /** @var Group $group */
        $group = $this->findBy(['name' => $entity->getName()], null, 1);

        if (isset($group) && !empty($group)) {
            $group[0]->setCountry($entity->getCountry());
            $group[0]->setCity($entity->getCity());
            $group[0]->setStartYear($entity->getStartYear());
            $group[0]->setEndYear($entity->getEndYear());
            $group[0]->setFounders($entity->getFounders());
            $group[0]->setNumberOfMembers($entity->getNumberOfMembers());
            $group[0]->setStyle($entity->getStyle());
            $group[0]->setDescription($entity->getDescription());

            $this->update();
        } else {
            $this->add($entity);
        }
    }

    public function add(Group $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function update(): void
    {
        $this->getEntityManager()->flush();
    }
}

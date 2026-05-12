<?php

namespace App\Services;

use Doctrine\Persistence\ObjectManager;

class FixturesEntityFactory
{
    public function ensureEntity(ObjectManager $manager, string $entityClass, array $search, array $setters)
    {
        $repo = $manager->getRepository($entityClass);
        $entity = $repo->findOneBy($search);

        if (!$entity) {
            $entity = new $entityClass();
        }

        foreach ($setters as $setter => $value) {
            if (isset($setter)) {
                $entity->$setter($value);
            }
        }

        $manager->persist($entity);

        return $entity;
    }
}
<?php

namespace App\Repository;

use App\Entity\Owner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @method Owner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Owner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Owner[]    findAll()
 * @method Owner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OwnerRepository extends ServiceEntityRepository
{
    public const COL_DENUMIRE = '1';

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Owner::class);
    }

    public function saveOwner($formData)
    {
        $owner = new Owner();

        if (isset($formData['owner_id']) && !empty($formData['owner_id'])) {
            $owner = $this->find($formData['owner_id']);
        }

        if(!$owner instanceof Owner) {
            throw new BadRequestHttpException("Missing ID");
        }

        $owner->setDenumire($formData['denumire']);
        $owner->setCui($formData['cui']);
        $owner->setAdresa($formData['adresa']);
        $owner->setBanca($formData['banca']);
        $owner->setContBancar($formData['cont']);
        $owner->setSerieFactura($formData['serie_factura']);
        $owner->setRegCom($formData['reg_com']);
        $owner->setCapitalSocial(!empty($formData['cap_soc']) ? $formData['cap_soc'] : 0);
        $owner->setSters(false);

        try {
            $this->em->persist($owner);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }

        return $owner->getId();
    }

    public function getOwner($id)
    {
        try {
            $owner = $this->createQueryBuilder('owner')
                ->where('owner.id = :id')
                ->setParameter(':id', $id)
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return $owner[0] ?? [];
    }

    public function getAllOwners($filter = null)
    {
        $query = $this->createQueryBuilder('owner');

        $this->applyFilter($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalOwnersByFilter($filter);
            $owners = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return [
            'owners' => $owners,
            'total' => $total
        ];
    }

    public function deleteOwner($ownerId)
    {
        $owner = $this->em->getRepository(Owner::class)->find($ownerId);

        $owner->setSters(true);

        try {
            $this->em->persist($owner);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 5001, $exception);
        }
    }

    private function getTotalOwnersByFilter($filter)
    {
        $totalQuery = $this->createQueryBuilder('owner')->select('COUNT(owner.id)');

        $this->applyFilter($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function applyFilter($query, $filter)
    {
        $params = [':sters' => false];

        $query->where('owner.sters = :sters');

        if (empty($filter['value'])) {
            $query->setParameters($params);

            return $query;
        }

        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->like('owner.denumire', ':filter'),
                $query->expr()->like('owner.cui', ':filter')
            )
        );

        $query->setParameters(array_merge([':filter' => '%'.$filter['value'].'%'], $params));

        return $query;
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_DENUMIRE:
                $query->addOrderBy("owner.denumire", $sort['dir']);
                break;
            default:
                break;
        }
    }
}

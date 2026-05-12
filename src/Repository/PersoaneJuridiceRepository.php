<?php

namespace App\Repository;

use App\Entity\PersoaneJuridice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @extends ServiceEntityRepository<PersoaneJuridice>
 */
class PersoaneJuridiceRepository extends ServiceEntityRepository
{
    public const COL_DENUMIRE = '1';

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, PersoaneJuridice::class);
    }

    public function saveClientPj($formData)
    {
        $pj = new PersoaneJuridice();

        if (isset($formData['pj_id']) && !empty($formData['pj_id'])) {
            $pj = $this->find($formData['pj_id']);
        }

        if(!$pj instanceof PersoaneJuridice) {
            throw new BadRequestHttpException("Missing ID");
        }

        $pj->setDenumire($formData['denumire']);
        $pj->setCui($formData['cui']);
        $pj->setAdresa($formData['adresa']);
        $pj->setSters(false);

        try {
            $this->em->persist($pj);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }

        return $pj->getId();
    }

    public function getClientPj($id)
    {
        try {
            $pj = $this->createQueryBuilder('persoane_juridice')
                ->where('persoane_juridice.id = :id')
                ->setParameter(':id', $id)
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return $pj[0] ?? [];
    }

    public function getAllClientiPj($filter = null)
    {
        $query = $this->createQueryBuilder('persoane_juridice');

        $this->applyFilter($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalClientiPjByFilter($filter);
            $clientiPj = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return [
            'clienti_pj' => $clientiPj,
            'total' => $total
        ];
    }

    public function deleteClientPj($id)
    {
        $pj = $this->em->getRepository(PersoaneJuridice::class)->find($id);

        $pj->setSters(true);

        try {
            $this->em->persist($pj);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 5001, $exception);
        }
    }

    public function getClientiByCui($cui)
    {
        try {
            $query = $this->createQueryBuilder('persoane_juridice');
            $query->select('persoane_juridice.id', "persoane_juridice.denumire")
                ->where($query->expr()->like('persoane_juridice.cui', ':cui'))
                ->setParameter(':cui', '%' . $cui . '%');

            $clienti = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return $clienti ?? [];
    }

    private function getTotalClientiPjByFilter($filter)
    {
        $totalQuery = $this->createQueryBuilder('persoane_juridice')->select('COUNT(persoane_juridice.id)');

        $this->applyFilter($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function applyFilter($query, $filter)
    {
        $params = [':sters' => false];

        $query->where('persoane_juridice.sters = :sters');

        if (empty($filter['value'])) {
            $query->setParameters($params);

            return $query;
        }

        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->like('persoane_juridice.denumire', ':filter'),
                $query->expr()->like('persoane_juridice.cui', ':filter')
            )
        );

        $query->setParameters(array_merge([':filter' => '%'.$filter['value'].'%'], $params));

        return $query;
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_DENUMIRE:
                $query->addOrderBy("persoane_juridice.denumire", $sort['dir']);
                break;
            default:
                break;
        }
    }
}

<?php

namespace App\Repository;

use App\Entity\Owner;
use App\Entity\Preturi;
use App\Entity\Servicii;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @method Preturi|null find($id, $lockMode = null, $lockVersion = null)
 * @method Preturi|null findOneBy(array $criteria, array $orderBy = null)
 * @method Preturi[]    findAll()
 * @method Preturi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PreturiRepository extends ServiceEntityRepository
{
    const COL_DENUMIRE_SERVICIU = "1";
    const COL_DENUMIRE_OWNER = "2";
    const COL_MEDIC = "3";
    const COL_PRET = "4";
    const COL_PROCENTAJ_MEDIC = "5";

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Preturi::class);
    }

    public function getAllPreturi($filter)
    {
        $query = $this->createQueryBuilder('preturi')
            ->select('preturi.id','medic.nume AS numeMedic', 'medic.prenume AS prenumeMedic',
                'serviciu.denumire AS denumireServiciu', 'preturi.pret', 'owner.denumire AS denumireOwner',
                'preturi.procentajMedic')
            ->leftJoin('preturi.medic', 'medic')
            ->leftJoin('preturi.owner', 'owner')
            ->leftJoin('preturi.serviciu', 'serviciu');

        $this->applyFilters($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalPreturi($filter);
            $preturi = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return [
            'servicii_preturi' => $preturi,
            'total' => $total
        ];
    }

    public function savePret($formData)
    {
        $pret = new Preturi();

        if (isset($formData['pret_id']) && !empty($formData['pret_id'])) {
            $pret = $this->find($formData['pret_id']);
        }

        if(!$pret instanceof Preturi) {
            throw new BadRequestHttpException("Missing ID");
        }

        $pret->setMedic($this->em->getRepository(User::class)->find($formData['pret_medic']));
        $pret->setOwner($this->em->getRepository(Owner::class)->find($formData['pret_owner']));
        $pret->setServiciu($this->em->getRepository(Servicii::class)->find($formData['pret_serviciu']));
        $pret->setPret($formData['pret_pret']);
        $pret->setProcentajMedic($formData['pret_procentaj_medic']);
        $pret->setSters(false);
        $pret->setCotaTva(0);

        try {
            $this->em->persist($pret);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }

        return $pret->getId();
    }

    public function getPret($id)
    {
        try {
            return $this->createQueryBuilder('preturi')
                ->select('preturi.id', 'preturi.pret', 'serviciu.id AS serviciuId', 'medic.id AS medicId',
                    'owner.id AS ownerId ', 'preturi.procentajMedic', 'COALESCE(preturi.cotaTva, 0) AS cotaTva')
                ->leftJoin('preturi.serviciu', 'serviciu')
                ->leftJoin('preturi.owner', 'owner')
                ->leftJoin('preturi.medic', 'medic')
                ->where('preturi.id = :id')
                ->setParameter(':id', $id)
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY)[0];
        } catch (\Exception $e) {
            throw new \Exception("Data collection error", 4001, $e);
        }
    }

    public function getMediciForOwner($ownerId)
    {
        $query = $this->createQueryBuilder('preturi')
            ->select('medic.id', 'medic.nume', 'medic.prenume')
            ->innerJoin('preturi.medic', 'medic');

        if (!empty($ownerId)) {
            $query->where('preturi.owner = :owner')
                ->setParameter(':owner', $ownerId);
        }

        try {
            $medici = $query->distinct()
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $e) {
            throw new \Exception("Data collection error", 4001, $e);
        }

        return $medici;
    }

    public function deletePret($pretId)
    {
        $pret = $this->find($pretId);

        $pret->setSters(true);

        try {
            $this->em->persist($pret);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }
    }

    public function getPreturiMedic($medic)
    {
        $query = $this->createQueryBuilder('preturi');
        $query->select('preturi.id', 'serviciu.denumire', 'preturi.pret')
            ->leftJoin('preturi.serviciu', 'serviciu')
            ->where('preturi.medic = :medic')
            ->setParameter(':medic', $medic);

        try {
            $preturi = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return $preturi ?? [];
    }

    private function getTotalPreturi($filter)
    {
        $totalQuery = $this->createQueryBuilder('preturi')
            ->select('COUNT(preturi.id) AS totalPreturi')
            ->leftJoin('preturi.medic', 'medic')
            ->leftJoin('preturi.owner', 'owner')
            ->leftJoin('preturi.serviciu', 'serviciu');

        $this->applyFilters($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function applyFilters($query, $filter)
    {
        $parameters = [];
        $conditions = $query->expr()->andX();

        if (!empty($filter['value'])) {
            $orFilter = $query->expr()->orX(
                $query->expr()->like('medic.nume', ':filter'),
                $query->expr()->like('medic.prenume', ':filter'),
                $query->expr()->like('serviciu.denumire', ':filter'),
                $query->expr()->like('preturi.pret', ':filter'),
                $query->expr()->like('preturi.procentajMedic', ':filter'),
                $query->expr()->like('owner.denumire', ':filter')
            );

            $conditions->add($orFilter);

            $parameters[':filter'] = '%' . $filter['value'] . '%';
        }

        foreach ($filter['propertyFilters'] as $entityFieldValue) {
            $entity = array_key_first($entityFieldValue);
            $field = array_key_first($entityFieldValue[$entity]);
            $value = $entityFieldValue[$entity][$field];

            $entityField = $entity . '.' . $field;
            $paramName = ':' . $entity . '_' . $field;
            $conditions->add($query->expr()->eq($entityField, $paramName));

            $parameters[$paramName] = $value;
        }

        if (!empty($parameters)) {
            $query->where($conditions);

            $query->setParameters($parameters);
        }

        return $query;
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_DENUMIRE_SERVICIU:
                $query->addOrderBy('serviciu.denumire', $sort['dir']);
                break;
            case self::COL_DENUMIRE_OWNER:
                $query->addOrderBy('owner.denumire', $sort['dir']);
                break;
            case self::COL_MEDIC:
                $query->addOrderBy("CONCAT(medic.nume, ' ', medic.prenume)", $sort['dir']);
                break;
            case self::COL_PRET:
                $query->addOrderBy('preturi.pret', $sort['dir']);
                break;
            case self::COL_PROCENTAJ_MEDIC:
                $query->addOrderBy('preturi.procentajMedic', $sort['dir']);
                break;
            default:
                break;
        }
    }
}

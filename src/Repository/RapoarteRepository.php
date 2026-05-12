<?php

namespace App\Repository;

use App\Entity\Consultatii;
use App\Entity\Owner;
use App\Entity\RapoarteColaboratori;
use App\Entity\User;
use App\Services\NomenclatoareService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RapoarteColaboratori|null find($id, $lockMode = null, $lockVersion = null)
 * @method RapoarteColaboratori|null findOneBy(array $criteria, array $orderBy = null)
 * @method RapoarteColaboratori[]    findAll()
 * @method RapoarteColaboratori[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RapoarteRepository extends ServiceEntityRepository
{
    const COL_DATA_GENERARII = "1";
    const COL_MEDIC = "2";
    const COL_OWNER = "3";
    const COL_AN = "4";
    const COL_LUNA = "5";
    const COL_SUMA = "6";
    const COL_STARE = "7";
    const STARE_PLATITA = 'platita';
    const STARE_NEPLATITA = 'neplatita';

    public function __construct(
        ManagerRegistry $registry,
        private EntityManagerInterface $em,
        private NomenclatoareService $service)
    {
        parent::__construct($registry, RapoarteColaboratori::class);
    }

    public function getAllRapoarteColaboratori($filter)
    {
        $query = $this->createQueryBuilder('rapoarte_colaboratori')
            ->select("DATE_FORMAT(rapoarte_colaboratori.dataGenerarii, '%Y-%m-%d') AS dataGenerarii",
                'rapoarte_colaboratori.suma', 'rapoarte_colaboratori.an', 'rapoarte_colaboratori.luna',
                'rapoarte_colaboratori.stare', 'owner.denumire AS denumire_owner', 'rapoarte_colaboratori.id',
                "CONCAT(medic.nume, ' ', medic.prenume) AS nume_medic")
            ->leftJoin('rapoarte_colaboratori.medic', 'medic')
            ->leftJoin('rapoarte_colaboratori.owner', 'owner');

        $this->applyFilters($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalRapoarte($filter);
            $rapoarteColaboratori = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }

        foreach ($rapoarteColaboratori as &$raportColaborator) {
            $raport = $this->em->getRepository(RapoarteColaboratori::class)->find($raportColaborator['id']);
            $consultatiiRaport = $this->em->getRepository(Consultatii::class)->getConsultatiiRaportColaborator($raport);

            $sumaDePlataRaport = 0;

            foreach ($consultatiiRaport as $id) {
                $consultatie = $this->em->getRepository(Consultatii::class)->find($id);

                $sumaDePlataRaport += $consultatie->getTarif() * $consultatie->getPret()->getProcentajMedic() / 100;
            }

            $raportColaborator['sumaDePlata'] = $sumaDePlataRaport;
        }

        return [
            'rapoarteColaboratori' => $rapoarteColaboratori,
            'total' => $total
        ];
    }

    public function saveRaportColaboratori($formData)
    {
        $raportColaboratori = new RapoarteColaboratori();
        $date = new \DateTime();

        $raportColaboratori->setOwner($this->em->getRepository(Owner::class)->find($formData['owner']));
        $raportColaboratori->setMedic($this->em->getRepository(User::class)->find($formData['medic']));
        $raportColaboratori->setStare(self::STARE_NEPLATITA);
        $raportColaboratori->setAn($date->format('Y'));
        $raportColaboratori->setLuna($this->service->getLunileAnului()[intval($date->format('m'))]);
        $raportColaboratori->setDataGenerarii($date);
        $raportColaboratori->setSuma($this->em->getRepository(Consultatii::class)
            ->calculeazaPlataColaborator($formData));

        try {
            $this->em->persist($raportColaboratori);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }

        return $raportColaboratori->getId();
    }

    public function getRaportByFilters($filter)
    {
        $query = $this->createQueryBuilder('rapoarte_colaboratori')
            ->select("DATE_FORMAT(rapoarte_colaboratori.dataGenerarii, '%d-%m-%Y') AS dataGenerarii",
                'rapoarte_colaboratori.suma', 'rapoarte_colaboratori.an', 'rapoarte_colaboratori.luna',
                'rapoarte_colaboratori.stare', 'owner.denumire AS denumire_owner',
                "CONCAT(medic.nume, ' ', medic.prenume) AS nume_medic", 'rapoarte_colaboratori.id')
            ->leftJoin('rapoarte_colaboratori.medic', 'medic')
            ->leftJoin('rapoarte_colaboratori.owner', 'owner')
            ->where('rapoarte_colaboratori.medic = :medicId')
            ->andWhere('rapoarte_colaboratori.owner = :ownerId')
            ->andWhere('rapoarte_colaboratori.luna = :luna')
            ->andWhere('rapoarte_colaboratori.an = :an')
            ->setParameters([
                ':medicId' => $filter['medic'],
                ':ownerId' => $filter['owner'],
                ':luna' => $this->service->getLunileAnului()[intval($filter['luna'])],
                ':an' => $filter['an']
            ]);

        try {
            $raport = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return $raport[0] ?? [];
    }

    public function platesteColaborator($formData)
    {
        $raportColaboratori = $this->find($formData['raport_colaboratori_id']);

        $raportColaboratori->setStare(self::STARE_PLATITA);

        try {
            $this->em->persist($raportColaboratori);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }

        return $raportColaboratori->getId();
    }

    private function applyFilters($query, $filter)
    {
        $parameters = [];
        $conditions = $query->expr()->andX();

        if (!empty($filter['value'])) {
            $orFilter = $query->expr()->orX(
                $query->expr()->like('medic.nume', ':filter'),
                $query->expr()->like('medic.prenume', ':filter'),
                $query->expr()->like('owner.denumire', ':filter'),
                $query->expr()->like("DATE_FORMAT(rapoarte_colaboratori.dataGenerarii, '%d-%m-%Y')", ':filter'),
                $query->expr()->like('rapoarte_colaboratori.suma', ':filter'),
                $query->expr()->like('rapoarte_colaboratori.an', ':filter'),
                $query->expr()->like('rapoarte_colaboratori.luna', ':filter'),
                $query->expr()->like('rapoarte_colaboratori.stare', ':filter')
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

    private function getTotalRapoarte($filter)
    {
        $totalQuery = $this->createQueryBuilder('rapoarte_colaboratori')
            ->select('COUNT(rapoarte_colaboratori.id) AS totalRapoarteColaboratori')
            ->leftJoin('rapoarte_colaboratori.medic', 'medic')
            ->leftJoin('rapoarte_colaboratori.owner', 'owner');

        $this->applyFilters($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_DATA_GENERARII:
                $query->addOrderBy('rapoarte_colaboratori.dataGenerarii', $sort['dir']);
                break;
            case self::COL_MEDIC:
                $query->addOrderBy("CONCAT(medic.nume, ' ', medic.prenume)", $sort['dir']);
                break;
            case self::COL_OWNER:
                $query->addOrderBy('owner.denumire', $sort['dir']);
                break;
            case self::COL_AN:
                $query->addOrderBy('rapoarte_colaboratori.an', $sort['dir']);
                break;
            case self::COL_LUNA:
                $query->addOrderBy('rapoarte_colaboratori.luna', $sort['dir']);
                break;
            case self::COL_SUMA:
                $query->addOrderBy('rapoarte_colaboratori.suma', $sort['dir']);
                break;
            case self::COL_STARE:
                $query->addOrderBy('rapoarte_colaboratori.stare', $sort['dir']);
                break;
            default:
                break;
        }
    }
}

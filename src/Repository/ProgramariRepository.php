<?php

namespace App\Repository;

use App\Entity\Pacienti;
use App\Entity\Preturi;
use App\Entity\Programari;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ProgramariRepository extends ServiceEntityRepository
{
    const COL_PACIENT = "1";
    const COL_MEDIC = "2";
    const COL_DENUMIRE_SERVICIU = "3";
    const COL_PRET = "4";
    const COL_DATA_PROGRAMARE = "5";
    const COL_ORA_PROGRAMARE = "6";
    const COL_STARE = "7";
    const ANULATA = 1;
    const NEANULATA = 0;
    const ONORATA = 1;
    const NEONORATA = 0;
    const VIITOARE = 2;

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Programari::class);
    }

    public function getAllProgramari($filter)
    {
        $query = $this->createQueryBuilder('programari')
            ->select('programari.id',"CONCAT(medic.nume, ' ', medic.prenume) AS numeMedic",
                'serviciu.denumire AS denumireServiciu', 'preturi.pret', 'medic.id AS medicId',
                "CONCAT(pacienti.nume, ' ', pacienti.prenume) AS numePacient", 'pacienti.id AS pacientId',
                "DATE_FORMAT(programari.data, '%d-%m-%Y') AS data", 'programari.data AS dataProgramare',
                "DATE_FORMAT(programari.ora, '%H:%i') AS ora", 'programari.anulata', 'preturi.pret',
                "CASE 
                    WHEN COUNT(consultatii.id) > 0 THEN :onorata 
                    ELSE CASE WHEN
                        COUNT(consultatii.id) = 0 AND CONCAT(programari.data, ' ', programari.ora) < :now
                        THEN :neonorata ELSE :viitoare END      
                END AS stare",)
            ->leftJoin('programari.consultatii', 'consultatii')
            ->leftJoin('programari.pacient', 'pacienti')
            ->leftJoin('programari.pret', 'preturi')
            ->leftJoin('preturi.medic', 'medic')
            ->leftJoin('preturi.serviciu', 'serviciu');

        $this->applyFilters($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        $query->groupBy('programari.id');

        try {
            $total = $this->getTotalProgramari($filter);
            $programari = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return [
            'programari' => $programari,
            'total' => $total
        ];
    }

    public function saveProgramare($formData, $adaugataDe)
    {
        $programare = new Programari();

        if (isset($formData['programare_id']) && !empty($formData['programare_id'])) {
            $programare = $this->em->getRepository(Programari::class)->find($formData['programare_id']);
        }

        if(!$programare instanceof Programari) {
            throw new BadRequestHttpException("Missing ID");
        }

        $pacient = $this->em->getRepository(Pacienti::class)->find($formData['programare_pacient']);
        $pret = $this->em->getRepository(Preturi::class)->find($formData['programare_pret_serviciu']);

        $programare->setPacient($pacient);
        $programare->setPret($pret);
        $data = \DateTime::createFromFormat('d-m-Y', $formData['programare_data']);
        $dataFormatata = $data->format('Y-m-d');
        $programare->setData(new \DateTime($dataFormatata));
        $programare->setOra(\DateTime::createFromFormat('H:i', $formData['programare_ora']));
        $programare->setAnulata(0);
        $programare->setAdaugataDe($adaugataDe);

        try {
            $this->em->persist($programare);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }

        return $programare->getId();
    }

    public function getProgramare($id)
    {
        try {
            $programare = $this->createQueryBuilder('programari')
                ->select('programari.id', "DATE_FORMAT(programari.data, '%d-%m-%Y') AS data",
                    "DATE_FORMAT(programari.ora, '%H:%i') AS ora", 'programari.anulata', 'pacienti.cnp',
                    'preturi.pret', 'medic.id AS medicId', 'serviciu.id AS serviciuId', 'preturi.id AS pretId')
                ->leftJoin('programari.pacient', 'pacienti')
                ->leftJoin('programari.pret', 'preturi')
                ->leftJoin('preturi.serviciu', 'serviciu')
                ->leftJoin('preturi.medic', 'medic')
                ->where('programari.id = :id')
                ->andWhere('programari.anulata = :anulata')
                ->setParameters([':id' =>  $id, ':anulata' => self::NEANULATA])
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return $programare[0] ?? [];
    }

    public function cancelProgramare($programareId)
    {
        $programare = $this->em->getRepository(Programari::class)->find($programareId);

        $programare->setAnulata(self::ANULATA);

        try {
            $this->em->persist($programare);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 5001, $exception);
        }
    }

    public function checkAvailability($formData)
    {
        $date = \DateTime::createFromFormat('d-m-Y', $formData['programare_data']);
        $data = $date->format('Y-m-d');
        $time = \DateTime::createFromFormat('G:i', $formData['programare_ora']);
        $ora = $time->format('H:i:s');

        try {
            $programare = $this->createQueryBuilder('programari')
                ->select('COUNT(programari.id)')
                ->leftJoin('programari.pret', 'preturi')
                ->leftJoin('preturi.medic', 'medic')
                ->where('medic.id = :medic')
                ->andWhere('programari.data = :data')
                ->andWhere('programari.ora = :ora')
                ->andWhere('programari.anulata = :anulata')
                ->setParameters([
                    ':medic' => $formData['programare_medic'],
                    ':data' => $data,
                    ':ora' => $ora,
                    ':anulata' => self::NEANULATA,
                ])
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return $programare === 0;
    }

    private function getTotalProgramari($filter)
    {
        $totalQuery = $this->createQueryBuilder('programari')
            ->select('COUNT(programari.id) AS totalProgramari')
            ->leftJoin('programari.pacient', 'pacienti')
            ->leftJoin('programari.pret', 'preturi')
            ->leftJoin('preturi.medic', 'medic')
            ->leftJoin('preturi.serviciu', 'serviciu');

        $this->applyFilters($totalQuery, $filter, true);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function applyFilters($query, $filter, $isTotal = false)
    {
        $parameters = !$isTotal ? [
            ':now' => (new \DateTime())->format('Y-m-d H:i:s'),
            ':onorata' => self::ONORATA,
            ':neonorata' => self::NEONORATA,
            ':viitoare' => self::VIITOARE]
            : [];

        $conditions = $query->expr()->andX();

        if (!empty($filter['value'])) {
            $orFilter = $query->expr()->orX(
                $query->expr()->like('medic.nume', ':filter'),
                $query->expr()->like('medic.prenume', ':filter'),
                $query->expr()->like('serviciu.denumire', ':filter'),
                $query->expr()->like('pacienti.nume', ':filter'),
                $query->expr()->like('pacienti.prenume', ':filter'),
                $query->expr()->like('programari.data', ':filter'),
                $query->expr()->like('programari.ora', ':filter')
            );

            $parameters[':filter'] = '%' . $filter['value'] . '%';

            $conditions->add($orFilter);
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
            case self::COL_PACIENT:
                $query->addOrderBy("CONCAT(pacienti.nume, ' ', pacienti.prenume)", $sort['dir']);
                break;
            case self::COL_MEDIC:
                $query->addOrderBy("CONCAT(medic.nume, ' ', medic.prenume)", $sort['dir']);
                break;
            case self::COL_DENUMIRE_SERVICIU:
                $query->addOrderBy('serviciu.denumire', $sort['dir']);
                break;
            case self::COL_PRET:
                $query->addOrderBy('preturi.pret', $sort['dir']);
                break;
            case self::COL_DATA_PROGRAMARE:
                $query->addOrderBy('programari.data', $sort['dir']);
                break;
            case self::COL_ORA_PROGRAMARE:
                $query->addOrderBy('programari.ora', $sort['dir']);
                break;
            case self::COL_STARE:
                $query->addOrderBy('programari.anulata', $sort['dir']);
                break;
            default:
                break;
        }
    }
}

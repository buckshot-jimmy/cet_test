<?php

namespace App\Repository;

use App\Entity\Pacient;
use App\Services\UtilService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Pacient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pacient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pacient[]    findAll()
 * @method Pacient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PacientRepository extends ServiceEntityRepository
{
    public const COL_NUME = '1';
    public const COL_TARA = '2';
    public const COL_CNP = '3';
    public const COL_VARSTA = '6';
    public const COL_JUDET = '7';
    public const COL_LOCALITATE = '8';
    public const COL_TELEFON = '10';
    public const COL_TELEFON2 = '11';
    public const COL_EMAIL = '12';
    public const COL_OCUPATIE = '13';
    public const COL_LOC_MUNCA = '14';
    public const COL_DATA_INREG = '15';

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Pacient::class);
    }

    public function getAllPacienti($filter)
    {
        $query = $this->createQueryBuilder('pacienti')
            ->select('pacienti.id', 'pacienti.telefon', 'pacienti.prenume', 'pacienti.adresa', 'pacienti.nume',
                'pacienti.cnp', 'pacienti.judet', 'pacienti.ci', 'pacienti.email', 'pacienti.telefon2',
                'pacienti.localitate', 'pacienti.observatii', 'pacienti.tara', 'pacienti.ocupatie', 'pacienti.locMunca',
                "DATE_FORMAT(pacienti.dataInreg, '%d-%m-%Y') AS dataInreg", 'pacienti.ciEliberat');

        $this->applyFilter($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalPacienti($filter);
            $pacienti = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 4001, $exception);
        }

        foreach ($pacienti as &$pacient) {
            $datePacient = UtilService::calculeazaDatePacient($pacient['cnp']);
            $pacient['varsta'] = $datePacient['varsta'];
            $pacient['sex'] = $datePacient['sex'];
            $pacient['dataNasterii'] = $datePacient['dataNasterii'];

            $pacient['areConsultatiiDeschise'] = $this->em->getRepository(Pacient::class)
                ->pacientAreConsultatiiDeschise($pacient['id']);
        }

        if (self::COL_VARSTA === $filter['sort']['column']) {
            $varste = array_column($pacienti, 'varsta');
            array_multisort($varste, 'desc' === $filter['sort']['dir'] ? SORT_DESC : SORT_ASC, $pacienti);
        }

        return [
            'pacienti' => $pacienti,
            'total' => $total,
        ];
    }

    public function getAllPacientiCuConsultatii($filter)
    {
        $query = $this->createQueryBuilder('pacienti')
            ->select('pacienti.id', 'pacienti.telefon', 'pacienti.prenume', 'pacienti.adresa', 'pacienti.nume',
                'pacienti.cnp', 'pacienti.judet', 'pacienti.ci', 'pacienti.email', 'pacienti.telefon2',
                'pacienti.localitate', 'pacienti.observatii', 'pacienti.tara', 'pacienti.ocupatie', 'pacienti.locMunca',
                "DATE_FORMAT(pacienti.dataInreg, '%d-%m-%Y') AS dataInreg", 'pacienti.ciEliberat')
            ->innerJoin('pacienti.consultatii', 'consultatii')
            ->groupBy('pacienti.id');

        $this->applyFilter($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalPacientiCuConsultatii($filter);
            $pacienti = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 4001, $exception);
        }

        foreach ($pacienti as &$pacient) {
            $datePacient = UtilService::calculeazaDatePacient($pacient['cnp']);
            $pacient['varsta'] = $datePacient['varsta'];
            $pacient['sex'] = $datePacient['sex'];
            $pacient['dataNasterii'] = $datePacient['dataNasterii'];
        }

        if (self::COL_VARSTA === $filter['sort']['column']) {
            $varste = array_column($pacienti, 'varsta');
            array_multisort($varste, 'desc' === $filter['sort']['dir'] ? SORT_DESC : SORT_ASC, $pacienti);
        }

        return [
            'pacienti' => $pacienti,
            'total' => $total,
        ];
    }

    public function savePacient(Pacient $pacient, $adaugatDe)
    {
        $pacient->setDataInreg(new \DateTime());
        $pacient->setSters(false);
        $pacient->setJudet($pacient->getJudet() ?: '---');
        $pacient->setAdaugatDe($adaugatDe);

        try {
            $this->em->persist($pacient);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 4001, $exception);
        }

        return $pacient->getId();
    }

    public function getPacient($id)
    {
        try {
            $pacient = $this->createQueryBuilder('pacienti')
                ->where('pacienti.id = :id')
                ->setParameter(':id', $id)
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        if ($pacient && $pacient[0]) {
            $pacient[0] = array_merge($pacient[0], UtilService::calculeazaDatePacient($pacient[0]['cnp']));
        }

        return $pacient[0] ?? [];
    }

    public function deletePacient($pacientId)
    {
        $pacient = $this->em->getRepository(Pacient::class)->find($pacientId);

        $pacient->setSters(true);

        try {
            $this->em->persist($pacient);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 5001, $exception);
        }
    }

    public function getPacientiInCabinet($filter)
    {
        $query = $this->createQueryBuilder('pacienti')
            ->select('pacienti.id', 'pacienti.telefon', 'pacienti.prenume', 'pacienti.adresa', 'pacienti.nume',
                'pacienti.cnp', 'pacienti.judet', 'pacienti.tara', 'pacienti.localitate',
                'pacienti.email', "DATE_FORMAT(consultatii.dataConsultatie, '%d-%m-%Y') AS dataPrezentarii")
            ->leftJoin('pacienti.consultatii', 'consultatii')
            ->leftJoin('consultatii.pret', 'pret')
            ->leftJoin('pret.medic', 'medic');

        $this->applyFilterPacientiInCabinet($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSortPacientiInCabinet($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalPacientiInCabinet($filter);
            $pacientiInCabinet = $query->distinct()->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4041, $exception);
        }

        foreach ($pacientiInCabinet as &$pacient) {
            $pacient['areConsultatiiDeschise'] = $this->pacientAreConsultatiiDeschise($pacient['id']);
            $pacient['areConsultatiiNeplatite'] = $this->pacientAreConsultatiiNeplatite($pacient['id']);

            $pacient['dataPrezentariiJs'] = date('Y-m-d H:i:s', strtotime($pacient['dataPrezentarii']));
        }

        return [
            'pacienti' => $pacientiInCabinet,
            'total' => $total,
        ];
    }

    public function getPacientiByCnp($cnp)
    {
        try {
            $query = $this->createQueryBuilder('pacienti');
            $query->select('pacienti.id', "CONCAT(pacienti.nume, ' ', pacienti.prenume) AS numePacient")
                ->where($query->expr()->like('pacienti.cnp', ':cnp'))
                ->setParameter(':cnp', '%'.$cnp.'%');

            $pacienti = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return $pacienti ?? [];
    }

    public function getPacientiConsultatiiNefacturate($filter)
    {
        $query = $this->createQueryBuilder('pacienti');
        $query->select('DISTINCT pacienti.id', "CONCAT(pacienti.nume, ' ', pacienti.prenume) AS pacient",
                'pacienti.cnp', 'SUM(consultatii.tarif) AS total')
            ->innerJoin('pacienti.consultatii', 'consultatii')
            ->andWhere('consultatii.inchisa = :inchisa')
            ->andWhere('consultatii.stearsa = :stearsa')
            ->andWhere('consultatii.incasata = :incasata')
            ->andWhere('consultatii.facturata = :facturata')
            ->groupBy('pacienti.id');

        $this->applyFilterNefacturate($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalPacientiConsultatiiNefacturate($filter);
            $pacienti = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return [
            'pacienti' => $pacienti,
            'total' => $total,
        ];
    }

    private function getTotalPacientiConsultatiiNefacturate($filter)
    {
        $query = $this->createQueryBuilder('pacienti');
        $query->select('COUNT(DISTINCT pacienti.id)')
            ->innerJoin('pacienti.consultatii', 'consultatii')
            ->andWhere('consultatii.inchisa = :inchisa')
            ->andWhere('consultatii.stearsa = :stearsa')
            ->andWhere('consultatii.incasata = :incasata')
            ->andWhere('consultatii.facturata = :facturata');

        $this->applyFilterNefacturate($query, $filter);

        return $query->getQuery()->getSingleScalarResult();
    }

    private function applyFilterNefacturate($query, $filter)
    {
        $params = [
            ':inchisa' => true,
            ':stearsa' => false,
            ':incasata' => true,
            ':facturata' => false,
        ];

        if (!empty($filter['value'])) {
            $query->andWhere(
                $query->expr()->orX(
                    $query->expr()->like('pacienti.prenume', ':filter'),
                    $query->expr()->like('pacienti.nume', ':filter'),
                    $query->expr()->like('pacienti.cnp', ':filter'),
                )
            );

            $params[':filter'] = '%'.$filter['value'].'%';
        }

        $query->setParameters($params);

        return $query;
    }

    private function getTotalPacienti($filter)
    {
        $totalQuery = $this->createQueryBuilder('pacienti')
            ->select('COUNT(pacienti.id) AS totalPacienti');

        $this->applyFilter($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function getTotalPacientiCuConsultatii($filter)
    {
        $totalQuery = $this->createQueryBuilder('pacienti')
            ->select('COUNT(DISTINCT pacienti.id) AS totalPacienti')
            ->innerJoin('pacienti.consultatii', 'consultatii');

        $this->applyFilter($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    private function pacientAreConsultatiiDeschise($pacientId)
    {
        try {
            $areConsultatiiDeschise = $this->createQueryBuilder('pacienti')
                ->select('COUNT(consultatii.id)')
                ->leftJoin('pacienti.consultatii', 'consultatii')
                ->where('consultatii.pacient = :pacientId')
                ->andWhere('consultatii.inchisa = :inchisa')
                ->andWhere('consultatii.stearsa = :stearsa')
                ->setParameters([
                    ':pacientId' => $pacientId,
                    ':inchisa' => false,
                    ':stearsa' => false,
                ])
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 4041, $exception);
        }

        return $areConsultatiiDeschise > 0;
    }

    private function pacientAreConsultatiiNeplatite($pacientId)
    {
        try {
            $areConsultatiiNeplatite = $this->createQueryBuilder('pacienti')
                ->select('COUNT(consultatii.id)')
                ->leftJoin('pacienti.consultatii', 'consultatii')
                ->where('consultatii.pacient = :pacientId')
                ->andWhere('consultatii.incasata = :incasata')
                ->andWhere('consultatii.stearsa = :stearsa')
                ->setParameters([
                    ':pacientId' => $pacientId,
                    ':incasata' => false,
                    ':stearsa' => false,
                ])
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 4041, $exception);
        }

        return $areConsultatiiNeplatite > 0;
    }

    private function getTotalPacientiInCabinet($filter)
    {
        $totalQuery = $this->createQueryBuilder('pacienti')
            ->select("COUNT(DISTINCT CONCAT(pacienti.id, DATE_FORMAT(consultatii.dataConsultatie, '%d-%m-%Y')))")
            ->leftJoin('pacienti.consultatii', 'consultatii')
            ->leftJoin('consultatii.pret', 'pret')
            ->leftJoin('pret.medic', 'medic');

        $this->applyFilterPacientiInCabinet($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function applyFilter($query, $filter, $extra = null)
    {
        $params = array_merge([':sters' => false], $extra ?? []);

        $query->andWhere('pacienti.sters = :sters');

        if (empty($filter['value'])) {
            $query->setParameters($params);

            return $query;
        }

        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->like('pacienti.telefon', ':filter'),
                $query->expr()->like('pacienti.prenume', ':filter'),
                $query->expr()->like('pacienti.adresa', ':filter'),
                $query->expr()->like('pacienti.nume', ':filter'),
                $query->expr()->like('pacienti.cnp', ':filter'),
                $query->expr()->like('pacienti.judet', ':filter'),
                $query->expr()->like("DATE_FORMAT(pacienti.dataInreg, '%d-%m-%Y')", ':filter'),
                $query->expr()->like('pacienti.ocupatie', ':filter'),
                $query->expr()->like('pacienti.locMunca', ':filter'),
                $query->expr()->like('pacienti.email', ':filter'),
                $query->expr()->like('pacienti.telefon2', ':filter'),
                $query->expr()->like('pacienti.localitate', ':filter')
            )
        );

        $query->setParameters(array_merge([':filter' => '%'.$filter['value'].'%'], $params));

        return $query;
    }

    private function applyFilterPacientiInCabinet($query, $filter)
    {
        $params = [
            ':inchisa' => false,
            ':incasata' => false,
            ':stearsa' => false,
        ];

        $query->where(
            $query->expr()->andX(
                $query->expr()->eq('consultatii.stearsa', ':stearsa'),
                $query->expr()->orX(
                    $query->expr()->eq('consultatii.inchisa', ':inchisa'),
                    $query->expr()->eq('consultatii.incasata', ':incasata')
                )
            )
        );

        if (empty($filter['value'])) {
            $query->setParameters($params);

            return $query;
        }

        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->like('pacienti.telefon', ':filter'),
                $query->expr()->like('pacienti.prenume', ':filter'),
                $query->expr()->like('pacienti.adresa', ':filter'),
                $query->expr()->like('pacienti.nume', ':filter'),
                $query->expr()->like('pacienti.cnp', ':filter'),
                $query->expr()->like('pacienti.judet', ':filter'),
                $query->expr()->like("DATE_FORMAT(consultatii.dataConsultatie, '%d-%m-%Y')", ':filter'),
                $query->expr()->like('pacienti.tara', ':filter'),
                $query->expr()->like('pacienti.localitate', ':filter'),
                $query->expr()->like('pacienti.email', ':filter'),
                $query->expr()->like('pacienti.telefon2', ':filter'),
                $query->expr()->like('pacienti.ocupatie', ':filter'),
                $query->expr()->like('pacienti.locMunca', ':filter')
            )
        );

        $query->setParameters(array_merge([':filter' => '%'.$filter['value'].'%'], $params));

        return $query;
    }

    private function buildSortPacientiInCabinet($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_NUME:
                $query->addOrderBy("CONCAT(pacienti.nume, ' ', pacienti.prenume)", $sort['dir']);
                break;
            case self::COL_CNP:
                $query->addOrderBy('pacienti.cnp', $sort['dir']);
                break;
            case self::COL_TELEFON:
                $query->addOrderBy('pacienti.telefon', $sort['dir']);
                break;
            case self::COL_TARA:
                $query->addOrderBy('pacienti.tara', $sort['dir']);
                break;
            case self::COL_JUDET:
                $query->addOrderBy('pacienti.judet', $sort['dir']);
                break;
            case self::COL_DATA_INREG:
                $query->addOrderBy('dataPrezentarii', $sort['dir']);
                break;
            case self::COL_LOC_MUNCA:
                $query->addOrderBy('pacienti.locMunca', $sort['dir']);
                break;
            case self::COL_OCUPATIE:
                $query->addOrderBy('pacienti.ocupatie', $sort['dir']);
                break;
            case self::COL_LOCALITATE:
                $query->addOrderBy('pacienti.localitate', $sort['dir']);
                break;
            case self::COL_TELEFON2:
                $query->addOrderBy('pacienti.telefon2', $sort['dir']);
                break;
            case self::COL_EMAIL:
                $query->addOrderBy('pacienti.email', $sort['dir']);
                break;
            default:
                break;
        }
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_NUME:
                $query->addOrderBy("CONCAT(pacienti.nume, ' ', pacienti.prenume)", $sort['dir']);
                break;
            case self::COL_CNP:
                $query->addOrderBy('pacienti.cnp', $sort['dir']);
                break;
            case self::COL_TELEFON:
                $query->addOrderBy('pacienti.telefon', $sort['dir']);
                break;
            case self::COL_TARA:
                $query->addOrderBy('pacienti.tara', $sort['dir']);
                break;
            case self::COL_JUDET:
                $query->addOrderBy('pacienti.judet', $sort['dir']);
                break;
            case self::COL_DATA_INREG:
                $query->addOrderBy('pacienti.dataInreg', $sort['dir']);
                break;
            case self::COL_LOC_MUNCA:
                $query->addOrderBy('pacienti.locMunca', $sort['dir']);
                break;
            case self::COL_LOCALITATE:
                $query->addOrderBy('pacienti.localitate', $sort['dir']);
                break;
            case self::COL_EMAIL:
                $query->addOrderBy('pacienti.email', $sort['dir']);
                break;
            default:
                break;
        }
    }
}

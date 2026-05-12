<?php

namespace App\Repository;

use App\Entity\FacturaConsultatie;
use App\Entity\Facturi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Facturi>
 */
class FacturiRepository extends ServiceEntityRepository
{
    public const COL_FURNIZOR = '5';
    public const STORNO = 1;

    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $em)
    {
        parent::__construct($registry, Facturi::class);
    }

    public function getAllFacturi($filter = null)
    {
        $query = $this->createQueryBuilder('facturi')
            ->select('facturi.id', 'owner.denumire AS furnizor', 'SUM(fc.valoare) AS valoare',
                "DATE_FORMAT(facturi.data, '%d-%m-%Y') AS data", 'pj.denumire AS clientPj', 'facturi.tip',
                "DATE_FORMAT(facturi.scadenta, '%d-%m-%Y') AS scadenta", 'facturi.serie', ' facturi.numar',
                "CONCAT(pacienti.nume, ' ', pacienti.prenume) AS numePacient",
                "CONCAT(stornare.serie,stornare.numar,'/',DATE_FORMAT(stornare.data,'%d-%m-%Y')) AS storno",
                "CASE WHEN facturi.tip = 1
                    THEN CONCAT(stornare.serie,stornare.numar,'/',DATE_FORMAT(stornare.data,'%d-%m-%Y')) 
                    ELSE '' 
                END AS originala")
            ->leftJoin('facturi.facturaConsultatii', 'fc')
            ->leftJoin('facturi.stornare', 'stornare')
            ->leftJoin('facturi.owner', 'owner')
            ->leftJoin('facturi.pacient', 'pacienti')
            ->leftJoin('facturi.clientPj', 'pj')
            ->groupBy('facturi.id');

        $this->applyFilter($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalFacturiByFilter($filter);
            $facturi = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception('Data collection error', 4001, $exception);
        }

        return [
            'facturi' => $facturi,
            'total' => $total,
        ];
    }

    public function saveInvoice(Facturi $invoice)
    {
        try {
            $this->em->persist($invoice);

            foreach ($invoice->getFacturaConsultatii() as $fc) {
                $this->em->persist($fc);
            }

            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception('Failed operation', 4001, $exception);
        }

        return $invoice;
    }

    public function storneaza($factura)
    {
        $storno = clone $factura;
        $date = new \DateTime();

        $lastInvoice = $this->em->getRepository(Facturi::class)
            ->findOneBy(['owner' => $factura->getOwner()->getId()], ['id' => 'DESC']);
        $storno->setTip(self::STORNO);
        $storno->setNumar($lastInvoice ? $lastInvoice->getNumar() + 1 : 1);
        $storno->setScadenta($date->add(new \DateInterval('P7D')));
        $storno->setData($date);
        $storno->setStornare($factura);

        foreach ($factura->getFacturaConsultatii() as $linie) {
            $fc = new FacturaConsultatie();

            $fc->setFactura($storno);
            $fc->setValoare(-$linie->getValoare());
            $fc->setConsultatie($linie->getConsultatie());

            $storno->addFacturaConsultatii($fc);
        }

        $storno = $this->saveInvoice($storno);

        $factura->setStornare($storno);
        $this->saveInvoice($factura);
    }

    private function getTotalFacturiByFilter($filter)
    {
        $totalQuery = $this->createQueryBuilder('facturi')
            ->select('COUNT(DISTINCT facturi.id)')
            ->leftJoin('facturi.facturaConsultatii', 'fc')
            ->leftJoin('facturi.stornare', 'stornare')
            ->leftJoin('facturi.owner', 'owner')
            ->leftJoin('facturi.pacient', 'pacienti')
            ->leftJoin('facturi.clientPj', 'pj');

        $this->applyFilter($totalQuery, $filter);

        return $totalQuery->distinct()->getQuery()->getSingleScalarResult();
    }

    private function applyFilter($query, $filter)
    {
        if (empty($filter['value'])) {
            return $query;
        }

        $query->andWhere(
            $query->expr()->orX(
                $query->expr()->like('owner.denumire', ':filter'),
                $query->expr()->like('pacienti.nume', ':filter'),
                $query->expr()->like('pacienti.prenume', ':filter'),
                $query->expr()->like('pj.denumire', ':filter'),
            )
        );

        $query->setParameter(':filter', '%'.$filter['value'].'%');

        return $query;
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_FURNIZOR:
                $query->addOrderBy('owner.denumire', $sort['dir']);
                break;
            default:
                break;
        }
    }
}

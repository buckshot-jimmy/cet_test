<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\Specialitate;
use App\Entity\Titulatura;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    const ROL_MEDIC = 'ROLE_Medic';
    const ROL_PSIHOLOG = 'ROLE_Psiholog';
    const STERS = 0;
    const COL_NUME = "1";
    const COL_ROL = "2";
    const COL_TELEFON = "3";
    const COL_EMAIL = "4";
    const COL_USERNAME = "5";
    const COL_COD_PARAFA = "6";
    const COL_TITULATURA = "7";
    const COL_SPECIALITATE = "8";
    const PAROLA_SCHIMBATA_PRIMA_LOGARE = 1;

    public function __construct(
        ManagerRegistry $registry,
        private UserPasswordHasherInterface $encoder,
        private EntityManagerInterface $em
    ) {
        parent::__construct($registry, User::class);
    }

    public function getAllUsers($filter)
    {
        $query = $this->createQueryBuilder('utilizatori')
            ->select('utilizatori.id', 'utilizatori.nume', 'utilizatori.prenume', 'role.id AS idRol',
                'utilizatori.telefon', 'utilizatori.codParafa', 'utilizatori.email', 'utilizatori.username',
                'SUBSTRING(role.denumire, 6, 20) AS rol', 'titulatura.denumire AS tit', 'specialitate.denumire AS spe')
            ->innerJoin('utilizatori.role', 'role')
            ->leftJoin('utilizatori.titulatura', 'titulatura')
            ->leftJoin('utilizatori.specialitate', 'specialitate');

        $this->applyFilters($query, $filter);

        if (isset($filter['sort'])) {
            $this->buildSort($filter['sort'], $query);
        }

        if (isset($filter['length']) && isset($filter['start'])) {
            $query->setMaxResults($filter['length'])
                ->setFirstResult($filter['start']);
        }

        try {
            $total = $this->getTotalUsers($filter);
            $users = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return [
            'utilizatori' => $users,
            'total' => $total
        ];
    }

    public function saveFirstTimeNewPassword($data)
    {
        $user = $this->em->getRepository(User::class)->find($data['user_id']);

        if(!$user) {
            throw new BadRequestHttpException("Missing ID");
        }

        $user->setPassword($this->encoder->hashPassword($user, $data['password']));
        $user->setParolaSchimbata(true);

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }
    }

    public function saveUser($formData)
    {
        $user = new User();

        if ($formData['editUserId'] === $formData['loggedUserId']) {
            $user = $this->saveProfile($formData);

            return $user;
        }

        if ($formData['editUserId']) {
            $user = $this->em->getRepository(User::class)->find($formData['editUserId']);
        }

        $user->setNume($formData['nume']);
        $user->setPrenume($formData['prenume']);
        $user->setTelefon($formData['telefon']);
        $user->setEmail($formData['email']);
        $user->setRole($this->em->getRepository(Role::class)->find($formData['rol']));
        $user->setUsername($formData['username']);
        $user->setTitulatura($formData['titulatura']
            ? $this->em->getRepository(Titulatura::class)->find($formData['titulatura'])
            : null);
        $user->setSpecialitate($formData['specialitate']
            ? $this->em->getRepository(Specialitate::class)->find($formData['specialitate'])
            : null);
        $user->setCodParafa($formData['cod_parafa']);
        $user->setSters(false);
        $user->setPassword($this->encoder->hashPassword($user, $formData['password']));

        if (isset($formData['parolaSchimbata'])
            && $formData['parolaSchimbata'] === self::PAROLA_SCHIMBATA_PRIMA_LOGARE) {
            $user->setParolaSchimbata($formData['parolaSchimbata']);
        }

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }

        return $user;
    }

    public function getUser($id)
    {
        try {
            $user = $this->createQueryBuilder('utilizator')
                ->select('utilizator.id', 'utilizator.nume', 'utilizator.prenume',
                    'utilizator.telefon', 'utilizator.codParafa', 'utilizator.email', 'utilizator.username',
                    'role.id AS rol', 'ut.id AS titulatura', 'us.id AS specialitate',
                    'ut.denumire AS denumireTitulatura', 'us.denumire AS denumireSpecialitate',
                    'utilizator.parolaSchimbata')
                ->innerJoin('utilizator.role', 'role')
                ->leftJoin('utilizator.titulatura', 'ut')
                ->leftJoin('utilizator.specialitate', 'us')
                ->where('utilizator.id = :id')
                ->setParameter(':id', $id)
                ->getQuery()
                ->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return $user[0] ?? [];
    }

    public function getAllMedici()
    {
        $query = $this->createQueryBuilder('medici');
        $query->select('medici.id', 'medici.nume', 'medici.prenume')
            ->leftJoin('medici.role', 'role')
            ->where($query->expr()->andX(
                $query->expr()->eq('medici.sters', ':sters'),
                $query->expr()->orX(
                    $query->expr()->eq('role.denumire', ':rolMedic'),
                    $query->expr()->eq('role.denumire', ':rolPsiholog')))
            )
            ->setParameters([
                    ':rolMedic' => self::ROL_MEDIC,
                    ':rolPsiholog' => self::ROL_PSIHOLOG,
                    ':sters' => self::STERS
            ]
        );

        try {
            $medici = $query->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        } catch (\Exception $exception) {
            throw new \Exception("Data collection error", 4001, $exception);
        }

        return $medici;
    }

    public function deleteUser($userId)
    {
        $user = $this->em->getRepository(User::class)->find($userId);

        $user->setSters(true);

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 5001, $exception);
        }
    }

    private function getTotalUsers($filter)
    {
        $totalQuery = $this->createQueryBuilder('utilizatori')
            ->select('COUNT(utilizatori.id) AS totalUtilizatori')
            ->innerJoin('utilizatori.role', 'role')
            ->leftJoin('utilizatori.titulatura', 'titulatura')
            ->leftJoin('utilizatori.specialitate', 'specialitate');

        $this->applyFilters($totalQuery, $filter);

        return $totalQuery->getQuery()->getSingleScalarResult();
    }

    private function buildSort($sort, $query)
    {
        switch ($sort['column']) {
            case self::COL_NUME:
                $query->addOrderBy("CONCAT(utilizatori.nume, ' ', utilizatori.prenume)", $sort['dir']);
                break;
            case self::COL_ROL:
                $query->addOrderBy('role.denumire', $sort['dir']);
                break;
            case self::COL_TELEFON:
                $query->addOrderBy('utilizatori.telefon', $sort['dir']);
                break;
            case self::COL_EMAIL:
                $query->addOrderBy('utilizatori.email', $sort['dir']);
                break;
            case self::COL_USERNAME:
                $query->addOrderBy('utilizatori.username', $sort['dir']);
                break;
            case self::COL_COD_PARAFA:
                $query->addOrderBy('utilizatori.codParafa', $sort['dir']);
                break;
            case self::COL_TITULATURA:
                $query->addOrderBy('titulatura.denumire', $sort['dir']);
                break;
            case self::COL_SPECIALITATE:
                $query->addOrderBy('specialitate.denumire', $sort['dir']);
                break;
            default:
                break;
        }
    }

    private function applyFilters($query, $filter)
    {
        $expr = $query->expr();

        $query
            ->where($expr->neq('utilizatori.id', ':loggedUserId'))
            ->andWhere($expr->eq('utilizatori.sters', ':sters'))
            ->setParameter('loggedUserId', $filter['loggedUserId'])
            ->setParameter('sters', false);

        if (empty($filter['value'])) {
            return $query;
        }

        $searchValue = '%' . $filter['value'] . '%';

        $orConditions = $expr->orX(
            $expr->like('utilizatori.nume', ':filter'),
            $expr->like('utilizatori.prenume', ':filter'),
            $expr->like('utilizatori.telefon', ':filter'),
            $expr->like('utilizatori.username', ':filter'),
            $expr->like('utilizatori.email', ':filter'),
            $expr->like('utilizatori.codParafa', ':filter'),
            $expr->like('titulatura.denumire', ':filter'),
            $expr->like('role.denumire', ':filter'),
            $expr->like('specialitate.denumire', ':filter')
        );

        $query
            ->andWhere($orConditions)
            ->setParameter('filter', $searchValue);

        return $query;
    }

    private function saveProfile($formData)
    {
        $user = $this->em->getRepository(User::class)->find($formData['loggedUserId']);

        $user->setNume($formData['edit_profile_nume']);
        $user->setPrenume($formData['edit_profile_prenume']);
        $user->setTelefon($formData['edit_profile_telefon']);
        $user->setEmail($formData['edit_profile_email']);
        $user->setRole($this->em->getRepository(Role::class)->find($formData['role']));
        $user->setUsername($formData['edit_profile_username']);
        if ($formData['edit_profile_titulatura']) {
            $user->setTitulatura($this->em->getRepository(Titulatura::class)
                ->find($formData['edit_profile_titulatura']));
        }
        if ($formData['edit_profile_specialitate']) {
            $user->setSpecialitate($this->em->getRepository(Specialitate::class)
                ->find($formData['edit_profile_specialitate']));
        }
        $user->setCodParafa($formData['edit_profile_cod_parafa']);
        $user->setPassword($this->encoder->hashPassword($user, $formData['edit_profile_password']));

        if ($this->encoder->hashPassword($user, $formData['edit_profile_password']) !== $user->getPassword()) {
            $user->setParolaSchimbata(self::PAROLA_SCHIMBATA_PRIMA_LOGARE);
        }

        try {
            $this->em->persist($user);
            $this->em->flush();
        } catch (\Exception $exception) {
            throw new \Exception("Failed operation", 4001, $exception);
        }

        return $user;
    }
}



<?php

namespace App\Services;

use App\Entity\Pacient;
use App\Form\PacientFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class PacientService
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private TranslatorInterface $translator,
        private EntityManagerInterface $em
    ) {}

    public function getPacientForRequest(Request $request): Pacient
    {
        $pacientId = $request->request->get('pacient_id') ?: $request->request->get('id');

        if (!$pacientId) {
            return $this->getInitializedPacient();
        }

        $pacient = $this->em->getRepository(Pacient::class)->find($pacientId);

        if (!$pacient instanceof Pacient) {
            throw new BadRequestHttpException($this->translator->trans('Missing ID'));
        }

        return $pacient;
    }

    public function getPacientForData(array $data): Pacient
    {
        $pacient = $this->getInitializedPacient();

        if (!$data) {
            return $pacient;
        }

        $pacient->setNume($data['nume']);
        $pacient->setPrenume($data['prenume']);
        $pacient->setCnp($data['cnp']);
        $pacient->setTelefon($data['telefon']);
        $pacient->setAdresa($data['adresa']);
        $pacient->setTara($data['tara']);
        $pacient->setCi($data['ci'] ?? null);
        $pacient->setCiEliberat($data['ciEliberat'] ?? null);
        $pacient->setJudet($data['judet'] ?? null);
        $pacient->setLocalitate($data['localitate'] ?? null);
        $pacient->setTelefon2($data['telefon2'] ?? null);
        $pacient->setEmail($data['email'] ?? null);
        $pacient->setOcupatie($data['ocupatie'] ?? null);
        $pacient->setLocMunca($data['locMunca'] ?? null);
        $pacient->setStareCivila($data['stareCivila'] ?? 0);
        $pacient->setObservatii($data['observatii'] ?? null);

        return $pacient;
    }

    public function createPacientForm(
        Pacient $pacient,
        NomenclatoareService $service,
        string|int|null $varsta = '',
        string|int|null $pacientId = null
    ) {
        return $this->formFactory->createNamed('', PacientFormType::class, $pacient, [
            'tari' => $service->getTari(),
            'judete' => $service->getJudete(),
            'stariCivile' => $service->getStariCivile(),
            'varsta' => $varsta,
            'pacient_id' => $pacientId ?? $pacient->getId(),
        ]);
    }

    public function buildFormValidationErrors(FormErrorIterator $errors): string
    {
        $messages = '';

        foreach ($errors as $error) {
            $messages .= ' ' . $this->translator->trans($error->getMessage());
        }

        return $messages;
    }

    private function getInitializedPacient(): Pacient
    {
        $pacient = new Pacient();
        $pacient->setNume('');
        $pacient->setPrenume('');
        $pacient->setCnp('');
        $pacient->setTelefon('');
        $pacient->setAdresa('');
        $pacient->setTara('Romania');
        $pacient->setLocalitate('');
        $pacient->setStareCivila(0);

        return $pacient;
    }
}
<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class FirmeConstraints extends Constraint
{
    public $messages = [
        "cui" => "Introduceti un CUI valid",
        "cuiUnic" => "CUI introdus exista in baza de date",
        "denumireUnica" => "Firma introdusa exista in baza de date",
        "ibanUnic" => "Contul introdus exista in baza de date",
        "iban" => "Introduceti un IBAN valid",
        "regCom" => "Introduceti un numar valid",
    ];
}
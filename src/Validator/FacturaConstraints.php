<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class FacturaConstraints extends Constraint
{
    public $messages = [
        "facturaUnica" => "Factura cu seria, numarul si data exista in baza de date",
        "noClient" => "Introduceti un client valid"
    ];
}
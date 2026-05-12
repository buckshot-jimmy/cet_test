<?php


namespace App\Validator;


use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PacientConstraints extends Constraint
{
    public $messages = [
        "cnp" => "Introduceti un CNP valid",
        "cnpIdUnic" => "CNP / ID introdus exista in baza de date"
    ];
}
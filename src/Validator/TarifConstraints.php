<?php


namespace App\Validator;


use Symfony\Component\Validator\Constraint;

#[\Attribute]
class TarifConstraints extends Constraint
{
    public $messages = [
        "medicServiciuOwner" => "Dr-Owner tariff in DB",
        "tarifNegativ" => "Negative tariff"
    ];
}
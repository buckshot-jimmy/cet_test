<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ProgramareConstraints extends Constraint
{
    public $messages = [
        "unavailableTime" => "Ora indisponibila",
        "pastTime" => "Data in trecut",
    ];
}
<?php


namespace App\Validator;


use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UserConstraints extends Constraint
{
    public $messages = [
        "parolaCurenta" => "Password cannot be the same as your current password",
        "modificareRol" => "You are not allowed to change your role",
        "modificareParolaAltUtilizator" => "You are not allowed to change another user's password",
        "modificareParolaAltAdministrator" => "You are not allowed to change another administrator's password",
        "parolaSlaba" => "Password is too weak. You should use one capital letter, one letter, " .
            "one digit and one special character. It should be at least 6 characters long.",
        "usernameUnic" => "Username already exists",
        "emailUnic" => "Email already exists",
    ];
}
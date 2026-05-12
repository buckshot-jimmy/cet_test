jQuery.extend(jQuery.validator.messages, {
    required: "Camp obligatoriu",
    digits: "Introduceti doar cifre",
    max: jQuery.validator.format("Introduceti o valoare mai mica sau egala cu {0}"),
    min: jQuery.validator.format("Introduceti o valoare egala sau mai mare decat {0}"),
    email: "Introduceti o adresa de email valida",
    number: "Introduceti doar cifre",
    equalTo: "Cele doua campuri trebuie sa contina acelas text",
    minlength: "Introduceti cel putin {0} caractere."
});

$.validator.addMethod("time", function(value, element) {
    return this.optional(element) || /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value);
}, "Introduceți o oră validă (HH:mm).");

$.validator.addMethod("dateDMY", function(value, element) {
    if (!value) return true; // allow empty if not required
    var regex = /^(\d{2})-(\d{2})-(\d{4})$/; // dd-mm-yyyy
    if (!regex.test(value)) return false;

    var parts = value.match(regex);
    var day = parseInt(parts[1], 10);
    var month = parseInt(parts[2], 10);
    var year = parseInt(parts[3], 10);
    var date = new Date(year, month - 1, day);

    // Validate date correctness
    return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
}, "Introduceti o data valida (dd-mm-yyyy)");

jQuery.validator.addMethod('strength', function (value, element) {
    let pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{6,}$/;
    return this.optional(element) || pattern.test(value);
}, "Parola este prea slaba. Trebuie sa folosesti o litera mare, o litera mica, un numar si un caracter special. Trebuie sa fie cel putin 6 caractere.");

jQuery.validator.addMethod('letters', function (value, element) {
    let regex = new RegExp(/^[a-zA-Z- ]*$/);
    return this.optional(element) || regex.test(value);
}, "Introduceti doar litere, spatiu si semnul '-'");

jQuery.validator.addMethod('cnp', function (value, element) {
    if ($("#tara").val() !== 'Romania') {
        return true;
    }

    return valideazaCnp(value);
}, "Introduceti un CNP valid");

jQuery.validator.addMethod('parafa', function (value, element) {
    return this.optional(element) || /^[a-zA-Z0-9]+$/i.test(value);
}, "Introduceti litere si cifre");

jQuery.validator.addMethod('cui', function (value, element) {
    return valideazaCui(value);
}, "Introduceti un CUI valid");

jQuery.validator.addMethod('contBancar', function (value, element) {
    return validateIBAN(value);
}, "Introduceti un IBAN valid");

jQuery.validator.addMethod('regCom', function (value, element) {
    return validateNrRegCom(value);
}, "Introduceti un numar valid");

jQuery.validator.setDefaults({
    errorElement: 'div', //default input error message container
    errorClass: 'form-control-feedback', // default input error message class
    focusInvalid: false, // do not focus the last invalid input
    ignore: "",  // validate all fields including form hidden input
    errorPlacement: function(error, element) { // render error placement for each input type
        if ($(element).hasClass("date-or-time") || $(element).hasClass("select2")) {
            $(element).parent().after(error.addClass("has-danger"));
        } else if ($(element).hasClass("select2-search")) {
            $(element).parent().find('.select2-container').after(error.addClass("has-danger"));
        } else {
            $(element).after(error.addClass("has-danger"));
        }
    },
    highlight: function(element) { // hightlight error inputs
        if ($(element).hasClass("date-or-time")) {
            $(element).parent().next('.form-control-feedback').addClass('has-danger');
        } else if ($(element).hasClass("select2-search")) {
            $(element).next().next().addClass('has-danger');
        } else {
            $(element).next('.form-control-feedback').addClass('has-danger'); // set error class to the control group
        }
    },
    unhighlight: function(element) { // revert the change done by hightlight
        if ($(element).hasClass("date-or-time")) {
            $(element).parent().next('.form-control-feedback').removeClass('has-danger');
        } else {
            $(element).parent().find('.form-control-feedback').remove();
        }
    },
    success: function(label, element) {
        $(element).parent().find('.form-control-feedback').remove();

        if ($('form').find('.has-danger').length === 0) {
            $(".rezultat_operatiune_fail").hide();
        }
    }
});

$('.modal').on('show.bs.modal', function (e) {
    $(this).find(".has-danger").removeClass('has-danger').hide();
});

function valideazaCnp( p_cnp ) {
    var i=0 , year=0 , hashResult=0 , cnp=[] , hashTable=[2,7,9,1,4,6,3,5,8,2,7,9];
    if( p_cnp.length !== 13 ) { return false; }
    for( i=0 ; i<13 ; i++ ) {
        cnp[i] = parseInt( p_cnp.charAt(i) , 10 );
        if( isNaN( cnp[i] ) ) { return false; }
        if( i < 12 ) { hashResult = hashResult + ( cnp[i] * hashTable[i] ); }
    }
    hashResult = hashResult % 11;
    if( hashResult === 10 ) { hashResult = 1; }
    year = (cnp[1]*10)+cnp[2];
    switch( cnp[0] ) {
        case 1  : case 2 : { year += 1900; } break;
        case 3  : case 4 : { year += 1800; } break;
        case 5  : case 6 : { year += 2000; } break;
        case 7  : case 8 : case 9 : { year += 2000; if( year > ( parseInt( new Date().getYear() , 10 ) - 14 ) ) { year -= 100; } } break;
        default : { return false; }
    }
    if( year < 1800 || year > 2099 ) { return false; }
    return ( cnp[12] === hashResult );
}

function validateNrRegCom(nrRegCom) {
    if (!nrRegCom) return true;

    const regex = /^[JCF]\d{2}\/\d{1,6}\/\d{4}$/;
    return regex.test(nrRegCom);
}

function validateIBAN(iban) {
    if (!iban) return true;

    // Elimină spații și transformă în uppercase
    iban = iban.replace(/\s+/g, '').toUpperCase();

    // Verificare basic (lungime minimă)
    if (iban.length < 15 || iban.length > 34) return false;

    // Mută primele 4 caractere la final
    const rearranged = iban.slice(4) + iban.slice(0, 4);

    // Înlocuiește literele cu numere (A=10, B=11, ..., Z=35)
    const numericIBAN = rearranged
        .split('')
        .map(char => {
            if (char >= 'A' && char <= 'Z') {
                return char.charCodeAt(0) - 55;
            }
            return char;
        })
        .join('');

    // Calculează mod 97 (fără overflow)
    let remainder = numericIBAN;
    while (remainder.length > 2) {
        const block = remainder.slice(0, 9);
        remainder = (parseInt(block, 10) % 97) + remainder.slice(block.length);
    }

    return parseInt(remainder, 10) % 97 === 1;
}

function valideazaCui(cui) {
    var cif = cui;
    // Daca este string, elimina atributul fiscal si spatiile
    if(isNaN(parseFloat(cui)) || !isFinite(cui)){
        cif = cif.toUpperCase();
        if(cif.indexOf('RO') === 0){
            cif = cif.replace('RO', '');
        } else {
            return false;
        }
        cif = parseInt(cif.trim());
    } else {
        cif = parseInt(cif);
    }
    // daca are mai mult de 10 cifre sau mai putin de 6, nu-i valid
    if(cif.toString().length > 10 || cif.toString().length < 6){
        return false;
    }
    // numarul de control
    var v = 753217532;

    // extrage cifra de control
    var c1 = parseInt(cif % 10);
    cif = parseInt(cif / 10);

    // executa operatiile pe cifre
    var t = 0;
    while (cif > 0) {
        t += (cif % 10) * (v % 10);
        cif = parseInt(cif / 10);
        v = parseInt(v / 10);
    }

    // aplica inmultirea cu 10 si afla modulo 11
    var c2 = parseInt((t * 10) % 11);
    // daca modulo 11 este 10, atunci cifra de control este 0
    if(c2 === 10){
        c2 = 0;
    }

    return c1 === c2;
}
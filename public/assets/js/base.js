$(document).ready(function () {
    initModalHide();
    initPreventDatatableAlert();
    initProfileChanges();

    const ROL_MEDIC = "ROLE_Medic";
    const PAROLA_NESCHIMBATA_PRIMA_LOGARE = "0";

    $("#iesire").click(function () {
        $(".spinner").show();
    });

    $("#login_form" ).validate({
        rules: {
            username: {
                required: true,
            },
            password: {
                required: true,
            }
        }
    });

    $(".edit_profile_button").click(function () {
        $( "#edit_profile_form" ).validate({
            rules: {
                edit_profile_nume: {
                    required: true,
                    letters: true
                },
                edit_profile_prenume: {
                    required: true,
                    letters: true
                },
                edit_profile_username: {
                    required: true
                },
                edit_profile_telefon: {
                    required: true,
                    digits: true
                },
                edit_profile_email: {
                    required: true
                },
                edit_profile_rol: {
                    required: true
                },
                edit_profile_cod_parafa: {
                    required: function(element){
                        return $("#edit_profile_rol").val() === ROL_MEDIC;
                    }
                },
                edit_profile_titulatura: {
                    required: function(element){
                        return $("#edit_profile_rol").val() === ROL_MEDIC;
                    }
                },
                edit_profile_specialitate: {
                    required: function(element){
                        return $("#edit_profile_rol").val() === ROL_MEDIC;
                    }
                },
                edit_profile_password: {
                    required: true,
                    minlength: 6,
                    strength: true
                }
            },
            messages: {
                "edit_profile_cod_parafa":{
                    required: "Camp obligatoriu pentru medici"
                },
                "edit_profile_titulatura":{
                    required: "Camp obligatoriu pentru medici"
                },
                "edit_profile_specialitate":{
                    required: "Camp obligatoriu pentru medici"
                }
            }
        });

        $("#edit_profile_rol").trigger("change");
        $("#edit_profile_password").val("");
        $(".edit_profile_modal").modal("show");
    });

    $(".confirma_editare_profil").click(function () {
        resetRezultatOperatiune(true);

        if (!$("#edit_profile_form").valid()) {
            addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
            return false;
        }

        $.ajax({
            url: "user/add_edit_user",
            type: "POST",
            data: {
                form: $("#edit_profile_form").serialize() + "&editUserId=" + $("#profile_user_id").val() +
                    "&loggedUserId=" + $("#logged_user_id").val() + "&role=" + $("#edit_profile_rol").val() +
                    "&edit_profile_password=" + $("#edit_profile_password").val()
            },
            success: function (response) {
                addRezultatOperatiuneSuccess(response, ".edit_profile_modal");

                location.reload();
            },
            error: function (response) {
                addRezultatOperatiuneFail(response);
            }
        });
    });

    $('.modal').on('hide.bs.modal', function () {
        $(this).find('form').trigger('reset');
        $(this).find('form').find('input').val('');
        $(this).find('.select2-search').val('').trigger('change');
        $(this).find("select").prop('disabled', false);
        $(".btn_confirma").prop("disabled", false);
        $(".spinner").hide();
        $(".rezultat_operatiune").removeClass('rezultat_operatiune_success').removeClass('rezultat_operatiune_fail')
            .removeClass('rezultat_operatiune_info').hide();
    });

    if ($("#parola_schimbata").val() == PAROLA_NESCHIMBATA_PRIMA_LOGARE) {
        $( "#change_password_form" ).validate({
            rules: {
                change_password: {
                    required: true,
                    equalTo: "#change_confirm_password",
                    minlength: 6,
                },
                change_confirm_password: {
                    required: true,
                    equalTo: "#change_password",
                    minlength: 6,
                }
            }
        });

        $(".change_password_modal").modal("show");

        $(".confirma_change_password").click(function () {
           resetRezultatOperatiune(true);

            if (!$("#change_password_form").valid()) {
                return false;
            }

            $.ajax({
                url: "security/salveaza_noua_parola",
                type: "POST",
                data: {
                    parola: $("#change_password").val(),
                    parolaConfirmata: $("#change_confirm_password").val(),
                    loggedUserId: $("#logged_user_id").val(),
                    editUserId: $("#logged_user_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, ".change_password_modal");
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    }
});

function hideModalTimeout(modalClass)
{
    setTimeout(function () {
        $(modalClass).modal("hide");
    }, 2000);
}

function initModalHide()
{
    let lastFocusedElement = null;

    $(document).on('show.bs.modal', '.modal', function () {
        lastFocusedElement = document.activeElement;
    });

    $(document).on('hidden.bs.modal', '.modal', function () {
        if (lastFocusedElement) {
            $(lastFocusedElement).trigger('focus');
        }
        document.activeElement?.blur();

        if ($('.modal.show').length) {
            $('body').addClass('modal-open');
        }
    });
}

function initPreventDatatableAlert()
{
    $.fn.dataTable.ext.errMode = 'none';

    $(document).on('error.dt', function (e) {
        e.preventDefault();
    });

    $(document).on('xhr.dt', function (e, settings, id, response) {
        if (response.responseJSON.message && response.responseJSON.status_code !== 200) {
            $(".short-modal").html('<i class="fa fa-exclamation-triangle text-warning"></i>');
            $("#short_modal_text").text(response.responseJSON.message);
            $(".short_modal").modal("show");
        }
    });
}

function initProfileChanges()
{
    const ROL_MEDIC = "2";
    const ROL_PSIHOLOG = "3";

    $("#edit_profile_rol").on("change", function () {
        enableProfileFields();

        if (![ROL_MEDIC, ROL_PSIHOLOG].includes($("#edit_profile_rol").val())) {
            disableProfileFields();
        }
    });
}

function disableProfileFields()
{
    $("#edit_profile_cod_parafa").prop('disabled', true);
    $("#edit_profile_titulatura").prop('disabled', true);
    $("#edit_profile_specialitate").prop('disabled', true);
}

function enableProfileFields()
{
    $("#edit_profile_cod_parafa").prop('disabled', false);
    $("#edit_profile_titulatura").prop('disabled', false);
    $("#edit_profile_specialitate").prop('disabled', false);
}

function resetRezultatOperatiune(spinner)
{
    $(".rezultat_operatiune").removeClass('rezultat_operatiune_success')
        .removeClass('rezultat_operatiune_fail').removeClass('rezultat_operatiune_info').text('');

    if (spinner) {
        $(".spinner").show();
    }
}

function addRezultatOperatiuneSuccess(response, modalClassToHide)
{
    $(".rezultat_operatiune").html(response.message)
        .addClass('rezultat_operatiune_success').show();

    hideModalTimeout(modalClassToHide);

    $(".spinner").hide();
}

function addRezultatOperatiuneInfo(response, modalClassToHide)
{
    $(".rezultat_operatiune").html(response.responseJSON.message)
        .addClass('rezultat_operatiune_info').show();

    hideModalTimeout(modalClassToHide);

    $(".spinner").hide();
}

function addRezultatOperatiuneFail(response)
{
    $(".rezultat_operatiune").html(response.responseJSON.message)
        .addClass('rezultat_operatiune_fail').show();

    $(".spinner").hide();
}

function addShortModal(response, result) {
    let modalTitle = '<i class="fa fa-check text-success"></i>';
    let modalText = '';

    if (result === 'fail') {
        modalTitle = '<i class="fa fa-exclamation-triangle text-warning"></i>';
        modalText = response.responseJSON.message;
    } else {
        modalText = response.message;
    }

    $(".short-modal").html(modalTitle);

    $("#short_modal_text").text(modalText);
    $(".short_modal").modal("show");
}

function initSearchPacient(input) {
    $(input).select2({
        width: "100%",
        placeholder: "CNP",
        minimumInputLength: 3,
        numericInput: true,
        language: {
            searching: function () {
                return "Se caută...";
            },
            noResults: function () {
                return "Nu au fost găsite rezultate";
            },
            inputTooShort: function () {
                return "Introduceti cel putin 3 cifre";
            },
            errorLoading: function () {
                return "Eroare la incarcarea datelor";
            },
        },
        ajax: {
            url: "/pacienti/get_pacienti_by_cnp",
            dataType: "json",
            delay: 200,
            data: function (params) {
                return {
                    cnp: params.term
                };
            },
            processResults: function (response) {
                return {
                    results: response.pacienti.map(item => ({
                        id: item.id,
                        text: item.numePacient
                    }))
                };
            },
        }
    });

    $(input).on("select2:select", function (e) {
        $(input).valid();
    });
}
function initSearchClientPj(input) {
    $(input).select2({
        width: "100%",
        placeholder: "CUI",
        minimumInputLength: 3,
        language: {
            searching: function () {
                return "Se caută...";
            },
            noResults: function () {
                return "Nu au fost găsite rezultate";
            },
            inputTooShort: function () {
                return "Introduceti cel putin 3 caractere";
            },
            errorLoading: function () {
                return "Eroare la incarcarea datelor";
            },
        },
        ajax: {
            url: "/get_clienti_pj_by_cui",
            dataType: "json",
            delay: 200,
            data: function (params) {
                return {
                    cui: params.term
                };
            },
            processResults: function (response) {
                return {
                    results: response.clienti.map(item => ({
                        id: item.id,
                        text: item.denumire
                    }))
                };
            },
        }
    });

    $(input).on("select2:select", function (e) {
        $(input).valid();
    });
}

function fetchPacienti(cnp) {
    return fetch(`/pacienti/get_pacienti_by_cnp?cnp=${cnp}`)
        .then(res => res.json());
}

function calculeazaVarsta(cnp, dataNasteriiPacient) {
    if (dataNasteriiPacient.length > 0) {
        let ziNastere = dataNasteriiPacient.substr(0, 2);
        let lunaNastere = dataNasteriiPacient.substr(3, 2);
        let anNastere = dataNasteriiPacient.substr(6, 4);
        let dataCompleta = anNastere + "-" + lunaNastere + "-" + ziNastere;

        let dataN = moment(new Date(dataCompleta));
        let dataCons = moment(new Date());

        return dataCons.diff(dataN, 'years');
    }

    let sex = "M";
    let secol = "19";

    if (cnp[0] === "2" || cnp[0] === "6" || cnp[0] === "8") {
        sex = "F";
    }

    if(cnp[0] === "5" || cnp[0] === "6") {
        secol = "20";
    }

    let ziNastere = cnp.substr(5, 2);
    let lunaNastere = cnp.substr(3, 2);
    let anCnp = cnp.substr(1, 2);
    let anNastere = secol + anCnp;
    let dataNasterii = anNastere + "-" + lunaNastere + "-" + ziNastere;
    let dataNasteriiView = ziNastere + "-" + lunaNastere + "-" + anNastere;
    let nastere = moment(new Date(dataNasterii));
    let dataConsultatiei = moment(new Date());

    return [dataConsultatiei.diff(nastere, 'years'), dataNasteriiView, sex];
}

function checkBoxStyle() {
    return 'position: relative; width: 20px; height: 20px; border: 2px solid #d1d5db; border-radius: 6px;' +
        'margin-right: 12px; padding-bottom: 15px;';
}
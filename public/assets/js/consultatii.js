let consultatii = function () {
    let tablePacientiConsultatii;

    const TIP_CONSULTATIE = 0;
    const TIP_INVESTIGATIE = 1;
    const TIP_EVAL_PSIHO = 2;
    const TIP_TOATE = 3;

    let initConsultatieFormValidation = function () {
        $( "#consultatie_form" ).validate({
            rules: {
                consultatie_nrInreg: {
                    required: true
                },
                consultatie_pacient: {
                    required: true
                },
                consultatie_medic: {
                    required: true
                },
                consultatie_serviciu: {
                    required: true
                },
                consultatie_owner: {
                    required: true
                },
                consultatie_diagnostic: {
                    required: true
                },
                consultatie_consultatie: {
                    required: true
                },
                consultatie_tratament: {
                    required: true
                }
            }
        });
    };

    let initInvestigatieFormValidation = function () {
        $( "#investigatie_form" ).validate({
            rules: {
                investigatie_nrInreg: {
                    required: true
                },
                investigatie_pacient: {
                    required: true
                },
                investigatie_medic: {
                    required: true
                },
                investigatie_serviciu: {
                    required: true
                },
                investigatie_owner: {
                    required: true
                },
                investigatie_rezultat: {
                    required: true
                },
                investigatie_concluzie: {
                    required: true
                }
            }
        });
    };

    let initStergere = function () {
        $(".modal_stergere").on("hide.bs.modal", function () {
            $(".servicii_documente_modal").css('filter', 'blur()');
        });

        $("body").on("click", ".sterge_consultatie", function () {
            let consultatieId = this.closest('tr').id;
            $("#modal_stergere_id").val(consultatieId);
            $('.confirma_stergere').addClass('delete_consultatie');

            let tip = $(this).attr('tip');
            let tipText = ((parseInt(tip) === TIP_CONSULTATIE || parseInt(tip) === TIP_EVAL_PSIHO)
                ? "consultatia selectata?"
                : "investigatia selectata?");

            $("#stergere_text").html("Stergeti " + tipText);

            $(".servicii_documente_modal").css('filter', 'blur(5px)');
            $(".modal_stergere").modal("show").css("z-index", 1100);
        });

        $("body").on('click', '.delete_consultatie', function () {
            let consultatieId = $("#modal_stergere_id").val();
            let pacientId = $("#servicii_documente_pacient_id").val();

            resetRezultatOperatiune(true);

            $.ajax({
                url: "consultatii/sterge_consultatie",
                type: "POST",
                data: {
                    id: consultatieId
                },
                success: function (response) {
                    $("#table_servicii_documente").empty();

                    getServiciiPacient(pacientId);

                    addRezultatOperatiuneSuccess(response, ".modal_stergere");
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    let initServiciiDocumente = function () {
        $(".servicii_documente_modal ").on('hidden.bs.modal', function(){
            $("#table_servicii_documente").empty();
            $("#cauta_serviciu").val("");
        });

        $("#cauta_serviciu").on("keyup", function() {
            let value = $(this).val().toLowerCase();

            $("#table_servicii_documente tr").each(function(index) {
                let row = $(this);
                let data = row.find("td:first").text().toLowerCase();
                let medic = row.find("td:nth-child(2)").text().toLowerCase();
                let serviciu = row.find("td:nth-child(3)").text().toLowerCase();

                $(this).toggle(data.indexOf(value) !== -1
                    || medic.indexOf(value) !== -1
                    || serviciu.indexOf(value) !== -1
                );
            });
        });

        $("body").on("click", ".servicii_documente", function () {
            let id = this.closest('tr').id;
            $("#servicii_documente_pacient_id").val(id);

            let pacient = this.getAttribute('pacient');
            $(".titlu_servicii_documente_modal").css('font-weight', 'bold')
                .text('Pacient ' + pacient + ' - Lista consultatii / investigatii');

            $(".servicii_documente_modal").modal("show");

            getServiciiPacient(id);
        });
    };

    let initEditareConsultatieFromPacient = function () {
        $("body").on("click", ".edit_from_pacient", function () {
            $(".servicii_documente_modal").css('filter', 'blur(5px)');

            let id = this.closest('tr').id;
            getConsultatieInvestigatieEval(id, $(this).attr('tip'));
        });

        $("body").on("click", ".confirma_cons_from_pacient", function () {
            confirmaConsultatie();
        });

        $("body").on("click", ".confirma_inv_from_pacient", function () {
            confirmaInvestigatie();
        });

        $("body").on("click", ".confirma_eval_psiho_from_pacient", function () {
            confirmaEvaluarePsihologica();
        });
    };

    let initEvents = function () {
        $("body").on('hidden.bs.modal', '.add_edit_consultatie_modal, .add_edit_investigatie_modal, .add_edit_eval_psiho_modal', function(){
            $(this).children(".dropdown-menu").empty();
            $(".spinner").hide();
            $(".servicii_documente_modal").css('filter', 'blur(0)');

            $("#consultatie_id").val('');
            $("#investigatie_id").val('');
            $("#eval_psiho_id").val('');
        });

        $(".view_consultatie_modal, .view_investigatie_modal, .view_eval_psiho_modal, .short_modal")
            .on('hidden.bs.modal', function(){
            removeBlur();

            $(".confirma_preia_cons, .confirma_preia_inv, .confirma_preia_eval, .confirma_preia_tot").removeAttr("disabled");
        });

        $("body").on('click', '.confirma_preia_cons, .confirma_preia_inv, .confirma_preia_eval, .confirma_preia_tot', function () {
           $(this).attr('disabled', 'disabled');
        });

        $('body').on('click', '.confirma_preia_tot', function () {
            if ($('.view_consultatie_modal').is(':visible')) {
                $('.confirma_preia_cons').each(function () {
                    $(this).trigger('click');
                });
            } else if ($('.view_investigatie_modal').is(':visible')) {
                $('.confirma_preia_inv').each(function () {
                    $(this).trigger('click');
                });
            } else {
                $('.confirma_preia_eval').each(function () {
                    $(this).trigger('click');
                });
            }
        });

        $("body").on("click", ".confirma_preia_cons, .confirma_preia_inv", function () {
            const btn = $(this);

            const from = $(".view_consultatie_modal ").is(":visible")
                ? btn.data("from-consultatie")
                : btn.data("from-investigatie");

            const to = $(".add_edit_consultatie_modal").is(":visible")
                ? btn.data("to-consultatie")
                : btn.data("to-investigatie");

            if (!from || !to) return;

            const val = $(from).val();
            if (!val) return;

            $(to).val((_, old) => (old ? old + "\n" : "") + val);
        });

        $("body").on("click", ".confirma_preia_eval", function () {
            const btn = $(this);

            const from = btn.data("from-eval") ? [btn.data("from-eval")]
                : [btn.data("from-eval-ce"), btn.data("from-eval-cu-ce")];
            const to = btn.data("to-eval") ? [btn.data("to-eval")]
                : [btn.data("to-eval-ce"), btn.data("to-eval-cu-ce")];

            if (!from || !to) return;

            $.each(from, function (index, elem) {
                if (!$(elem).val()) return true;
                const val = $(from[index]).val();

                $(to[index]).val((_, old) => (old ? old + "\n" : "") + val);
            });
        });

        $("body").on("click", ".istoric_consultatii_cons, .istoric_consultatii_inv", function () {
            if ($(this).parent().find('.dropdown-menu a').length === 0) {
                getIstoricPacient(TIP_CONSULTATIE, $(this).parent().find('.dropdown-menu'));
            }
        });

        $("body").on("click", ".istoric_investigatii_cons, .istoric_investigatii_inv", function () {
            if ($(this).parent().find('.dropdown-menu a').length === 0) {
                getIstoricPacient(TIP_INVESTIGATIE, $(this).parent().find('.dropdown-menu'));
            }
        });

        $("body").on("click", ".istoric_toate_cons, .istoric_toate_inv", function () {
            if ($(this).parent().find('.dropdown-menu a').length === 0) {
                getIstoricPacient(TIP_TOATE, $(this).parent().find('.dropdown-menu'));
            }
        });

        $("body").on("click", ".istoric_eval_psiho", function () {
            if ($(this).parent().find('.dropdown-menu a').length === 0) {
                getIstoricPacient(TIP_EVAL_PSIHO, $(this).parent().find('.dropdown-menu'));
            }
        });

        $("body").on("click", "#list_all_cons", function () {
            $("#consultatie_medicTrimitator").focus();

        });

        $("body").on("click", "#list_all_inv", function () {
            $("#investigatie_medicTrimitator").focus();
        });
    };

    let initTablePacientiConsultatii = function () {
        tablePacientiConsultatii = $('#consultatii').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "pacienti/list_pacienti_cu_consultatii",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                { "orderable": false, "targets": [0, 9, 16] }
            ],
            "order": [[ 1, "desc" ]],
            "columns": [
                {
                    title: "Actiuni",
                    render: function (data, type, row) {
                        return '<a href="#" class="btn btn-sm btn-primary btn-circle servicii_documente"' +
                            ' title="Servicii si documente" style="margin-left: 10px;" pacient="' + row.nume + " "
                            + row.prenume + '"><i class="fas fa-list"></i></a>'
                    }
                },
                {
                    title: "Nume",
                    data: "nume_prenume",
                    name: "nume_prenume",
                    render: function (data, type, row) {
                        return row.nume + " " + row.prenume;
                    },
                    width: 100
                },
                {
                    title: "Tara",
                    data: "tara",
                    name: "tara"
                },
                {
                    title: "CNP",
                    data: "cnp",
                    name: "cnp",
                    width: 100
                },
                {
                    title: "Sex",
                    data: "sex",
                    name: "sex"
                },
                {
                    title: "Data nasterii",
                    data: "dataNasterii",
                    name: "dataNasterii",
                    width: 60
                },
                {
                    title: "Varsta",
                    data: "varsta",
                    name: "varsta"
                },
                {
                    title: "Judet",
                    data: "judet",
                    name: "judet"
                },
                {
                    title: "Localitate",
                    data: "localitate",
                    name: "localitate"
                },
                {
                    title: "Adresa",
                    data: "adresa",
                    name: "adresa",
                    width: 200
                },
                {
                    title: "Telefon",
                    data: "telefon",
                    name: "telefon"
                },
                {
                    title: "Telefon 2",
                    data: "telefon2",
                    name: "telefon2"
                },
                {
                    title: "Email",
                    data: "email",
                    name: "email"
                },
                {
                    title: "Ocupatie",
                    data: "ocupatie",
                    name: "ocupatie"
                },
                {
                    title: "Loc de munca",
                    data: "locMunca",
                    name: "locMunca"
                },
                {
                    title: "Data inreg.",
                    data: "dataInreg",
                    name: "dataInreg",
                    width: 60
                },
                {
                    title: "Observatii",
                    data: "observatii",
                    name: "observatii"
                }
            ]
        });
    };

    let getIstoricPacient = function (TIP_SERVICIU, parentDiv) {
        resetRezultatOperatiune();

        let pacientId = '';
        let consInvEvalId = '';

        if ($("#consultatie_pacient").val()) {
            pacientId = $("#consultatie_pacient").val();
        } else if ($("#investigatie_pacient").val()) {
            pacientId = $("#investigatie_pacient").val();
        } else {
            pacientId = $("#eval_psiho_pacient").val();
        }

        if ($("#consultatie_id").val()) {
            consInvEvalId = parseInt($("#consultatie_id").val());
        } else if ($("#investigatie_id").val()) {
            consInvEvalId = parseInt($("#investigatie_id").val());
        } else {
            consInvEvalId = parseInt($("#eval_psiho_id").val());
        }

        $.ajax({
            url: "consultatii/get_istoric_pacient",
            type: "GET",
            data: {
                pacient_id: pacientId,
                tip_serviciu: TIP_SERVICIU
            },
            success: function (response) {
                let color = "style='color: black'";

                if (TIP_SERVICIU === TIP_CONSULTATIE || TIP_SERVICIU === TIP_EVAL_PSIHO) {
                    color = "style='color: #1cc88a'";
                } else if (TIP_SERVICIU === TIP_INVESTIGATIE) {
                    color = "style='color: #4e73df'";
                }

                let istoricPacient = "";
                $.each(response.istoric, function (id, istoricServiciu) {
                    if (istoricServiciu.id !== consInvEvalId) {
                        istoricPacient = "<a class='dropdown-item' href='#' " +
                            "onclick='getServiciuIstoric(" + istoricServiciu.id + ", " + TIP_SERVICIU + "); " +
                            "return false;' " + color + "' class='istoric_a'>" + istoricServiciu.denumire + ' | '
                            + istoricServiciu.data + ' | ' + istoricServiciu.nume + ' '
                            + istoricServiciu.prenume + "</a>";

                        parentDiv.append(istoricPacient);
                    }
                });
            },
            error: function (response) {
               addRezultatOperatiuneFail(response);
            }
        });
    };

    return {
        init: function () {
            initTablePacientiConsultatii();
            initEvents();
            initEditareConsultatieFromPacient();
            initStergere();
            initServiciiDocumente();
            initConsultatieFormValidation();
            initInvestigatieFormValidation();
        }
    }
}();

function validateForms() {
    $("#consultatie_form").validate({
        rules: {
            consultatie_nrInreg: {
                required: true
            },
            consultatie_pacient_name: {
                required: true,
                letters: true
            },
            consultatie_medic: {
                required: true
            },
            consultatie_serviciu: {
                required: true
            },
            consultatie_diagnostic: {
                required: true
            },
            consultatie_consultatie: {
                required: true
            },
            consultatie_tratament: {
                required: true
            }
        }
    });

    $("#investigatie_form").validate({
        rules: {
            investigatie_nrInreg: {
                required: true
            },
            investigatie_pacient_name: {
                required: true,
                letters: true
            },
            investigatie_medic: {
                required: true
            },
            investigatie_serviciu: {
                required: true
            },
            investigatie_rezultat: {
                required: true
            },
            investigatie_concluzie: {
                required: true
            }
        }
    });

    $("#eval_psiho_form").validate({
        rules: {
            eval_psiho_nrInreg: {
                required: true
            },
            eval_psiho_pacient_name: {
                required: true,
                letters: true
            },
            eval_psiho_medic: {
                required: true
            },
            eval_psiho_serviciu: {
                required: true
            },
            eval_psiho_rezultat: {
                required: true
            },
            eval_psiho_concluzie: {
                required: true
            },
            eval_psiho_recomandari: {
                required: true
            },
            eval_psiho_obiectiv: {
                required: true
            }
        }
    });
}

function getConsultatieInvestigatieEval(id, tipServiciu) {
    const TIP_CONSULTATIE = '0';
    const TIP_INVESTIGATIE = '1';

    $.ajax({
        url: "consultatii/get_consultatie_investigatie_eval",
        type: "GET",
        data: {
            id: id
        },
        success: function (response) {
            if (tipServiciu === TIP_CONSULTATIE) {
                $(".modal_consultatie_content").html(response);
                $(".add_edit_consultatie_modal").modal("show");
            } else if (tipServiciu === TIP_INVESTIGATIE) {
                $(".modal_investigatie_content").html(response);
                $(".add_edit_investigatie_modal").modal("show");
            } else {
                $(".modal_eval_psiho_content").html(response);
                $(".add_edit_eval_psiho_modal").modal("show");
            }

            validateForms();
        },
        error: function (response) {
            if ($(".servicii_documente_modal").is(':visible')) {
                $(".servicii_documente_modal").modal("hide");
            }

            addShortModal(response, 'fail');
        }
    });
}

function confirmaInvestigatie(tableToRefresh)
{
    resetRezultatOperatiune(true);

    if (!$("#investigatie_form").valid()) {
        addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
        return false;
    }

    $.ajax({
        url: "consultatii/edit_investigatie",
        type: "POST",
        data:
            transformUrlParamsToJson("investigatie_",
                $("#investigatie_form").serialize() +
                "&investigatie_tarif=" + $("#tarif_investigatie").val() +
                "&investigatie_pacient=" + $("#investigatie_pacient").val() +
                "&investigatie_pret=" + $("#investigatie_pret").val()
            ),
        success: function (response) {
            addRezultatOperatiuneSuccess(response, ".add_edit_investigatie_modal");

            if (tableToRefresh) {
                tableToRefresh.ajax.reload();
            }
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function confirmaConsultatie(tableToRefresh) {
    resetRezultatOperatiune(true);

    if (!$("#consultatie_form").valid()) {
        addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
        return false;
    }

    $.ajax({
        url: "consultatii/edit_consultatie",
        type: "POST",
        data:
            transformUrlParamsToJson("consultatie_",
                $("#consultatie_form").serialize() +
                "&consultatie_tarif=" + $("#tarif_consultatie").val() +
                "&consultatie_pacient=" + $("#consultatie_pacient").val() +
                "&consultatie_pret=" + $("#consultatie_pret").val()
            ),
        success: function (response) {
            addRezultatOperatiuneSuccess(response, ".add_edit_consultatie_modal");

            if (tableToRefresh) {
                tableToRefresh.ajax.reload();
            }
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function confirmaEvaluarePsihologica(tableToRefresh)
{
    resetRezultatOperatiune(true);

    if (!$("#eval_psiho_form").valid()) {
        addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
        return false;
    }

    $.ajax({
        url: "consultatii/edit_eval_psiho",
        type: "POST",
        data:
            transformUrlParamsToJson("eval_psiho_",
                $("#eval_psiho_form").serialize() +
                "&eval_psiho_tarif=" + $("#tarif_eval_psiho").val() +
                "&eval_psiho_pacient=" + $("#eval_psiho_pacient").val() +
                "&eval_psiho_pret=" + $("#eval_psiho_pret").val()
            ),
        success: function (response) {
            addRezultatOperatiuneSuccess(response, '.add_edit_eval_psiho_modal');

            if (tableToRefresh) {
                tableToRefresh.ajax.reload();
            }
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function getServiciuIstoric(id, tipServiciu) {
    let TIP_CONSULTATIE = 0;
    let TIP_INVESTIGATIE = 1;

    $.ajax({
        url: "consultatii/get_consultatie_investigatie_eval",
        type: "GET",
        data: {
            id: id,
            view: true
        },
        success: function (response) {
            if ($('.add_edit_consultatie_modal').is(':visible')) {
                $(".add_edit_consultatie_modal").css('filter', 'blur(5px)');
            }
            if ($('.add_edit_investigatie_modal').is(':visible')) {
                $(".add_edit_investigatie_modal").css('filter', 'blur(5px)');
            }
            if ($('.add_edit_eval_psiho_modal').is(':visible')) {
                $(".add_edit_eval_psiho_modal").css('filter', 'blur(5px)');
            }

            if (tipServiciu === TIP_CONSULTATIE) {
                $(".view_modal_consultatie_content").html(response);
                $(".view_consultatie_modal").modal("show");
            } else if (tipServiciu === TIP_INVESTIGATIE) {
                $(".view_modal_investigatie_content").html(response);
                $(".view_investigatie_modal").modal("show");
            } else {
                $(".view_modal_eval_psiho_content").html(response);
                $(".view_eval_psiho_modal").modal("show");
            }
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function tiparesteScrisoareMedicala(id) {
    let input = "<input type='hidden' id='id' name='id' value='" + id + "'>" +
        "<input type='hidden' id='template' name='template' value='scrisoare_medicala.html.twig'>";
    $("<form action='consultatii/pdf_servicii_formulare' method='post'>" + input + "</form>")
        .appendTo('body').submit().remove();
}

function tiparesteBuletinInvestigatie(id) {
    let input = "<input type='hidden' id='id' name='id' value='" + id + "'>" +
        "<input type='hidden' id='template' name='template' value='buletin_investigatie.html.twig'>";
    $("<form action='consultatii/pdf_servicii_formulare' method='post'>" + input + "</form>").appendTo('body')
        .submit().remove();
}

function tiparesteReferatMedical(id) {
    let input = "<input type='hidden' id='id' name='id' value='" + id + "'>" +
        "<input type='hidden' id='template' name='template' value='referat_medical.html.twig'>";
    $("<form action='consultatii/pdf_servicii_formulare' method='post'>" + input + "</form>").appendTo('body')
        .submit().remove();
}

function tiparesteFisaPsihodiagnostic(id) {
    let input = "<input type='hidden' id='id' name='id' value='" + id + "'>" +
        "<input type='hidden' id='template' name='template' value='fisa_psihodiagnostic.html.twig'>";
    $("<form action='consultatii/pdf_servicii_formulare' method='post'>" + input + "</form>").appendTo('body')
        .submit().remove();
}

function tiparesteFisaConsultatii(id) {
    let input = "<input type='hidden' id='id' name='id' value='" + id + "'>" +
        "<input type='hidden' id='template' name='template' value='fisa_consultatii.html.twig'>";
    $("<form action='consultatii/pdf_servicii_formulare' method='post'>" + input + "</form>").appendTo('body')
        .submit().remove();
}

function removeBlur() {
    if ($('.add_edit_consultatie_modal').is(':visible')) {
        $(".add_edit_consultatie_modal").css('filter', 'blur()');
    }
    if ($('.add_edit_investigatie_modal').is(':visible')) {
        $(".add_edit_investigatie_modal").css('filter', 'blur()');
    }
    if ($('.add_edit_eval_psiho_modal').is(':visible')) {
        $(".add_edit_eval_psiho_modal").css('filter', 'blur()');
    }
}

function getServiciiPacient(id) {
    const TIP_CONSULTATIE = 0;
    const TIP_INVESTIGATIE = 1;
    const TIP_EVAL_PSIHO = 2;

    $.ajax({
        url: "pacienti/get_servicii_pacient",
        type: "GET",
        data: {
            pacient_id: id
        },
        success: function (response) {
            $.each(response.serviciiPacient, function (index, serviciu) {
                let numeDocument = "Scrisoare medicala";
                if (serviciu.tipServiciu === TIP_INVESTIGATIE) {
                    numeDocument = "Buletin investigatie";
                }

                let emptyButton = '<td style="width:150px; padding-right: 15px; padding-bottom: 15px;">' +
                    '<button style="width: 150px; height: 40px; display: none;"></td>';

                let row = '<tr id="' + serviciu.consultatieId + '">' +
                    '<td style="width: 100px; padding-right: 15px; padding-bottom: 15px; font-weight: bold;">' +
                    serviciu.dataConsultatie + '</td><td style="width: 120px; font-weight: bold; padding-right: 15px; '
                    + 'padding-bottom: 15px;">' + serviciu.numeMedic +
                    '</td><td style="width: 150px; font-weight: bold; padding-right: 15px; padding-bottom: 15px;">'
                    + serviciu.denumireServiciu + '</td>';

                if ($("#rol_user").val() === 'ROLE_Administrator') {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 40px; height: 40px;" ' +
                        'class="btn btn-danger sterge_consultatie" tip="' + serviciu.tipServiciu +
                        '"><i class="fas fa-trash"></i></button></td>';
                } else {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 25px; height: 40px; display: none;"></button></td>';
                }

                if (!serviciu.inchisa && (serviciu.medicId === parseInt($("#logged_user_id").val()) ||
                    $("#rol_user").val() === 'ROLE_Administrator')) {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 40px; height: 40px; font-weight: bold; " tip="' + serviciu.tipServiciu +
                        '"' + ' class="btn btn-info edit_from_pacient"><i class="fas fa-edit"></i></button></td>';
                } else {
                    row += '<td style="width:40px; padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 40px; height: 40px; display: none;"></td>';
                }

                if (serviciu.tipServiciu === TIP_CONSULTATIE) {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 150px; height: 40px; font-size: 14px;" ' +
                        'class="btn btn-danger scrisoare_from_pacient" onclick="tiparesteScrisoareMedicala(' +
                        serviciu.consultatieId + ')">' +
                        numeDocument + '</button></td>';
                } else if (serviciu.tipServiciu === TIP_INVESTIGATIE) {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 150px; height: 40px; font-size: 14px;" ' +
                        'class="btn btn-danger buletin_from_pacient" onclick="tiparesteBuletinInvestigatie(' +
                        serviciu.consultatieId + ')">' +
                        numeDocument + '</button></td>';
                } else if (serviciu.tipServiciu === TIP_EVAL_PSIHO) {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 150px; height: 40px; font-size: 14px;" ' +
                        'class="btn btn-success eval_psiho_from_pacient" onclick="tiparesteFisaPsihodiagnostic(' +
                        serviciu.consultatieId + ')">' +
                        'Fisa psihodiag.</button></td>';
                }

                if (serviciu.tipServiciu === TIP_CONSULTATIE) {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 150px; height: 40px; font-size: 14px;" ' +
                        'class="btn btn-success referat_from_pacient" onclick="tiparesteReferatMedical(' +
                        serviciu.consultatieId + ')">' +
                        'Referat medical</button></td>';
                } else {
                    row += emptyButton;
                }

                if (serviciu.tipServiciu === TIP_CONSULTATIE) {
                    row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                        '<button style="width: 150px; height: 40px; font-size: 14px;" ' +
                        'class="btn btn-warning fisa_consultatie_from_pacient" onclick="tiparesteFisaConsultatii(' +
                        serviciu.consultatieId + ')">' +
                        'Fisa de consultatii</button></td>';
                } else {
                    row += emptyButton;
                }

                row += '<td style="padding-right: 15px; padding-bottom: 15px;">' +
                    '<button disabled style="width: 150px; height: 40px; font-size: 14px;" ' +
                    'class="btn btn-outline-secondary">Alte documente</button></td></tr>';

                $("#table_servicii_documente").append(row);
            });
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function transformUrlParamsToJson(tip, urlParams)
{
    const params = new URLSearchParams(urlParams.replaceAll(tip, ""));

    return Object.fromEntries(params.entries());
}

$(document).ready(function () {
    consultatii.init();
});
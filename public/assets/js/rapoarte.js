let rapoarte = function () {
    let tableRapoarteColaboratori;

    const IANUARIE = "1";

    let initTableRapoarteColaboratori = function () {
        tableRapoarteColaboratori = $('#rapoarte_colaboratori').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "rapoarte/list_rapoarte_colaboratori",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                { "orderable": false, "targets": [0] }
            ],
            "order": [[ 1, "desc" ]],
            "columns": [
                {
                    title: "Actiuni",
                    width: 50,
                    render: function (data, type, row) {
                        let actiuni = "<a href='#' onclick='pdfPlataColaborator(" + row.id + "); return false;' "
                            + "class='btn btn-outline-danger btn-circle btn-sm pdf_raport_colaborator' " +
                            'title="Descarca raport" style="margin-right: 3px;"><i class="fas fa-file-pdf"></i></a>';

                        if (row.stare === 'neplatita' && $("#rol_user").val() === 'ROLE_Administrator') {
                            actiuni += '<a href="#" class="btn btn-info btn-circle btn-sm plateste_colaborator_grid" ' +
                                'title="Plateste colaborator" style="margin-right: 3px;"><i class="fas fa-credit-card">'
                                + '</i></a>';
                        }

                        return actiuni;
                    }
                },
                {
                    title: 'Data generarii',
                    data: 'dataGenerarii',
                    name: 'dataGenerarii',
                    width: 150
                },
                {
                    title: "Medic",
                    data: "nume_medic",
                    name: "nume_medic"
                },
                {
                    title: "Owner",
                    data: "denumire_owner",
                    name: "denumire_owner"
                },
                {
                    title: "An",
                    data: "an",
                    name: "an"
                },
                {
                    title: "Luna",
                    data: "luna",
                    name: "luna"
                },
                {
                    title: "Suma de plata",
                    data: "sumaDePlata",
                    name: "sumaDePlata",
                    width: 150
                },
                {
                    title: 'Stare',
                    data: 'stare',
                    name: 'stare'
                }
            ]
        } );
    };

    let raportColaboratoriFormValidation = function () {
        $( "#add_raport_colaboratori_form" ).validate({
            rules: {
                owner: {
                    required: true
                },
                medic: {
                    required: true
                },
                an: {
                    required: true
                },
                luna: {
                    required: true
                },
                suma: {
                    required: true
                }
            }
        });
    };

    let initAdaugaRaportColaboratori = function () {
        $(".add_raport_colaboratori").click(function () {
            raportColaboratoriFormValidation();

            initLunaAn();

            $(".add_raport_colaboratori_modal").modal("show");
        });
    };

    let initEvents = function () {
        $(".add_raport_colaboratori_modal").on("hidden.bs.modal", function () {
            $("#raport_colaboratori_id").val('');
            $(".confirma_raport_colaboratori").prop('disabled', true);
            $(".confirma_plateste_colaboratori").prop('disabled', true)
        });

        $("#owner").on("change", function () {
            resetRezultatOperatiune();

            resetOwnerChange();
            getColaboratoriOwner($(this).val());
        });

        $("body").on("click", ".plateste_colaborator_grid", function () {
            let raportId = this.closest('tr').id;
            $("#raport_plateste_id").val(raportId);

            $("#plateste_colaborator_text").html("Faceti plata pentru colaboratorul selectat?");

            $(".modal_plateste_colaborator").modal("show");
        });

        $(".confirma_plata_colaborator").click(function () {
           resetRezultatOperatiune(true);

            $.ajax({
                url: "rapoarte/plata_colaborator",
                type: "POST",
                data: {
                    formData: $("#form_plata_colaborator").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.modal_plateste_colaborator');

                    tableRapoarteColaboratori.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
        
        $(".confirma_raport_colaboratori").click(function () {
            resetRezultatOperatiune(true);

            $(".spinner").hide();
            $(".spinner_conf_raport").show();

            if (!$("#add_raport_colaboratori_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "rapoarte/add_raport_colaboratori",
                type: "POST",
                data: {
                    formData: $("#add_raport_colaboratori_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response);

                    $(".confirma_raport_colaboratori").prop('disabled', true);
                    $(".confirma_plateste_colaboratori").prop('disabled', false);
                    $("#raport_colaboratori_id").val(response.id);

                    tableRapoarteColaboratori.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });

        $(".confirma_plateste_colaboratori").click(function () {
            resetRezultatOperatiune(true);

            $(".spinner").hide();
            $(".spinner_conf_plata_raport").show();

            if (!$("#add_raport_colaboratori_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "rapoarte/plateste_colaborator",
                type: "POST",
                data: {
                    formData: $("#add_raport_colaboratori_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_raport_colaboratori_modal');

                    tableRapoarteColaboratori.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });

        let resetOwnerChange = function () {
            $(".confirma_raport_colaboratori").prop('disabled', true);
            $(".confirma_plateste_colaboratori").prop('disabled', true);
            $("#suma").val("");
        };

        $("#medic, #an, #luna").on("change", function () {
            resetOwnerChange();

            if ($("#owner").val() !== '' && $("#medic").val() !== '' && $("#an").val() !== '' && $("#luna").val() !== '') {
                calculeazaSumaDePlataColaborator($("#owner").val(), $("#medic").val(), $("#an").val(), $("#luna").val());
            }
        });
    };

    let calculeazaSumaDePlataColaborator = function () {
        resetRezultatOperatiune();

        $.ajax({
            url: "rapoarte/calculeaza_plata_colaborator",
            type: "GET",
            data: {
                formData: $("#add_raport_colaboratori_form").serialize()
            },
            success: function (response) {
                let sumaDePlata = response.totalDePlata;
                $("#suma").val(sumaDePlata);

                switch (response.stare) {
                    case 'nou':
                        $(".confirma_raport_colaboratori").prop('disabled', false);
                        break;

                    case 'neplatit':
                        $("#raport_colaboratori_id").val(response.id);
                        $(".rezultat_operatiune").html(response.message)
                            .addClass('rezultat_operatiune_info').show();
                        $(".confirma_raport_colaboratori").prop('disabled', true);
                        $(".confirma_plateste_colaboratori").prop('disabled', false);
                        break;

                    default:
                        $(".rezultat_operatiune").html(response.message)
                            .addClass('rezultat_operatiune_fail').show();
                        $(".confirma_raport_colaboratori").prop('disabled', true);
                        $(".confirma_plateste_colaboratori").prop('disabled', true);
                        break;
                }
            },
            error: function (response) {
                addRezultatOperatiuneFail(response);
            }
        });
    };

    let initLunaAn = function () {
        let lunaAnterioara = moment().subtract(1, 'month').format("M");
        let an = moment().format("YYYY");

        if (moment().format("M") === IANUARIE) {
            an -= 1;
        }

        $("#luna").val(lunaAnterioara);
        $("#an").val(an);
    };

    let getColaboratoriOwner = function (ownerId) {
        resetRezultatOperatiune();

        $.ajax({
            url: "rapoarte/get_colaboratori_owner",
            type: "GET",
            data: {
                ownerId: ownerId
            },
            success: function (response) {
                $("#medic").empty().append('<option value="">-- Alege --</option>');

                $.each(response.data, function (idx, medic) {
                    let option = '<option value="' + medic.id + '">' + medic.nume + " " + medic.prenume +
                        '</option>';

                    $("#medic").append(option);
                });
            },
            error: function (response) {
                addRezultatOperatiuneFail(response);
            }
        });
    }

    return {
        init: function () {
            initTableRapoarteColaboratori();
            initAdaugaRaportColaboratori();
            initEvents();
        }
    }
}();

function pdfPlataColaborator(raportId) {
    let input = "<input type='hidden' id='id' name='id' value='" + raportId + "'>";
    $("<form action='rapoarte/pdf_plata_colaborator' method='post'>" + input + "</form>").appendTo('body')
        .submit().remove();
}

$(document).ready(function () {
    rapoarte.init();
});
let programari = function () {
    let tableProgramari;
    const COL_STARE = 7;
    const COL_DATA = 5;
    const ONORATA = 1;
    const NEONORATA = 0;

    const stareProgramari = {
        2: {
            text: "Viitoare",
            class: "badge badge-pill badge-success"
        },
        1: {
            text: "Onorata",
            class: "badge badge-pill badge-info"
        },
        0: {
            text: "Neonorata",
            class: "badge badge-pill badge-warning"
        }
    };

    const ROL_USER_LOGAT = $("#rol_user").val();

    const ROL_MEDIC = "ROLE_Medic";
    const ROL_PSHIHOLOG = "ROLE_Psiholog";
    const ROL_ADMIN = "ROLE_Administrator";

    let initTableProgramari = function () {
        tableProgramari = $('#programari').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "programari/list_programari",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                {
                    "orderable": false, "targets": [COL_STARE]
                }
            ],
            "order": [[ COL_DATA, "asc" ]],
            "columns": [
                {
                    title: "Actiuni",
                    width: 50,
                    render: function (data, type, row) {
                        let actiuni = '';

                        if (!row.anulata && row.stare !== ONORATA) {
                            if (([ROL_MEDIC, ROL_PSHIHOLOG].includes(ROL_USER_LOGAT) &&
                                row.medicId === parseInt($("#logged_user_id").val())) || ROL_ADMIN === ROL_USER_LOGAT) {
                                actiuni += '<a href="#" class="btn btn-danger btn-circle btn-sm anuleaza_programare"' +
                                    ' title="Anuleaza" style="margin-right: 3px;"><i class="fas fa-trash"></i></a>'

                                actiuni += '<a href="#" class="btn btn-info btn-circle btn-sm editeaza_programare" ' +
                                    'title="Editeaza" style="margin-right: 3px;"><i class="fas fa-edit"></i></a>';
                            }

                            if (row.stare !== NEONORATA) {
                                actiuni += '<a href="javascript:trimiteEmailProgramare(' + row.id + ')"' +
                                    ' class="btn btn-outline-dark btn-circle btn-sm trimite_email" ' +
                                    'title="Trimite email" style="margin-right: 3px;"><i class="fas fa-envelope"></i></a>';
                            }


                            actiuni += '<a href="javascript:deschideConsultatie(' + row.id + ','
                                + row.pacientId + ', \'' + row.numePacient + '\')" ' +
                                'class="btn btn-primary btn-circle btn-sm deschide_consultatie" ' +
                                'title="Deschide consultatie" style="margin-right: 3px;">' +
                                '<i class="fas fa-book-medical"></i></a>';
                        }

                        if (row.anulata || row.stare === ONORATA) {
                            return '<button disabled class="btn btn-circle btn-sm" title="Nicio actiune disponibila">' +
                                '<i class="fas fa-smile fa-2x"></i></button>';
                        }

                        return actiuni;
                    }
                },
                {
                    title: "Pacient",
                    data: "numePacient",
                    name: "numePacient"
                },
                {
                    title: "Medic",
                    data: "numeMedic",
                    name: "numeMedic",
                },
                {
                    title: "Serviciu",
                    data: "denumireServiciu",
                    name: "denumireServiciu"
                },
                {
                    title: "Pret RON",
                    data: "pret",
                    name: "pret"
                },
                {
                    title: "Data",
                    data: "data",
                    name: "data",
                    width: 55
                },
                {
                    title: "Ora",
                    data: "ora",
                    name: "ora",
                },
                {
                    title: "Stare",
                    data: "stare",
                    name: "stare",
                    render: function (data, type, row) {
                        if (row.anulata) {
                            return '<span class="badge badge-pill badge-danger">Anulata</span>';
                        }

                        const info = stareProgramari[data];
                        return '<span class="' + info.class + '">' + info.text + '</span>';
                    }
                }
            ]
        } );
    };

    let initAdaugaProgramare = function () {
        $(".add_programare").click(function () {
            $("#programare_id").val("");

            validateForm();

            $(".titlu_add_edit_programare").text("Adauga programare");
            $(".add_edit_programare_modal").modal("show");
        });
    };

    let validateForm = function () {
        $("#add_edit_programare_form" ).validate({
            rules: {
                programare_pacient: {
                    required: true,
                },
                programare_medic: {
                    required: true
                },
                programare_pret_serviciu: {
                    required: true
                },
                programare_data: {
                    dateDMY: true,
                    required: true
                },
                programare_ora: {
                    time: true,
                    required: true
                },
                programare_pret: {
                    required: true
                }
            },
        });
    };

    let initSearchPreturiMedic = function () {
        $("#programare_medic").on("change", function () {
            $("#programare_pret_serviciu").empty();

            if ($("#programare_medic").val()) {
                let options = '<option value="">- Alegeti -</option>';

                getPreturiMedic($("#programare_medic").val()).then(function(preturi) {
                    $.each(preturi, function (idx, serviciu) {
                        options += '<option value="' + serviciu.id + '" pret="' + serviciu.pret + '">' +
                            serviciu.denumire + '</option>';
                    });
                    $("#programare_pret_serviciu").append(options);
                });
            }
        });
    };

    let getPreturiMedic = function (medicId) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: "/servicii_preturi/get_preturi_medic",
                type: "GET",
                data: { medic: medicId },
                success: function (response) {
                    resolve(response.preturiMedic);
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                    reject(response);
                }
            });
        });
    };

    let initEditeazaProgramare = function () {
        $("body").on("click", ".editeaza_programare", function () {
            validateForm();

            let id = this.closest('tr').id;
            $("#programare_id").val(id);

            $.ajax({
                url: "programari/get_programare",
                type: "GET",
                data: {
                    id: id
                },
                success: function (response) {
                    let programare = response.programareData;

                    $("#programare_id").val(programare.id);
                    $("#programare_medic").val(programare.medicId);

                    fetchPacienti(programare.cnp).then(function(response) {
                        let pacient = response.pacienti[0];

                        const option = new Option(
                            pacient.numePacient,
                            pacient.id,
                            true,
                            true
                        );

                        $("#programare_pacient")
                            .append(option)
                            .trigger("change");
                    });

                    getPreturiMedic(programare.medicId).then(function(preturi) {
                        $("#programare_pret_serviciu").empty();
                        let options = '<option value="">- Alegeti -</option>';

                        $.each(preturi, function (idx, serviciu) {
                            let selected = '';

                            if (serviciu.id === programare.pretId) {
                                selected = 'selected';
                            }

                            options += '<option ' + selected + ' value="' + serviciu.id + '" pret="' + serviciu.pret +
                                '">' + serviciu.denumire + '</option>';
                        });
                        $("#programare_pret_serviciu").append(options);
                    }).catch(function(error) {
                        console.error(error);
                    });

                    $("#programare_pret").val(programare.pret);
                    $("#programare_data").val(programare.data);
                    $("#programare_ora").val(programare.ora);

                    $(".titlu_add_edit_programare").text("Editeaza programare");
                    $(".add_edit_programare_modal").modal("show");
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                },
            });
        });
    };

    let initEvents = function () {
        $(".confirma_programare").click(function () {
            resetRezultatOperatiune(true);

            if (!$("#add_edit_programare_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "/programari/add_edit_programare",
                type: "POST",
                data: {
                    form: $("#add_edit_programare_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_programare_modal');

                    tableProgramari.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });

        $("#programare_pret_serviciu").on("change", function () {
            $("#programare_pret").val($("#programare_pret_serviciu option:selected").attr("pret"));
            $("#programare_pret").valid();
        });

        let today = new Date();
        $('#programare_data').datepicker({
            format: 'dd-mm-yyyy',
            startDate: today,
            autoclose: true,
            todayHighlight: true,
            language: 'ro',

        })
        $('#programare_data').on('hide', function(e) {
            e.stopPropagation();
            $("#programare_data").valid();
        });
        $('#programare_data').on('show', function(e) {
            e.stopPropagation();
            $("#programare_data").valid();
        });
        $('#programare_data').on('change', function(e) {
            e.stopPropagation();
            $("#programare_data").valid();

            const now = new Date();
            const minutes = now.getMinutes();
            let nextQuarterHour = now;
            if (minutes % 15 !== 0) {
                let minutesToAdd = 15 - (minutes % 15);
                nextQuarterHour = new Date(now.getTime() + minutesToAdd * 60 * 1000);
            }
            $("#programare_ora").timepicker('setTime', nextQuarterHour);
        });

        $("#programare_ora").timepicker({
            showMeridian: false,
            showInputs: true,
            disableFocus: false,
            minuteStep: 1,
            icons: {
                up: 'fas fa-chevron-up',
                down: 'fas fa-chevron-down'
            },
        });

        $('#programare_ora').on('changeTime.timepicker', function(e) {
            const [day, month, year] = $("#programare_data").val().split("-");
            const targetDate = new Date(year, month - 1, day);
            const now = new Date();

            if ($("#programare_data").val() !== '' && targetDate < now) {
                const selected = e.time.value;

                const currentMinutes = now.getHours() * 60 + now.getMinutes();

                const [h, m] = selected.split(':').map(Number);
                const selectedMinutes = h * 60 + m;

                const minutes = now.getMinutes();

                let nextQuarterHour = now;
                if (minutes % 15 !== 0) {
                    let minutesToAdd = 15 - (minutes % 15);
                    nextQuarterHour = new Date(now.getTime() + minutesToAdd * 60 * 1000);
                }

                if (selectedMinutes < currentMinutes) {
                    $(this).timepicker('setTime', nextQuarterHour);
                }
            }
        });

        $("#programare_ora").on('hide.timepicker', function(e) {
            resetRezultatOperatiune();

            if ($("#add_edit_programare_form").valid()) {
                $.ajax({
                    url: "/programari/check_availability",
                    type: "GET",
                    data: {
                        form: $("#add_edit_programare_form").serialize()
                    },
                    error: function (response) {
                        addRezultatOperatiuneFail(response);
                    }
                });
            }
        });

        $(".add_consultatie_investigatie_modal").on('hidden.bs.modal', function (e) {
           tableProgramari.ajax.reload();
        });
    };

    let initAnuleazaProgramare = function () {
        $("body").on("click", ".anuleaza_programare", function () {
            let programareId = this.closest('tr').id;
            $("#modal_stergere_id").val(programareId);
            $('.confirma_stergere').addClass('cancel_programare');

            $("#stergere_text").html("Anulezi programarea selectata?");

            $(".modal_stergere").modal("show");
        });

        $("body").on('click', '.cancel_programare', function () {
            resetRezultatOperatiune(true);

            $.ajax({
                url: "/programari/anuleaza_programare",
                type: "POST",
                data: {
                    id: $("#modal_stergere_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.modal_stergere');

                    tableProgramari.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    return {
        init: function () {
            initTableProgramari();
            initAdaugaProgramare();
            initEditeazaProgramare();
            initSearchPacient("#programare_pacient");
            initSearchPreturiMedic();
            initAnuleazaProgramare();
            initEvents();
        }
    }
}();

function trimiteEmailProgramare(programareId) {
    $.ajax({
        url: "/programari/trimite_email_programare",
        type: "POST",
        data: {
            id: programareId
        },
        success: function (response) {
            addShortModal(response);
        },
        error: function (response) {
            addShortModal(response, 'fail');
        }
    });
}

function deschideConsultatie(programareId, pacientId, numePacient) {
    $("select[multiple]").css('overflow-x', 'auto');

    $(".titlu_cons_inv_modal").css('font-weight', 'bold')
        .text('Pacient ' + numePacient + ' - Adauga consultatie / investigatie');

    $("#deschide_cons_inv_pacient_id").val(pacientId);
    $("#deschide_cons_inv_programare_id").val(programareId);

    cautaPreturi();

    $(".add_consultatie_investigatie_modal").modal("show");
}

$(document).ready(function () {
    programari.init();
});
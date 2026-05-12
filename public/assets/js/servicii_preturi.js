let servicii_preturi = function () {
    const ROL_RECEPTIE = "ROLE_Receptie";
    const ROL_MEDIC = "ROLE_Medic";

    let table;

    let initAddServiciu = function () {
        $(".add_serviciu").click(function () {
            $( "#add_serviciu_form" ).validate({
                rules: {
                    add_denumire_serviciu: {
                        required: true
                    },
                    add_tip_serviciu: {
                        required: true
                    }
                }
            });

            $(".add_serviciu_modal").modal("show");
        });

        $(".confirma_adauga_serviciu").click(function () {
            resetRezultatOperatiune(true);

            if (!$("#add_serviciu_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "servicii_preturi/add_serviciu",
                type: "POST",
                data: {
                    form: $("#add_serviciu_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_serviciu_modal');

                    window.location.reload();
                },
                error: function (response) {
                   addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    let initAddPret = function () {
        $(".add_pret").click(function () {
            $("#pret_id").val("");

            validatePretForm();

            $(".titlu_pret").text("Adauga tarif");
            $(".add_edit_pret_modal").modal("show");
        });

        $(".confirma_pret").click(function () {
            resetRezultatOperatiune(true);

            if (!$("#add_edit_pret_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "/servicii_preturi/add_edit_pret",
                type: "POST",
                data: {
                    form: $("#add_edit_pret_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_pret_modal');

                    table.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    let initEditPret = function () {
        $("body").on("click", ".editeaza_pret", function () {
            validatePretForm();

            let id = this.closest('tr').id;
            $("#pret_id").val(id);

            $.ajax({
                url: "servicii_preturi/get_pret",
                type: "GET",
                data: {
                    id: id
                },
                success: function (response) {
                    let responseData = response.pretData;

                    $("#pret_medic").val(responseData.medicId);
                    $("#pret_serviciu").val(responseData.serviciuId);
                    $("#pret_pret").val(responseData.pret);
                    $("#pret_owner").val(responseData.ownerId);
                    $("#pret_procentaj_medic").val(responseData.procentajMedic);

                    $(".titlu_pret").text("Editeaza tarif");
                    $(".add_edit_pret_modal").modal("show");
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });
    };

    let initStergePret = function () {
        $("body").on("click", ".sterge_pret", function () {
            let pretId = this.closest('tr').id;
            $("#modal_stergere_id").val(pretId);

            $("#stergere_text").html("Stergeti tariful selectat?");

            $(".modal_stergere").modal("show");
        });

        $(".confirma_stergere").click(function () {
            resetRezultatOperatiune(true);

            $.ajax({
                url: "servicii_preturi/sterge_pret",
                type: "POST",
                data: {
                    id: $("#modal_stergere_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.modal_stergere');

                    table.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    var validatePretForm = function () {
        $( "#add_edit_pret_form" ).validate({
            rules: {
                pret_medic: {
                    required: true
                },
                pret_owner: {
                    required: true
                },
                pret_serviciu: {
                    required: true
                },
                pret_pret: {
                    required: true,
                    digits: true
                },
                pret_procentaj_medic: {
                    required: true,
                    digits: true,
                    max: 100,
                    min: 0
                }
            }
        });
    };

    let initTable = function () {
        table = $('#servicii_preturi').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "servicii_preturi/list",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                {
                    "orderable": false, "targets": 0
                }
            ],
            "order": [[ 1, "desc" ]],
            "columns": [
                {
                    title: "Actiuni",
                    width: 50,
                    render: function () {
                        if ($("#rol_user").val() === ROL_RECEPTIE || $("#rol_user").val() === ROL_MEDIC) {
                            return '<button disabled class="btn btn-circle btn-sm" title="Nicio actiune disponibila">' +
                                '<i class="fas fa-smile fa-2x"></i></button>';
                        }

                        return '<a href="#" class="btn btn-danger btn-circle btn-sm sterge_pret" title="Sterge" style="margin-right: 3px;">' +
                            '<i class="fas fa-trash"></i></a>' +
                            '<a href="#" class="btn btn-info btn-circle btn-sm editeaza_pret" title="Editeaza"><i class="fas fa-edit">' +
                            '</i></a>';
                    }
                },
                {
                    title: "Serviciu",
                    data: "denumireServiciu",
                    name: "denumireServiciu",
                    width: 250
                },
                {
                    title: "Owner",
                    data: "denumireOwner",
                    name: "denumireOwner",
                    width: 250
                },
                {
                    title: "Medic",
                    data: "medic",
                    name: "medic",
                    width: 250,
                    render: function (data, type, row) {
                        return row.numeMedic + " " + row.prenumeMedic;
                    }
                },
                {
                    title: "Tarif RON",
                    data: "pret",
                    name: "pret"
                },
                {
                    title: "Procentaj medic",
                    data: "procentajMedic",
                    name: "procentajMedic"
                }
            ]
        } );
    };

    return {
        init: function () {
            initTable();
            initAddServiciu();
            initAddPret();
            initEditPret();
            initStergePret();
        }
    }
}();

$(document).ready(function () {
    servicii_preturi.init();
});
let owners = function() {
    let tableOwners;
    const ROL_ADMIN = "ROLE_Administrator";

    let initTableOwners = function () {
        tableOwners = $('#owners').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "owners/list",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                { "orderable": false, "targets": [0,2,3,4,5,6] }
            ],
            "order": [[ 1, "asc" ]],
            "columns": [
                {
                    title: "Actiuni",
                    render: function () {
                        let actiuni = '';

                        if ($("#rol_user").val() !== ROL_ADMIN) {
                            return '<button disabled class="btn btn-circle btn-sm" title="Nicio actiune disponibila">' +
                                '<i class="fas fa-smile fa-2x"></i></button>';
                        }

                        actiuni += '<a style="margin-right: 3px;" href="#" ' +
                            'class="btn btn-info btn-circle btn-sm editeaza_owner" title="Editeaza">'
                            + '<i class="fas fa-edit"></i></a>';
                        actiuni += '<a href="#" class="btn btn-danger btn-circle btn-sm sterge_owner"' +
                            ' title="Sterge" style="margin-right: 3px;"><i class="fas fa-trash"></i></a>';

                        return actiuni;
                    }
                },
                {
                    title: "Denumire",
                    data: "denumire",
                    name: "denumire"
                },
                {
                    title: "Adresa",
                    data: "adresa",
                    name: "adresa"
                },
                {
                    title: "CUI",
                    data: "cui",
                    name: "cui",
                },
                {
                    title: "Nr. Reg. Comertului",
                    data: "regCom",
                    name: "regCom",
                },
                {
                  title: "Capital social",
                  data: "capitalSocial",
                  name: "capitalSocial",
                },
                {
                    title: "Serie factura",
                    data: "serieFactura",
                    name: "serieFactura",
                },
                {
                    title: "Cont bancar",
                    data: "contBancar",
                    name: "contBancar",
                },
                {
                    title: "Banca",
                    data: "banca",
                    name: "banca"
                }
            ]
        });
    }
    let initStergeOwner = function () {
        $("body").on("click", ".sterge_owner", function () {
            let ownerId = this.closest('tr').id;
            $("#modal_stergere_id").val(ownerId);
            $('.confirma_stergere').addClass('delete_owner');

            $("#stergere_text").html("Stergeti owner-ul selectat?");

            $(".modal_stergere").modal("show");
        });

        $("body").on('click', '.delete_owner', function () {
            resetRezultatOperatiune(true);

            $.ajax({
                url: "owners/sterge_owner",
                type: "POST",
                data: {
                    id: $("#modal_stergere_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.modal_stergere');

                    tableOwners.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };


    let initAddEditOwner = function () {
        $("body").on("click", ".add_owner", function () {
            $("#owner_id").val("");
            validateOwnerForm();
            $(".owner_content").load("/owners/get_owner");
            $(".titlu_modal_owner").text("Adauga firma");
            $(".add_edit_owner_modal").modal("show");
        });

        $("body").on("click", ".editeaza_owner", function () {
            let id = this.closest('tr').id;
            $("#owner_id").val(id);

            $.ajax({
                url: "owners/get_owner",
                type: "GET",
                data: {
                    id: id
                },
                success: function (response) {
                    $(".owner_content").html(response);
                    $(".titlu_modal_owner").text("Editeaza firma");
                    $(".add_edit_owner_modal").modal("show");

                    validateOwnerForm();
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });

        $("body").on("click", ".confirma_owner", function () {
            validateOwnerForm();
            resetRezultatOperatiune(true);

            if (!$("#add_edit_owner_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "owners/add_edit_owner",
                type: "POST",
                data: {
                    form: $("#add_edit_owner_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_owner_modal');

                   tableOwners.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    return {
        init: function () {
            initTableOwners();
            initAddEditOwner();
            initStergeOwner();
        }
    }
}();

function validateOwnerForm() {
    $("#add_edit_owner_form").validate({
        rules: {
            denumire: {
                required: true
            },
            cui: {
                required: true,
                cui: true
            },
            adresa: {
                required: true
            },
            cont: {
                contBancar: true
            },
            serie_factura: {
                required: true,
            },
            reg_com: {
                regCom: true
            },
            cap_soc: {
                digits: true,
            }
        }
    });
}

$(document).ready(function () {
    owners.init();
});
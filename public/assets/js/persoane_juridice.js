let pj = function() {
    let tableClientiPj;
    const ROL_ADMIN = "ROLE_Administrator";

    let initTableClientiPj = function () {
        tableClientiPj = $('#clienti_pj').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "clienti_pj/list",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                { "orderable": false, "targets": [0,2,3] },
                { "width": "75px", "targets": [0] }
            ],
            "order": [[ 1, "asc" ]],
            "autoWidth": false,
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
                            'class="btn btn-info btn-circle btn-sm editeaza_pj" title="Editeaza">'
                            + '<i class="fas fa-edit"></i></a>';
                        actiuni += '<a href="#" class="btn btn-danger btn-circle btn-sm sterge_pj"' +
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
                }
            ]
        });
    }
    let initStergePj = function () {
        $("body").on("click", ".sterge_pj", function () {
            let clientPjId = this.closest('tr').id;
            $("#modal_stergere_id").val(clientPjId);
            $('.confirma_stergere').addClass('delete_pj');

            $("#stergere_text").html("Stergeti clientul selectat?");

            $(".modal_stergere").modal("show");
        });

        $("body").on('click', '.delete_pj', function () {
            resetRezultatOperatiune(true);

            $.ajax({
                url: "/sterge_client_pj",
                type: "POST",
                data: {
                    id: $("#modal_stergere_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.modal_stergere');

                    tableClientiPj.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };


    let initAddEditPj = function () {
        $("body").on("click", ".add_pj", function () {
            $("#pj_id").val("");
            validatePjForm();
            $(".pj_content").load("/get_client_pj");
            $(".titlu_modal_pj").text("Adauga client");
            $(".add_edit_pj_modal").modal("show");
        });

        $("body").on("click", ".editeaza_pj", function () {
            let id = this.closest('tr').id;
            $("#pj_id").val(id);

            $.ajax({
                url: "/get_client_pj",
                type: "GET",
                data: {
                    id: id
                },
                success: function (response) {
                    $(".pj_content").html(response);
                    $(".titlu_modal_pj").text("Editeaza firma");
                    $(".add_edit_pj_modal").modal("show");

                    validatePjForm();
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });

        $("body").on("click", ".confirma_pj", function () {
            validatePjForm();
            resetRezultatOperatiune(true);

            if (!$("#add_edit_pj_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "/add_edit_pj",
                type: "POST",
                data: {
                    form: $("#add_edit_pj_form").serialize()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_pj_modal');

                    tableClientiPj.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    return {
        init: function () {
            initTableClientiPj();
            initAddEditPj();
            initStergePj();
        }
    }
}();

function validatePjForm() {
    $("#add_edit_pj_form").validate({
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
            }
        }
    });
}

$(document).ready(function () {
    pj.init();
});
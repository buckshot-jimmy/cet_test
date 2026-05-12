let utilizatori = function () {
    let table;

    const ROLE_MEDIC = "ROLE_Medic";
    const ROLE_ADMIN = "ROLE_Administrator";
    const ROLE_PSHIHOLOG = 'ROLE_Psiholog';

    let initAddUser = function () {
        $("#rol").on("change", function () {
            let role = "ROLE_" + $("#rol option:selected").text();

            if (role === ROLE_MEDIC || role === ROLE_PSHIHOLOG) {
                $("#cod_parafa").prop("disabled", false);
                $("#specialitate").prop("disabled", false);
                $("#titulatura").prop("disabled", false);
            } else {
                $("#cod_parafa").val("").prop("disabled", true);
                $("#specialitate").val("").prop("disabled", true);
                $("#titulatura").val("").prop("disabled", true);
            }
        });

        $(".add_user").click(function () {
            $("#user_id").val("");
            $("#rol").val("");

            $("#password").prop("disabled", false);

            validateUserForm();

            $("#password").val("");
            $(".titlu_user").text("Adauga utilizator");
            $(".add_edit_user_modal").modal("show");
        });

        $(".confirma_utilizator").click(function () {
            resetRezultatOperatiune(true);

            if (!$("#add_edit_user_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "user/add_edit_user",
                type: "POST",
                data: {
                    form: $("#add_edit_user_form").serialize() + "&editUserId=" + $("#user_id").val() +
                        "&loggedUserId=" + $("#logged_user_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_user_modal');

                    table.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    let initEditUser = function () {
        let rolesDoctor = [ROLE_MEDIC, ROLE_PSHIHOLOG];

        $("#rol").on("change", function () {
            let role = "ROLE_" + $("#rol option:selected").text();

            if (role === ROLE_MEDIC || role === ROLE_PSHIHOLOG) {
                $("#cod_parafa").prop("disabled", false);
                $("#specialitate").prop("disabled", false);
                $("#titulatura").prop("disabled", false);
            } else {
                $("#cod_parafa").val("").prop("disabled", true);
                $("#specialitate").val("").prop("disabled", true);
                $("#titulatura").val("").prop("disabled", true);
            }
        });

        $("body").on("click", ".editeaza_utilizator", function () {
            $("#password").val("");

            if ($("#rol_user").val() !== ROLE_ADMIN) {
                $("#password").prop("disabled", true);
            }

            validateUserForm();

            let id = this.closest('tr').id;
            $("#user_id").val(id);

            $.ajax({
                url: "user/get_user",
                type: "GET",
                data: {
                    id: id
                },
                success: function (response) {
                    let responseData = response.userData;

                    $("#nume").val(responseData.nume);
                    $("#prenume").val(responseData.prenume);
                    $("#telefon").val(responseData.telefon);
                    $("#email").val(responseData.email);
                    $("#cod_parafa").val(responseData.codParafa);
                    $("#rol").val(responseData.rol);
                    $("#specialitate").val(responseData.specialitate);
                    $("#titulatura").val(responseData.titulatura);
                    $("#username").val(responseData.username);

                    if (!rolesDoctor.includes("ROLE_" + $("#rol option:selected").text())) {
                        $("#cod_parafa").prop("disabled", true);
                        $("#specialitate").prop("disabled", true);
                        $("#titulatura").prop("disabled", true);
                    }

                    $(".titlu_user").text("Editeaza utilizator");
                    $(".add_edit_user_modal").modal("show");
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });

        $(".confirma_editare_utilizator").click(function () {
            resetRezultatOperatiune(true);

            if (!$("#add_edit_user_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "user/add_edit_user",
                type: "POST",
                data: {
                    form: $("#add_edit_user_form").serialize() + "&editUserId=" + $("#user_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_user_modal');

                    table.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    var validateUserForm = function () {
        $( "#add_edit_user_form" ).validate({
            rules: {
                nume: {
                    required: true,
                    letters: true
                },
                prenume: {
                    required: true,
                    letters: true
                },
                username: {
                    required: true
                },
                telefon: {
                    required: true,
                    digits: true
                },
                email: {
                    required: true
                },
                rol: {
                    required: true
                },
                cod_parafa: {
                    required: function(element){
                        return "ROLE_" + $("#rol option:selected").text() === ROLE_MEDIC;
                    },
                    parafa: true
                },
                titulatura: {
                    required: function(element){
                        return "ROLE_" + $("#rol option:selected").text() === ROLE_MEDIC;
                    }
                },
                specialitate: {
                    required: function(element){
                        return "ROLE_" + $("#rol option:selected").text() === ROLE_MEDIC;
                    }
                },
                password: {
                    required: true,
                    minlength: 6,
                    strength: true
                }
            },
            messages: {
                "cod_parafa":{
                    required: "Camp obligatoriu pentru medici"
                },
                "titulatura":{
                    required: "Camp obligatoriu pentru medici"
                },
                "specialitate":{
                    required: "Camp obligatoriu pentru medici"
                }
            }
        });
    };

    let initDeleteUser = function () {
        $("body").on("click", ".sterge_utilizator", function () {
            let userId = this.closest('tr').id;
            $("#modal_stergere_id").val(userId);

            $("#stergere_text").html("Stergeti utilizatorul selectat?");

            $(".modal_stergere").modal("show");
        });

        $(".confirma_stergere").click(function () {
            resetRezultatOperatiune(true);

            $.ajax({
                url: "user/sterge",
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

    let initTable = function () {
        table = $('#utilizatori').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "user/list",
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
                    render: function () {
                        if ($("#rol_user").val() !== ROLE_ADMIN) {
                            return '<button disabled class="btn btn-circle btn-sm" title="Nicio actiune disponibila">' +
                                '<i class="fas fa-smile fa-2x"></i></button>';
                        }

                        return '<a href="#" class="btn btn-danger btn-circle btn-sm sterge_utilizator" title="Sterge" style="margin-right: 3px;">' +
                            '<i class="fas fa-trash"></i></a>' +
                            '<a href="#" class="btn btn-info btn-circle btn-sm editeaza_utilizator" title="Editeaza">' +
                            '<i class="fas fa-edit"></i></a>';
                    }
                },
                {
                    title: "Nume",
                    data: "nume_prenume",
                    name: "nume_prenume",
                    render: function (data, type, row) {
                        return row.nume + " " + row.prenume;
                    }
                },
                {
                    title: "Rol",
                    data: "rol",
                    name: "rol"
                },
                {
                    title: "Telefon",
                    data: "telefon",
                    name: "telefon"
                },
                {
                    title: "Email",
                    data: "email",
                    name: "email"
                },
                {
                    title: "Username",
                    data: "username",
                    name: "username"
                },
                {
                    title: "Cod parafa",
                    data: "codParafa",
                    name: "cod_parafa"
                },
                {
                    title: "Titulatura",
                    data: "tit",
                    name: "titulatura"
                },
                {
                    title: "Specialitate",
                    data: "spe",
                    name: "specialitate"
                }
            ]
        } );
    };

    let initEvents = function () {
        $(".add_edit_user_modal").on("hide.bs.modal", function () {
            $("#cod_parafa").prop("disabled", false);
            $("#specialitate").prop("disabled", false);
            $("#titulatura").prop("disabled", false);
        });
    };

    return {
        init: function () {
            initTable();
            initAddUser();
            initEditUser();
            initDeleteUser();
            initEvents();
        }
    }
}();

$(document).ready(function () {
    utilizatori.init();
});
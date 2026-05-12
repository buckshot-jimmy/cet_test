let facturi = function() {
    let tableFacturi;
    const ROL_ADMIN = "ROLE_Administrator";
    const FACTURA = 0;
    const STORNO = 1;

    let initTableFacturi = function () {
        tableFacturi = $('#facturi').DataTable({
            "order": [],
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "facturi/list",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                { "orderable": false, "targets": [0,1,2,3,4,6,7,8,9,-1] }
            ],
            "createdRow": function (row, data, dataIndex) {
                if (data.tip === STORNO || data.storno) {
                    $(row).css('background-color', '#f3eaeb');
                }
            },
            "columns": [
                {
                    title: "Actiuni",
                    render: function (data, type, row) {
                        let actiuni = '';

                        if ($("#rol_user").val() !== ROL_ADMIN) {
                            return '<button disabled class="btn btn-circle btn-sm" title="Nicio actiune disponibila">' +
                                '<i class="fas fa-smile fa-2x"></i></button>';
                        }

                        actiuni += "<a href='#' onclick='pdfFactura(" + row.id + "); return false;' "
                            + "class='btn btn-outline-danger btn-circle btn-sm pdf_factura' " +
                            'title="Factura PDF" style="margin-right: 3px;"><i class="fas fa-file-pdf"></i></a>';

                        actiuni += '<a href="#" class="btn btn-outline-dark btn-circle btn-sm email_trimite_factura" ' +
                            'title="Trimite pe email" style="margin-right: 3px;"><i class="fas fa-envelope"></i></a>';

                        if (row.tip === FACTURA && !row.storno) {
                            actiuni += '<a href="#" class="btn btn-outline-warning btn-circle btn-sm storneaza_factura" '
                                + 'title="Storneaza" style="margin-right: 3px;"><i class="fas fa-undo"></i></a>';
                        }

                        return actiuni;
                    }
                },
                {
                    title: "Serie",
                    data: "serie",
                    name: "serie"
                },
                {
                    title: "Numar",
                    data: "numar",
                    name: "numar"
                },
                {
                    title: "Data",
                    data: "data",
                    name: "data",
                },
                {
                    title: "Scadenta",
                    data: "scadenta",
                    name: "scadenta",
                },
                {
                    title: "Furnizor",
                    data: "furnizor",
                    name: "furnizor",
                },
                {
                    title: "Client",
                    render: function (data, type, row) {
                        return row.numePacient ? row.numePacient : row.clientPj
                    }
                },
                {
                    title: "Valoare RON",
                    data: "valoare",
                    name: "valoare",
                },
                {
                    title: "Tip",
                    render: function (data, type, row) {
                        return row.tip === FACTURA ? "factura" : "storno";
                    }
                },
                {
                    title: "Stornata cu / Storneaza",
                    render: function (data, type, row) {
                        return row.storno ?? row.originala;
                    }
                }
            ]
        });
    }

    let initEvents = function () {
        $("body").on("click", ".email_trimite_factura", function () {
            $("#email_factura_form").valid();
            $("#factura_id").val(this.closest('tr').id);
            $(".email_factura_modal").modal("show");
        });
        $(".confirma_trimite_factura_email").on("click", function () {
            if ($("#email_factura_form").valid()) {
                $(".spinner").show();
                trimiteEmailFactura($("#factura_id").val(), $("#email_trimite_factura").val());
            }
        });
        $("body").on("click", ".storneaza_factura", function () {
            $("#factura_stornata_id").val(this.closest('tr').id);
            $(".storneaza_factura_modal").modal("show");
        });
        $(".confirma_storneaza_factura").on("click", function () {
            $(".spinner").show();

            $.ajax({
                url: "/facturi/storneaza_factura",
                type: "POST",
                data: {
                    factura_id: $("#factura_stornata_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.storneaza_factura_modal');
                    tableFacturi.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    return {
        init: function () {
            initTableFacturi();
            initEvents();
        }
    }
}();

function trimiteEmailFactura(facturaId, email) {
    $.ajax({
        url: "/facturi/trimite_email_factura",
        type: "POST",
        data: {
            id: facturaId,
            email: email
        },
        success: function (response) {
            addShortModal(response);
        },
        error: function (response) {
            addShortModal(response, 'fail');
        },
        complete: function () {
            $(".email_factura_modal").modal("hide");
            $(".spinner").hide();
        }
    });
}

function pdfFactura(facturaId) {
    let input = "<input type='hidden' id='factura_id' name='factura_id' value='" + facturaId + "'>";
    $("<form action='/facturi/pdf_factura' method='post'>" + input + "</form>").appendTo('body')
        .submit().remove();
}

function salveazaFactura() {
    $.ajax({
        url: "/facturi/salveaza_factura",
        type: "POST",
        data: {
            form: $("#add_factura_form").serialize()
        },
        success: function (response) {
            addRezultatOperatiuneSuccess(response, '.add_factura_modal');
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function facturaFormValidation() {
    $("#add_factura_form").validate({
        ignore: [],
        rules: {
            factura_pacient: {
                required: function () {
                    const val = $('#factura_pj').val();
                    return !val || !Number.isInteger(Number(val));
                }
            },
            factura_pj: {
                required: function () {
                    const val = $('#factura_pacient').val();
                    return !val || !Number.isInteger(Number(val));
                }
            }
        },
        messages: {
            "factura_pacient": {
                required: "Adaugati cel putin un client persoana fizica sau juridica"
            },
            "factura_pj": {
                required: "Adaugati cel putin un client persoana fizica sau juridica"
            }
        }
    });
}

$(document).ready(function () {
    facturi.init();
});
let consultatiiNefacturate = function() {
    let tableConsultatiiNefacturate;

    let initTableConsultatiiNefacturate = function () {
        tableConsultatiiNefacturate = $('#consultatii_nefacturate').DataTable({
            "order": [],
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "/facturi/consultatii_nefacturate_list",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                { "orderable": false, "targets": [0,2,3] }
            ],
            "columns": [
                {
                    title: "Actiuni",
                    width: "100px",
                    render: function () {
                        return '<a href="#" class="btn btn-outline-warning btn-circle btn-sm consultatii_nefacturate" '
                            + 'title="Consultatii nefacturate" style="margin-right: 3px;"><i class="fas fa-fw fa-file-invoice"></i></a>';
                    }
                },
                {
                    title: "Pacient",
                    data: "pacient",
                    name: "pacient"
                },
                {
                    title: "CNP",
                    width: "200px",
                    data: "cnp",
                    name: "cnp"
                },
                {
                    title: "Total RON",
                    width: "150px",
                    data: "total",
                    name: "total",
                }
            ]
        });
    }

    let initEvents = function () {
        $(".consultatii_nefacturate_modal").on('hidden.bs.modal', function(){
            $("#table_consultatii_nefacturate").empty();
            $(".factureaza").prop("disabled", true);
            $("#cauta_serviciu").val("");
        });

        $("#cauta_serviciu").on("keyup", function() {
            let value = $(this).val().toLowerCase();

            $("#table_consultatii_nefacturate tr").each(function(index) {
                let row = $(this);
                let data = row.find("td:first").text().toLowerCase();
                let medic = row.find("td:nth-child(2)").text().toLowerCase();
                let serviciu = row.find("td:nth-child(3)").text().toLowerCase();
                let owner = row.find("td:nth-child(4)").text().toLowerCase();

                $(this).toggle(data.indexOf(value) !== -1
                    || medic.indexOf(value) !== -1
                    || serviciu.indexOf(value) !== -1
                    || owner.indexOf(value) !== -1
                );
            });
        });

        $("body").on("change", "input[name='facturare_checkbox[]']", function () {
            let selectedOwner = $(this).attr("owner");
            let checked = $(this).is(":checked");
            let total = 0;
            let facturaOwnerText = "";

            if (checked) {
                $(".factureaza").prop("disabled", false);
            } else {
                $(".factureaza").prop("disabled", true);
            }

            $("input[name='facturare_checkbox[]']").each(function () {
                if ($(this).attr("owner") === selectedOwner) {
                    $(this).prop("checked", false);

                    if (checked === true) {
                        $(this).prop("checked", true);

                        total += parseInt($(this).closest('tr').find('td.pret_serviciu').text());
                        facturaOwnerText = $(this).closest('tr').find('td.owner_serviciu').text();
                    }
                } else {
                    $(this).prop("checked", false);
                }
            });

            $("#owner_factura").val(selectedOwner);
            $("#furnizor_factura").text(facturaOwnerText);
            $("#suma_factura").text(total);
        });

        $(".factureaza").on("click", function () {
            let ids = [];
            let checkboxes = $("input[name='facturare_checkbox[]']:checked");

            checkboxes.each(function () {
                ids.push(this.closest("tr").id);
            });

            if (ids.length > 0) {
                $("#consultatii_factura").val(ids);
            }

            $(".add_factura_modal").modal("show");
        });

        $("body").on("click", ".consultatii_nefacturate", function () {
            let pacient = tableConsultatiiNefacturate.row($(this).closest('tr')).data().pacient;
            $("#cnp_factura").val(tableConsultatiiNefacturate.row($(this).closest('tr')).data().cnp);
            $(".consultatii_nefacturate_title").css('font-weight', 'bold')
                .text('Lista consultatii nefacturate - ' + pacient);

            $(".consultatii_nefacturate_modal").modal("show");

            getConsultatiiNefacturatePacient(this.closest('tr').id);
            $("#nefacturate_pacient_id").val(this.closest('tr').id);
        });

        $("body").on('hidden.bs.modal', '.add_factura_modal', function(){
            getConsultatiiNefacturatePacient($("#nefacturate_pacient_id").val());
        });

        let getConsultatiiNefacturatePacient = function (pacientId) {
            $("#table_consultatii_nefacturate").empty();
            tableConsultatiiNefacturate.ajax.reload();

            $.ajax({
                url: "/facturi/consultatii_nefacturate_pacient",
                type: "GET",
                data: {
                    pacient_id: pacientId
                },
                success: function (response) {
                    $.each(response.serviciiPacient, function (index, serviciu) {
                        let row = '<tr style="padding-bottom: 15px;" id="' + serviciu.id + '">' +
                            '<td style="width: 120px; padding-right: 15px; padding-bottom: 15px; font-weight: bold;">' +
                            serviciu.dataConsultatie + '</td><td style="width: 200px; font-weight: bold; padding-right: 15px; '
                            + 'padding-bottom: 15px;">' + serviciu.numeMedic +
                            '</td><td style="width: 150px; font-weight: bold; padding-right: 15px; padding-bottom: 15px;">'
                            + serviciu.denumireServiciu + '</td>' +
                            '<td class="owner_serviciu" style="font-weight: bold; padding-right: 15px; padding-bottom: 15px;"> '
                            + serviciu.denumireOwner + '<td style="font-weight: bold; padding-bottom: 15px;" class="pret_serviciu">'
                            + serviciu.tarif + ' lei</td>' +
                            '</td><td style="padding-left: 50px; vertical-align: top;"><input name="facturare_checkbox[]" ' +
                            'owner="' + serviciu.ownerId + '" type="checkbox" style="' + checkBoxStyle() + '"></td></tr>';

                        $("#table_consultatii_nefacturate").append(row);

                        if (response.serviciiPacient.length > 0) {
                            $(".consultatii_nefacturate_modal").modal("show");
                        }
                    });
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        }
    };

    return {
        init: function () {
            initTableConsultatiiNefacturate();
            initEvents();
        }
    }
}();

$(document).ready(function () {
    consultatiiNefacturate.init();
});
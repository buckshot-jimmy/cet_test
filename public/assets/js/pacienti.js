let pacienti = function () {
    let tablePacienti;
    let tablePacientiInCabinet;

    const DESCHISA = 0;
    const STARE_NECASATORIT = 0;
    const NEINCASATA = 0;
    const ROL_RECEPTIE = "ROLE_Receptie";
    const ROL_ADMIN = "ROLE_Administrator";

    let initAdaugaPacient = function () {
        $(".add_pacient").click(function () {
            $("#pacient_id").val("");

            $.ajax({
                url: "pacienti/get_pacient",
                type: "GET",
                success: function (response) {
                    $(".titlu_add_edit_pacient").text("Adauga pacient");
                    $(".modal_pacient_content").html(response);
                    $(".add_edit_pacient_modal").modal("show");

                    initSelect2();
                    pacientFormValidation();
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });
    };

    let initEditeazaPacient = function () {
        $("body").on("click", ".editeaza_pacient", function () {
            let id = this.closest('tr').id;

            $.ajax({
                url: "pacienti/get_pacient",
                type: "GET",
                data: {
                    id: id
                },
                success: function (response) {
                    $(".titlu_add_edit_pacient").text("Editeaza pacient");
                    $(".modal_pacient_content").html(response);
                    $(".add_edit_pacient_modal").modal("show");

                    initSelect2();

                    if ($("#tara").val() !== 'Romania') {
                        $("#judet").prop('disabled', true);
                    }

                    pacientFormValidation();
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });
    };

    let initStergePacient = function () {
        $("body").on("click", ".sterge_pacient", function () {
            let pacientId = this.closest('tr').id;
            $("#modal_stergere_id").val(pacientId);
            $('.confirma_stergere').addClass('delete_pacient');

            $("#stergere_text").html("Stergeti pacientul selectat?");

            $(".modal_stergere").modal("show");
        });

        $("body").on('click', '.delete_pacient', function () {
            resetRezultatOperatiune(true);

            $.ajax({
                url: "pacienti/sterge_pacient",
                type: "POST",
                data: {
                    id: $("#modal_stergere_id").val()
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.modal_stergere');

                    tablePacienti.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    let initAdaugaConsultatieInvestigatie = function () {
        $("body").on("click", ".add_consultatie_investigatie", function () {
            $("select[multiple]").css('overflow-x', 'auto');

            let pacient = this.getAttribute('pacient');
            $(".titlu_cons_inv_modal").css('font-weight', 'bold')
                .text('Pacient ' + pacient + ' - Adauga consultatie / investigatie');

            let id = this.closest('tr').id;
            $("#deschide_cons_inv_pacient_id").val(id);

            cautaPreturi();

            $(".add_consultatie_investigatie_modal").modal("show");
        });

        $(".confirma_adauga_consultatie_investigatie").on("click", function () {
            var preturiIds = $('[name="duallistbox_servicii[]"]').val();

            $('input:checkbox:checked').each(function (index) {
                preturiIds.push($(this).val());
            });

            deschideStergeConsultatiiPacient(
                $("#deschide_cons_inv_pacient_id").val(),
                $("#deschide_cons_inv_programare_id").val(),
                preturiIds,
                moment().format('DD-MM-YYYY')
            );
        });
    };

    let deschideStergeConsultatiiPacient = function (pacientId, programareId, preturiIds, dataPrezentare) {
        resetRezultatOperatiune(true);

        $.ajax({
            url: "pacienti/deschide_sterge_consultatii",
            type: "POST",
            data: {
                pacientId: pacientId,
                programareId: programareId,
                serviciiPreturiIds: preturiIds,
                dataPrezentare: dataPrezentare,
            },
            success: function (response) {
                addRezultatOperatiuneSuccess(response, ".add_consultatie_investigatie_modal");

                tablePacienti.ajax.reload();
                tablePacientiInCabinet.ajax.reload();
            },
            error: function (response) {
                if (response.responseJSON.status_code === 201) {
                    addRezultatOperatiuneInfo(response, '.add_consultatie_investigatie_modal');

                    tablePacienti.ajax.reload();
                    tablePacientiInCabinet.ajax.reload();
                }

                addRezultatOperatiuneFail(response);
            }
        });
    };

    let initSelect2 = function () {
        $("#tara").select2({
            width: "100%",
            containerClass: "form-control"
        });

        $("#judet").select2({
            width: "100%",
            containerClass: "form-control"
        });

        $("span.select2-selection--single").addClass("form-control");
        $("span.select2-selection__arrow").addClass("form-control");
    };

    let initInchideToate = function () {
        $("body").on("click", ".inchide_toate", function () {
            resetRezultatOperatiune(false);

            let pacientId = this.closest('tr').id;
            $("#inchide_pacient_id").val(pacientId);

            $(".modal_inchide_toate").modal("show");
        });

        $("body").on("click", ".confirma_inchide_toate", function () {
            inchideToate($("#inchide_pacient_id").val(), [tablePacienti, tablePacientiInCabinet]);
        });
    };

    let initEvents = function () {
        $("#factura_pj").on("select2:select", function () {
            resetRezultatOperatiune();
            $("#factura_pacient").empty();
            $("#add_factura_form").valid();
        });
        $("#factura_pacient").on("select2:select", function () {
            resetRezultatOperatiune();
            $("#factura_pj").empty();
            $("#add_factura_form").valid();
        });

        $("body").on("keyup", "#nume, #prenume", function () {
            $(this).val($(this).val().toUpperCase());
        });

        $("body").on("focusout", "#cnp", function () {
            if ($("#cnp").valid()) {
                $(".rezultat_operatiune").hide();
                verificaUnicitateCnp($("#cnp").val());
            }

            if ($("#cnp").valid() && $("#tara").val() === 'Romania') {
                var datePacient = calculeazaVarsta($("#cnp").val(), "");
                $("#varsta").val(datePacient[0]).valid();
            } else {
                $("#varsta").val("");
            }
        });

        $("body").on("click", ".confirma_pacient", function () {
            resetRezultatOperatiune(true);

            if (!$("#add_edit_pacient_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "pacienti/add_edit_pacient",
                type: "POST",
                data:
                    $("#add_edit_pacient_form").serialize().replace("pacient_", ""),
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, '.add_edit_pacient_modal');

                    $("#tara").val('Romania').trigger('change.select2');
                    $("#judet").val('').trigger('change.select2');
                    $("#stareCivila").val(STARE_NECASATORIT).trigger('change.select2');

                    tablePacienti.ajax.reload();
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });

        $(".add_consultatie_investigatie_modal ").on('hidden.bs.modal', function(){
            $("[name='duallistbox_servicii[]_helper1'] option").remove();
            $("[name='duallistbox_servicii[]_helper2'] option").remove();
            $("[name='duallistbox_servicii[]'] option").remove();
        });

        $("body").on("change", "#tara", function () {
            $("#judet").val("").prop('disabled', true).trigger('change.select2');

            if ($(this).val() === "Romania") {
                $("#judet").val("Alba").prop('disabled', false).trigger('change.select2');
            }

            $('#tara').valid();
        });

        $("body").on("change", "#judet", function () {
            $('#judet').valid();
        });

        $(".add_factura_modal").on('show.bs.modal', function(){
            $("#factura_pacient").empty();
            $("#factura_pj").empty();

            fetchPacienti($("#cnp_factura").val()).then(function(response) {
                let pacient = response.pacienti[0];

                const option = new Option(
                    pacient.numePacient,
                    pacient.id,
                    true,
                    true
                );

                $("#factura_pacient")
                    .append(option)
                    .trigger("change");

                if ($("#add_factura_form").valid()) {
                    resetRezultatOperatiune();
                }
            });

            $(this).find('.rezultat_operatiune').hide();
            if ($(".incasare_modal").hasClass("show")) {
                $(".incasare_modal").modal("hide");
            }
        });

        $(".add_factura_modal").on('hidden.bs.modal', function(){
            if ($("input[name='plata_checkbox[]']").length > 0) {
                $(".confirma_incasare").prop("disabled", true);
                $(".incasare_modal").modal("show");
            }
        });

        $("body").on('click', '.confirma_factura', function () {
            resetRezultatOperatiune(true);

            if (!$("#add_factura_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            salveazaFactura();
        });
    };

    let pacientFormValidation = function () {
        $("#add_edit_pacient_form").validate({
            rules: {
                nume: {
                    required: true,
                    letters: true
                },
                prenume: {
                    required: true,
                    letters: true
                },
                cnp: {
                    required: true,
                    cnp: true
                },
                telefon: {
                    required: true
                },
                adresa: {
                    required: true
                },
                judet: {
                    required: function (element) {
                        return ($("#tara").val() === 'Romania' && $(element).val() === "");
                    }
                },
                varsta: {
                    required: true,
                    digits: true
                },
                tara: {
                    required: true
                },
                localitate: {
                    required: true
                }
            },
            messages: {
                "judet": {
                    required: "Obligatoriu pentru Romania"
                }
            }
        });
    };

    let initTablePacienti = function () {
        tablePacienti = $('#pacienti').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "pacienti/list",
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
                    render: function (data, type, row) {
                        let actiuni = '';

                        if ($("#rol_user").val() === ROL_ADMIN) {
                            actiuni += '<a href="#" class="btn btn-danger btn-circle btn-sm sterge_pacient"' +
                                ' title="Sterge" style="margin-right: 3px;"><i class="fas fa-trash"></i></a>';
                        }


                        actiuni += '<a href="#" class="btn btn-info btn-circle btn-sm editeaza_pacient" ' +
                            'title="Editeaza" style="margin-right: 3px;"><i class="fas fa-edit"></i></a>';

                        if ($("#rol_user").val() === ROL_ADMIN || $("#rol_user").val() === ROL_RECEPTIE) {
                            if (row.areConsultatiiDeschise) {
                                actiuni += '<a href="#" class="btn btn-light btn-circle btn-sm inchide_toate" ' +
                                    'title="Inchideti toate consultatiile" style="margin-right: 3px;">' +
                                    '<i class="fas fa-lock"></i></a>';
                            }
                        }

                        actiuni += '<a href="#" class="btn btn-success btn-sm add_consultatie_investigatie"'+
                            ' title="Adauga serviciu" style="margin-right: 3px;" pacient="' + row.nume + " "
                            + row.prenume + '"><i class="fas fa-cart-plus"></i></a>';

                        return actiuni;
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
                    title: "C.I.",
                    data: "ci",
                    name: "ci"
                },
                {
                    title: "Eliberata de",
                    data: "ciEliberat",
                    name: "ciEliberat",
                    width: 60
                },
                {
                    title: "Data nasterii",
                    data: "dataNasterii",
                    name: "dataNasterii",
                    width: 60
                },
                {
                    title: "Sex",
                    data: "sex",
                    name: "sex"
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

    let initTablePacientiInCabinet = function () {
        tablePacientiInCabinet = $('#pacienti_in_cabinet').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "pacienti/list_pacienti_in_cabinet",
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
                    render: function (data, type, row) {
                        let buttons = '';
                        let culoareConsultatie = "btn-success";
                        let culoareIncasare = "btn-success";

                        let dataCons = moment(row.dataPrezentariiJs);
                        let dataAzi = moment();

                        if (row.areConsultatiiDeschise === true) {
                            if (dataAzi.diff(dataCons, 'days') >= 1) {
                                culoareConsultatie = "btn-danger";
                            }

                            buttons += '<a title="Click pentru consultatii in desfasurare" data-toggle="tooltip" ' +
                                'data-placement="right" class="btn tooltipHover ' + culoareConsultatie +
                                ' btn-circle btn-sm" style="margin-right: 3px; color: white;">' +
                                '<i class="fas fa-user-md"></i></a>';
                        }

                        if (row.areConsultatiiNeplatite === true && row.areConsultatiiDeschise === false) {
                            if (dataAzi.diff(dataCons, 'days') >= 1) {
                                culoareIncasare = "btn-danger";
                            }

                            buttons += '<a class="btn ' + culoareIncasare + ' btn-circle btn-sm incaseaza" ' +
                                'title="Incaseaza" style="margin-right: 3px; color: white;" pacient="' +
                                row.nume + " " + row.prenume + '"><i class="fas fa-money-bill"></i></a>';
                        }

                        return buttons;
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
                    title: "Email",
                    data: "email",
                    name: "email"
                },
                {
                    title: "Data prezentarii",
                    data: "dataPrezentarii",
                    name: "dataPrezentarii",
                    width: 60
                }
            ]
        });
    };

    let clearModalTables = function () {
        $("#table_servicii").empty();
        $("#table_medic_owner").empty();
        $("#incasare_owner_text").text("");
    };

    let initIncasare = function () {
        $("body").on("click", ".incaseaza", function () {
            let pacient = this.getAttribute('pacient');
            $(".titlu_incasare_modal").css('font-weight', 'bold').text('Pacient ' + pacient + ' - Plata');

            let id = this.closest('tr').id;
            $("#cnp_factura").val(tablePacientiInCabinet.row($(this).closest('tr')).data().cnp);
            let dataPrezentare = tablePacientiInCabinet.row($(this).closest('tr')).data().dataPrezentarii;
            $("#incasare_pacient_id").val(id);
            $("#data_consultatii").val(dataPrezentare);

            getServiciiPacientInCabinet(id, dataPrezentare);

            $(".incasare_modal").modal("show");
        });

        $(".incasare_modal").on('hide.bs.modal', function(){
            $(".confirma_incasare").prop("disabled", true);
            clearModalTables();
        });

        $("body").on("change", "input[name='plata_checkbox[]']", function () {
            var selectedOwner = $(this).attr("owner");
            var checked = $(this).is(":checked");

            var total = 0;
            var incasareOwnerText = "";

            if (checked) {
                $(".confirma_incasare").prop("disabled", false);
            } else {
                $(".confirma_incasare").prop("disabled", true);
            }

            $("input[name='plata_checkbox[]']").each(function () {
                if ($(this).attr("owner") === selectedOwner) {
                    $(this).prop("checked", false);

                    if (checked === true) {
                        $(this).prop("checked", true);

                        total += parseInt($(this).closest('tr').find('td.pret_serviciu').text());
                        incasareOwnerText = $(this).closest('tr').find('td.owner_serviciu').text();
                    }
                } else {
                    $(this).prop("checked", false);
                }
            });

            $("#incasare_owner_text").text(total === 0 ? "" : incasareOwnerText + " - " + total + " lei");
            $("#furnizor_factura").text(incasareOwnerText);
            $("#owner_factura").val(selectedOwner);
            $("#suma_factura").text(total);
        });

        $(".confirma_incasare").on("click", function () {
            let ids = [];
            let checkboxes = $("input[name='plata_checkbox[]']:checked");

            checkboxes.each(function () {
                ids.push($(this).attr("consultatie"));
            });

            if (ids.length > 0) {
                $("#consultatii_factura").val(ids);

                resetRezultatOperatiune(true);

                $.ajax({
                    url: "pacienti/incaseaza_consultatii",
                    type: "POST",
                    data: {
                        consultatii: ids
                    },
                    success: function (response) {
                        addRezultatOperatiuneSuccess(response, '.rezultat_operatiune_success');

                        if ($("input[name='plata_checkbox[]']").length ===
                            $("input[name='plata_checkbox[]']:checked").length){
                            hideModalTimeout(".incasare_modal");
                        } else {
                            getServiciiPacientInCabinet($("#incasare_pacient_id").val(), $("#data_consultatii").val());
                        }

                        tablePacientiInCabinet.ajax.reload();
                    },
                    error: function (response) {
                        addRezultatOperatiuneFail(response);
                    },
                    complete: function () {
                        $(".add_factura_modal").modal("show");
                    }
                });
            }
        });
    };

    let getServiciiPacientInCabinet = function (id, dataPrezentare = null) {
        clearModalTables();

        $.ajax({
            url: "pacienti/get_servicii_pacient",
            type: "GET",
            data: {
                pacient_id: id,
                dataPrezentare: dataPrezentare,
                incasata: NEINCASATA
            },
            success: function (response) {
                let total = 0;
                let index = 1;
                let tableServicii = $("#table_servicii");

                let header = '<tr><td style="color: #78261f; font-weight: bold;">Medic</td>' +
                    '<td style="color: #78261f; font-weight: bold;">Serviciu</td>' +
                    '<td style="color: #78261f; font-weight: bold;">Tarif - lei</td>' +
                    '<td style="color: #78261f; font-weight: bold;">Firma</td>' +
                    '<td></td></tr>';

                tableServicii.append(header);

                $.each(response.serviciiPacient, function (id, serviciu) {
                    let border = "style='border-left: black solid 1px; border-right: black solid 1px; height: 40px; '";

                    if (index === 1) {
                        border = "style='border-top: black solid 1px; border-left: black solid 1px; " +
                            "border-right: black solid 1px; height: 40px;'" ;
                    }

                    if (index === response.serviciiPacient.length) {
                        border = "style='border-bottom: black solid 1px; border-left: black solid 1px; border-right: " +
                            "black solid 1px; height: 40px;'" ;
                    }

                    if (response.serviciiPacient.length === 1) {
                        border = "style='border: black solid 1px; height: 40px;'" ;
                    }

                    let trServicii = "<tr " + border + "><td>" + serviciu.numeMedic + "</td>" +
                        "<td>" + serviciu.denumireServiciu + "</td>" +
                        "<td class='pret_serviciu'>" + serviciu.pretServiciu + "</td>" +
                        "<td class='owner_serviciu'>" + serviciu.denumireOwner + "</td>" +
                        "<td><input type='checkbox' style='" + checkBoxStyle() + "' owner='" + serviciu.ownerId + "' consultatie='" +
                        serviciu.consultatieId + "' name='plata_checkbox[]' /></td></tr>";

                    total += serviciu.pretServiciu;

                    tableServicii.append(trServicii);

                    index++;
                });

                let totalRow = "<tr><td style='font-weight: bold; color: #78261f;'>Total general</td><td></td>" +
                    "<td style='font-weight: bold; color: #78261f;'>" + total + "</td></tr>";

                tableServicii.append(totalRow);
            },
            error: function (response) {
                addRezultatOperatiuneFail(response);
            }
        });
    };

    let initTooltip = function () {
        $('body').tooltip({
            selector: '[data-toggle="tooltip"]',
            html: true
        });

        $('body').on('hidden.bs.tooltip', '.tooltipHover', function () {
            $('[data-toggle="tooltip"]').each(function () {
                $(this).attr('data-original-title', 'Click pentru consultatii in desfasurare');
            });
        });

        $('body').on('mouseover', '.tooltipHover', function () {
            let pacientIcon = this;

            if ($(pacientIcon).hasClass('btn-danger')) {
                $('.tooltip-inner').css('background-color','#d52a1a')
            } else {
                $('.tooltip-inner').css('background-color','#169b6b')
            }
        });

        $('body').on('click', '.tooltipHover', function () {
            let pacientId = this.closest('tr').id;
            let pacientIcon = this;
            $(pacientIcon).tooltip('hide');
            let dataPrezentare = tablePacientiInCabinet.row($(this).closest('tr')).data().dataPrezentarii;

            $.ajax({
                url: "pacienti/get_servicii_pacient",
                type: "GET",
                data: {
                    pacient_id: pacientId,
                    inchisa: DESCHISA,
                    dataPrezentare: dataPrezentare,
                },
                success: function (response) {
                    let serviciiPacient = response.serviciiPacient;
                    let tooltipText = "";

                    serviciiPacient.forEach(function (serviciu, index) {
                        tooltipText += serviciu.denumireServiciu + ' - ' + serviciu.numeMedic;

                        if (index < (serviciiPacient.length - 1)) {
                            tooltipText += '<br />';
                        }
                    });

                    let allTooltipsPacient = $("tr[id=" + pacientId + "]").find('[data-toggle="tooltip"]');
                    $.each(allTooltipsPacient, function (index, tooltip) {
                        $(tooltip).attr('data-original-title', tooltipText);
                    });

                    $(pacientIcon).tooltip('show');
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });
    };

    return {
        init: function () {
            initTablePacienti();
            initTablePacientiInCabinet();
            initEvents();
            initAdaugaPacient();
            initEditeazaPacient();
            initStergePacient();
            initAdaugaConsultatieInvestigatie();
            initIncasare();
            initTooltip();
            initInchideToate();
            initSearchPacient("#factura_pacient");
            initSearchClientPj("#factura_pj");
            facturaFormValidation();
        }
    }
}();

function verificaUnicitateCnp(cnp) {
    $.ajax({
        url: "pacienti/verifica_unicitate_cnp",
        type: "POST",
        data: {
            cnp: cnp
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function inchideToate(pacientId, tablesToRefresh) {
    resetRezultatOperatiune(true);

    $.ajax({
        url: "pacienti/inchide_toate",
        type: "POST",
        data: {
            id: pacientId
        },
        success: function (response) {
            addRezultatOperatiuneSuccess(response, '.modal_inchide_toate');

            for (let i = 0; i < tablesToRefresh.length; i++) {
                tablesToRefresh[i].ajax.reload();
            }
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

function cautaPreturi(){
    let preturiDualListbox = $('select[name="duallistbox_servicii[]"]').bootstrapDualListbox();

    const dataPrezentare = moment().format('DD-MM-YYYY');

    $.ajax({
        url: "pacienti/get_preturi",
        type: "GET",
        data: {
            pacientId: $("#deschide_cons_inv_pacient_id").val(),
            dataPrezentare: dataPrezentare
        },
        success: function (response) {
            $(".preturi_ul").empty();

            var preturi = response.preturi.servicii_preturi;
            var serviciiPacientInCabinet = response.serviciiPacientInCabinet;

            preturi.forEach(function (pret, index) {
                var selected = "";
                if (serviciiPacientInCabinet.includes(pret.id)) {
                    selected = 'selected';
                }

                var pretValuesText = pret.numeMedic + " " + pret.prenumeMedic + " | " + pret.denumireServiciu
                    + " | " + pret.denumireOwner + " | " + pret.pret + ' RON';

                var pretHtml = '<option ' + selected + ' value="' + pret.id + '">' + pretValuesText +
                    '</option>';

                $("select[name='duallistbox_servicii[]']").append(pretHtml);
            });

            preturiDualListbox.trigger('bootstrapDualListbox.refresh' , true);
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

$(document).ready(function () {
    pacienti.init();
});
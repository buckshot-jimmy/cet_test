let consultatiiCurenteCabinet = function () {
    let tableCabinet;

    const TIP_CONSULTATIE = 0;
    const TIP_INVESTIGATIE = 1;
    const TIP_PSIHODIAGNOSTIC = 2;
    const STARE_DESCHISA = 0;
    const STARE_INCHISA = 1;
    const NEINCASATA = 0;
    const INCASATA = 1;

    let initTableCabinet = function () {
        tableCabinet = $('#consultatii_curente_cabinet').DataTable({
            "language": {
                "url": "/assets/datatable_translation.json"
            },
            "processing": true,
            "serverSide": true,
            "rowId": "id",
            "ajax": {
                type: "GET",
                url: "consultatii/list_consultatii_curente_cabinet",
                dataSrc: "data"
            },
            "paging": true,
            "columnDefs": [
                {
                    "orderable": false, "targets": [0]
                }
            ],
            "order": [[ 1, "desc" ]],
            "columns": [
                {
                    title: "Actiuni",
                    width: 50,
                    render: function (data, type, row) {
                        let titluDocument = "Tipareste Scrisoare medicala";
                        let stare = (row.inchisa === false ? STARE_DESCHISA : STARE_INCHISA);
                        let incasata = (row.incasata === false ? NEINCASATA : INCASATA);

                        if (row.tipServiciu === TIP_INVESTIGATIE) {
                            titluDocument = "Tipareste buletin de investigatie";
                        }
                        if (row.tipServiciu === TIP_PSIHODIAGNOSTIC) {
                            titluDocument = "Tipareste fisa de psihodiagnostic";
                        }

                        let denumireServiciu =
                            (row.tipServiciu === TIP_CONSULTATIE || row.tipServiciu === TIP_PSIHODIAGNOSTIC)
                                ? "consultatia"
                                : "investigatia";

                        let actiuni = '';

                        if (stare === STARE_DESCHISA) {
                            actiuni += '<a href="#" class="btn btn-info btn-circle btn-sm edit_from_cabinet"' +
                                ' title="Editeaza ' + denumireServiciu + '" style="margin-right: 3px;">' +
                                '<i class="fas fa-edit"></i></a>';

                            actiuni += '<a href="#" class="btn btn-light btn-circle btn-sm inchide_cons_inv" ' +
                                'title="Inchideti ' + denumireServiciu + '" style="margin-right: 3px;" stare="'+ stare
                                + '" tip="' + row.tipServiciu + '"><i class="fas fa-lock"></i></a>';
                        }

                        if (stare === STARE_INCHISA && incasata === NEINCASATA) {
                            actiuni += '<a href="#" class="btn btn-light btn-circle btn-sm deschide_cons_inv" ' +
                                'title="Deschide ' + denumireServiciu + '" style="margin-right: 3px;" stare="'+ stare
                                + '" tip="' + row.tipServiciu + '"><i class="fas fa-lock-open"></i></a>';
                        }

                        if (row.tipServiciu === TIP_CONSULTATIE) {
                            actiuni += '<a href="javascript:tiparesteScrisoareMedicala(' + row.id + ')" ' +
                                'class="btn btn-outline-danger btn-circle btn-sm tipareste_documente" title="' +
                                titluDocument + '" style="margin-right: 3px;"><i class="fas fa-file-pdf"></i></a>';
                        } else if (row.tipServiciu === TIP_INVESTIGATIE) {
                            actiuni += '<a href="javascript:tiparesteBuletinInvestigatie(' + row.id + ')" ' +
                                'class="btn btn-outline-danger btn-circle btn-sm tipareste_documente" title="' +
                                titluDocument + '" style="margin-right: 3px;"><i class="fas fa-file-pdf"></i></a>';
                        }
                        if (row.tipServiciu === TIP_CONSULTATIE) {
                            actiuni += '<a href="javascript:tiparesteFisaConsultatii(' + row.id + ')" ' +
                                'class="btn btn-outline-warning btn-circle btn-sm pdf_fisa_consultatii" ' +
                            'title="Fisa consultatii" style="margin-right: 3px;"><i class="fas fa-file-pdf"></i></a>';
                        }

                        if (row.tipServiciu === TIP_PSIHODIAGNOSTIC) {
                            actiuni += '<a href="javascript:tiparesteFisaPsihodiagnostic(' + row.id + ')" ' +
                                'class="btn btn-outline-success btn-circle btn-sm fisa_psiho" title="' +
                                titluDocument + '" style="margin-right: 3px;"><i class="fas fa-file-pdf"></i></a>';
                        }

                        if (row.tipServiciu === TIP_CONSULTATIE) {
                            actiuni += '<a href="javascript:tiparesteReferatMedical(' + row.id + ')" ' +
                                'class="btn btn-outline-success btn-circle btn-sm referat_medical" ' +
                                'title="Referat medical" ' + 'style="margin-right: 3px;">' +
                                '<i class="fas fa-file-medical-alt"></i></a>';
                        }

                        return actiuni;
                    }
                },
                {
                    title: "Nr. inreg.",
                    data: "nrInreg",
                    name: "nrInreg",
                    width: 25
                },
                {
                    title: "Data consultatie",
                    data: "dataConsultatie",
                    name: "dataConsultatie",
                    width: 55
                },
                {
                    title: "Pacient",
                    data: "pacient",
                    name: "pacient",
                    render: function (data, type, row) {
                        return row.numePacient + " " + row.prenumePacient;
                    }
                },
                {
                    title: "CNP / CI",
                    data: "cnp",
                    name: "cnp"
                },
                {
                    title: "Medic",
                    data: "medic",
                    name: "medic",
                    render: function (data, type, row) {
                        return row.numeMedic + " " + row.prenumeMedic;
                    }
                },
                {
                    title: "Serviciu",
                    data: "denumireServiciu",
                    name: "denumireServiciu"
                },
                {
                    title: "Owner",
                    data: "denumireOwner",
                    name: "denumireOwner"
                }
            ]
        } );
    };

    let initInchideDeschide = function () {
        $("body").on("click", ".inchide_cons_inv, .deschide_cons_inv", function () {
            let consultatieId = this.closest('tr').id;
            $("#modal_cons_inv_id").val(consultatieId);

            let stare = $(this).attr('stare');
            let textStare = parseInt(stare) === STARE_INCHISA ? 'Deschideti' : 'Inchideti';
            let tip = $(this).attr('tip');
            let tipText = ((parseInt(tip) === TIP_CONSULTATIE || parseInt(tip) === TIP_PSIHODIAGNOSTIC)
                ? " consultatia selectata?"
                : " investigatia selectata?");

            let titluText = textStare + tipText;

            $("#inchide_deschide_text").html(titluText);

            $(".modal_inchide_deschide").modal("show");
        });

        $("body").on("click", ".confirma_inchide_deschide", function () {
            inchideDeschide($("#modal_cons_inv_id").val(), tableCabinet);
        });
    };

    let initEditare = function () {
        $("body").on("click", ".edit_from_cabinet", function () {
            $(".confirma_consultatie").addClass("confirma_cons_cabinet");
            $(".confirma_investigatie").addClass("confirma_inv_cabinet");
            $(".confirma_eval_psiho").addClass("confirma_eval_psiho_cabinet");

            validateForms();

            let id = this.closest('tr').id;
            getConsultatieInvestigatieEval(id);
        });

        $("body").on("click", ".confirma_cons_cabinet", function () {
            confirmaConsultatie(tableCabinet);
        });

        $("body").on("click", ".confirma_inv_cabinet", function () {
            confirmaInvestigatie(tableCabinet);
        });

        $("body").on("click", ".confirma_eval_psiho_cabinet", function () {
            confirmaEvaluarePsihologica(tableCabinet);
        });
    };

    let initEvents = function () {
        $(".view_consultatie_modal, .view_investigatie_modal, .view_eval_psiho_modal, .short_modal")
            .on('hide.bs.modal', function(){
                removeBlur();
        });
    }

    let initPushNotification = function () {
        // const eventSource =
        // new EventSource('http://192.168.10.12:3000/.well-known/mercure?topic=deschide_sterge_consultatii');
        const eventSource =
            new EventSource('http://localhost:3000/.well-known/mercure?topic=deschide_sterge_consultatii');

        eventSource.onmessage = event => {
            tableCabinet.ajax.reload();
        }
    };

    return {
        init: function () {
            initTableCabinet();
            initInchideDeschide();
            initEditare();
            initEvents();
            initPushNotification();
        }
    }
}();

function inchideDeschide(consInvId, tableToRefresh) {
    resetRezultatOperatiune(true);

    $.ajax({
        url: "consultatii/inchide_deschide",
        type: "POST",
        data: {
            id: consInvId
        },
        success: function (response) {
            addRezultatOperatiuneSuccess(response, '.modal_inchide_deschide');

            tableToRefresh.ajax.reload();
        },
        error: function (response) {
            addRezultatOperatiuneFail(response);
        }
    });
}

$(document).ready(function () {
    consultatiiCurenteCabinet.init();
});
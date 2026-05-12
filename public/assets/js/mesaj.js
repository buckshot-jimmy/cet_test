let mesaj = function () {
    const ACTIV = 1;
    const INACTIV = 0;

    let initMesajAdmin = function () {
        $(".mesaj_admin").click(function () {
            $( "#add_mesaj_form" ).validate({
                rules: {
                    mesaj: {
                        required: true,
                    }
                }
            });

            $.ajax({
                url: "security/get_informare",
                type: "GET",
                success: function (response) {
                    $("#mesaj").val(response.mesaj);

                    let stare = (response.activ ? "on" : "off");
                    $("#active").bootstrapToggle(stare);

                    $(".modal_mesaj_admin").modal("show");
                },
                error: function (response) {
                    addShortModal(response, 'fail');
                }
            });
        });

        $(".confirma_mesaj").click(function () {
            resetRezultatOperatiune(true);

            if (!$("#add_mesaj_form").valid()) {
                addRezultatOperatiuneFail({'responseJSON': {'message': 'Eroare validare formular!'}});
                return false;
            }

            $.ajax({
                url: "security/salveaza_informare",
                type: "POST",
                data: {
                    mesaj: $("#mesaj").val(),
                    activ: $('#active').prop("checked") === false ? INACTIV : ACTIV
                },
                success: function (response) {
                    addRezultatOperatiuneSuccess(response, ".modal_mesaj_admin");
                },
                error: function (response) {
                    addRezultatOperatiuneFail(response);
                }
            });
        });
    };

    return {
        init: function () {
            initMesajAdmin();
        }
    }
} ();

$(document).ready(function () {
    mesaj.init();
});
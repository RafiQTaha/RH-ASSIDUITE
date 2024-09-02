$(document).ready(function  () {
    // stage = null;:
    $(".block_page").html('Gestions des Pointages')
    let stageId = null;
    $('select').select2()
    
    var table = $("#list_pointage").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[8, "desc"]],
        ajax: {
            url: Routing.generate('app_pointage_list'),
            type: "get",
            // beforeSend: function(jqXHR) {
            //     // If there's a previous jqXHR object, abort it
            //     if (previousXhr) {
            //         previousXhr.abort();
            //     }
            //     // Store the current jqXHR object for the new request
            //     previousXhr = jqXHR;
            // }
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        responsive: true,
        scrollX: true,
        columns: [
            {name: 'check.id', data: 'id'},
            {name: 'id_employe', data: 'id_employe'},
            {name: 'nom', data: 'nom'},
            {name: 'prenom', data: 'prenom'},
            {name: 'site', data: 'site'},
            {name: 'codeContract', data: 'codeContract'},
            {name: 'fonction', data: 'fonction'},
            {name: 'sn', data: 'sn'},
            {name: 'checktime', data: 'checktime'},
            // {name: 'u.roles', data: 'roles'},
            // {orderable: false , searchable: false,data:"enable"},
            // {orderable: false , searchable: false, data:"actions"} 
        ],
        drawCallback: function () {
          $("body tr#" + stageId).addClass("active_databales");
        },
        preDrawCallback: function (settings) {
          if ($.fn.DataTable.isDataTable("#list_pointage")) {
            var dt = $("#list_pointage").DataTable();
    
            //Abort previous ajax request if it is still in process.
            var settings = dt.settings();
            if (settings[0].jqXHR) {
              settings[0].jqXHR.abort();
            }
          }
        },
        createdRow: function (row, data, dataIndex) {
            $(row).attr('id', data.id);
        },
        language: datatablesFrench,
    });
    
    // let months = [
    //     'Janvier',
    //     'Février',
    //     'Mars',
    //     'Avril',
    //     'Mai',
    //     'Juin',
    //     'Juillet',
    //     'Août',
    //     'Septembre',
    //     'Octobre',
    //     'Novembre',
    //     'Décembre',
    // ];

    // const weekdaysShortFrench = [
    //     'Dim', // dimanche (Sunday)
    //     'Lun', // lundi (Monday)
    //     'Mar', // mardi (Tuesday)
    //     'Mer', // mercredi (Wednesday)
    //     'Jeu', // jeudi (Thursday)
    //     'Ven', // vendredi (Friday)
    //     'Sam'  // samedi (Saturday)
    // ];
    // let datepickerElement = document.querySelector('#date-range');
    // let datepicker = new Datepicker(datepickerElement, {
    //     // inline: true,
    //     ranged: true,
    //     min: new Date(),
    //     i18n: 
    //     { 
    //         months:months,
    //         weekdays: weekdaysShortFrench
    //     },

    //     // onChange: function(date) {
    //     //     if (date instanceof Array) {
    //     //         startDate = date[0];
    //     //         endDate = date[date.length - 1];
    //     //         let extendedEndDate = endDate;
    //     //         // Check if the end date is a Friday
    //     //         if (isFriday(endDate)) {
    //     //             extendedEndDate = new Date(endDate.getTime());
    //     //             // Extend the end date by two more days (Saturday and Sunday)
    //     //             extendedEndDate.setDate(extendedEndDate.getDate() + 2); // Add two days
    
    //     //             // Update the date array to include these new dates
    //     //             date.push(new Date(endDate.getTime() + 24 * 60 * 60 * 1000)); // Add Saturday
    //     //             date.push(new Date(endDate.getTime() + 2 * 24 * 60 * 60 * 1000)); // Add Sunday
    //     //             endDate = extendedEndDate;
    //     //         }
                
    //     //         dateReprise = new Date(endDate.getTime()); // Clone endDate
    //     //         dateReprise.setDate(dateReprise.getDate() + 1); // Add one day
    //     //         console.log('Selected Range:', startDate, endDate);
    //     //         // datepicker.setDate([startDate, endDate])
    //     //         // nombreJour = countDaysExcludingSundays(startDate, endDate);
    //     //         nombreJour = countDays(startDate, endDate);
    
    //     //         if (isSunday(dateReprise)) {
    //     //             dateReprise.setDate(dateReprise.getDate() + 1); // Add one day
    //     //         }
    //     //         $("#nbrjour").val(nombreJour);
    //     //         $("#date-reprise").val(moment(dateReprise).format('DD/MM/YYYY'));
    //     //     }
    //     // }
    // });


    const SynchroParDate = async ()=>{
        const icon = $(".syn_compte");
        icon.removeClass("fa-sync").addClass("fa-spinner fa-spin");
        let formData = new FormData();
        formData.append('date',$("#date").val());
        notyf.open({
            type:"info",
            message: "En cours...",
            duration: 9000000,
            dismissible: false
        });
        try {
            const request = await axios.post(Routing.generate('synchro_pointage'), formData);
            const response = request.data;
            notyf.dismissAll();
            notyf.open({
                type:"success",
                message: response,
                duration: 5000,
                dismissible: false
            });
            // Toast.fire({
            //   icon: 'success',
            //   title: response,
            // })
            table.ajax.reload()
            icon.addClass("fa-sync").removeClass("fa-spinner fa-spin");
        } catch (error) {
            const message = error.response.data;
            notyf.dismissAll();
            notyf.open({
                type:"error",
                message: message,
                duration: 5000,
                dismissible: false
            });
        }
        icon.addClass("fa-sync").removeClass("fa-spinner fa-spin");
    }

    $("#pointage_exporter").on("click", async (e) => {
        e.preventDefault();
        $("#pointage_exporter_modal").modal("show")
    })

    $("#pointage_exporter_form").on("submit", async function(e) {
        e.preventDefault();
        let dateDebut = $('#pointage_exporter_form #date_debut').val();
        let dateFin = $('#pointage_exporter_form #date_fin').val();
        if (dateDebut == "" || dateFin == ""  ) {
            notyf.dismissAll();
            notyf.open({
                type:"error",
                message: "merci d'inseret une periode",
                duration: 5000,
                dismissible: false
            });
        }
        let goto = Routing.generate('extraction_pointage',{dateDebut:dateDebut,dateFin:dateFin});
        // console.log(goto);
        window.open(goto ,"_blank");
    })

    $("#pointage_synchronisation").on("click", async (e) => {
        e.preventDefault();
        if(!$("#date").val() || $("#date").val() == ""){
            // Toast.fire({
            //   icon: 'error',
            //   title: 'Veuillez choissir une date!',
            // })
            notyf.open({
                type:"error",
                message: 'Veuillez choissir une date!',
                duration: 5000,
                dismissible: false
            });
            return;
        }
        SynchroParDate()
        // $("#ajouter_modal").modal("show")
    })

    
    $("body #date").on('change', async function (){
        const date = $(this).val();
        table.columns().search("");
        
        if(date != "") {
            table.columns(0).search(date).draw();
        }else{
            table.columns().search("").draw();
        }
    })

    
    // $('body').on('click','#imprimer_pointage', function (){
    //     if(!id_preinscription){
    //         Toast.fire({
    //         icon: 'error',
    //         title: 'Merci de Choisir Un Etudiant!',
    //         })
    //         return;
    //     }
    //     window.open('/preinscription/gestion/print_documents_preinscription/'+id_preinscription, '_blank');
    // })
    
})

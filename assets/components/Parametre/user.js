$(document).ready(function  () {
    user = null;
    let usersArray = [];
    $(".block_page").html('Parametrage users')
    
    var previousXhr = null;
    let userId = null;
    let dossierId = null;
    $("body").on('click', '#modifier', function(e){
        e.preventDefault();
        userId = $(this).attr('data-id');
        $("#modifier_modal").modal("show")
    })
    var table = $("#list_users").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: {
            url: Routing.generate('app_parametre_user_list'),
            type: "get",
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        responsive: true,
        scrollX: true,
        columns: [
            {name: 'u.id', data: 'id', orderable: false},
            {name: 'u.username', data: 'username'},
            {name: 'u.nom', data: 'nom'},
            {name: 'u.prenom', data: 'prenom'},
            {name: 'u.email', data: 'email'},
            {name: 'u.roles', data: 'roles'},
            {orderable: false , searchable: false,data:"enable"},
            // {orderable: false , searchable: false, data:"actions"} 
        ],
        columnDefs: [ 
            {
                targets: 0,
                render: function(data, type, full, meta) {
                    return `<input type="checkbox" value="${data}" id="${data}" class="user_check"/>`;
                   
                }
            },
 
            {
                targets: 6,
                render: function(data, type, full, meta) {
                    if(data == true) {
                        return `<i class="fa fa-unlock text-success enable"/>`;
                    } else {
                        return `<i class="fa fa-lock text-danger disable"/>`;
                    }
                   
                }
            },
 
        ],
        createdRow: function (row, data, dataIndex) {
            $(row).attr('id', data.id);
        },
        language: datatablesFrench,
    });

    $("body").on("click", ".enable",async function(e){
        e.preventDefault();
        id_user = $(this).closest('tr').attr('id');
        const icon = $(this);
        if(!id_user) {
            notyf.dismissAll();
            notyf.error('Veuillez selectionné un user');
            return;
        }
        swalWithBootstrapButtons.fire({
            showClass: {
                popup: 'animatedSwal flipInX faster'
            },
            position: 'top',
            title: "Confirmation ?",
            text: "Voulez vous vraiment désactiver ?",
            type: "warning",
            showCancelButton: true,
            focusConfirm: true,
            showCloseButton: true,
            confirmButtonText: "<i class='fa fa-check'></i> Oui!",
            cancelButtonText: "<i class='fa fa-times'></i> No, Annuler!",
        }).then(async (result)  =>  {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                icon.remove('fa-unlock').addClass("fa-spinner fa-spin ");
                notyf.open({
                    type:"info",
                    message: "En cours...",
                    duration: 9000000,
                    dismissible: false
                });
               
                var formData = new FormData();
                formData.append('usersId', JSON.stringify(id_user));
                try {
                    const request = await axios.post(
                        Routing.generate('app_parametre_user_desactiver'), formData
                        )
                    ;
                    const response = request.data;

                
                    notyf.dismissAll();
                    icon.addClass('fa-unlock').removeClass("fa-spinner fa-spin ");
                    notyf.success(response);
                    usersArray = [];   
                    user = null; 
                    table.ajax.reload()



            
                } catch (error) {
                    icon.addClass('fa-unlock').removeClass("fa-spinner fa-spin ");
                    console.log(error);
                    const message = error.response.data;
                    notyf.dismissAll();
                    notyf.error(message);
                }

            }
        })
    })
    $("body").on("click", ".disable",async function(e){
        e.preventDefault();
        id_user = $(this).closest('tr').attr('id');
        const icon = $(this);
        if(!id_user) {
            notyf.dismissAll();
            notyf.error('Veuillez selectionné un user');
            return;
        }
        swalWithBootstrapButtons.fire({
            showClass: {
                popup: 'animatedSwal flipInX faster'
            },
            position: 'top',
            title: "Confirmation ?",
            text: "Voulez vous vraiment activer ?",
            type: "warning",
            showCancelButton: true,
            focusConfirm: true,
            showCloseButton: true,
            confirmButtonText: "<i class='fa fa-check'></i> Oui!",
            cancelButtonText: "<i class='fa fa-times'></i> No, Annuler!",
        }).then(async (result)  =>  {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                icon.remove('fa-unlock').addClass("fa-spinner fa-spin ");
                notyf.open({
                    type:"info",
                    message: "En cours...",
                    duration: 9000000,
                    dismissible: false
                });

               
                var formData = new FormData();
                formData.append('usersId', JSON.stringify(id_user));
                try {
                    const request = await axios.post(
                        Routing.generate('app_parametre_user_desactiver'), formData
                        )
                    ;
                    const response = request.data;

                
                    notyf.dismissAll();
                    icon.addClass('fa-unlock').removeClass("fa-spinner fa-spin ");
                    notyf.success(response);
                    usersArray = [];   
                    user = null; 
                    table.ajax.reload()
                    


            
                } catch (error) {
                    console.log(error);
                    icon.addClass('fa-unlock').removeClass("fa-spinner fa-spin ");
                    const message = error.response.data;
                    notyf.dismissAll();
                    notyf.error(message);
                }

            }
        })
    })

    const checkInputIfAllChildAreChecked = () => {
        $(".sousModules").map(function() {
            if($(this).parent().find('.inputOperation').not(':checked').length === 0) {
                $(this).find(".inputSousModule").prop("checked", true);
            }
        })
       
        $(".modules").map(function() {
            if($(this).parent().find('.inputSousModule').not(':checked').length === 0) {
                $(this).find(".inputModule").prop("checked", true);
            }
        })

        if($('.modules').find(".inputModule").not(':checked').length === 0) {
            $('.tous').prop("checked", true);
        }
        
    }

    $('body').on('click','#list_users tbody tr',function () {
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            user = null;
        } else {
            $("#list_users tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            user = $(this).attr('id');
        }

        console.log(user);
        
        
    })

    $("#privilege").on("click", async (e) => {
        e.preventDefault();
        $(".privilege input:checkbox").prop("checked", false);
        const icon = $("#privillege i");
        if(!user){
          notyf.error('Veuillez selection une ligne!');
          return;
        }
        try {
            icon.remove('fa-plus').addClass("fa-spinner fa-spin ");
            const request = await axios.post(Routing.generate('parametre_user_operations',{ user: user }));
            const response = request.data;
            response.map(element => {
                console.log(element);
                $(".buttons ."+element.id).prop("checked", true);
            })
            icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
            checkInputIfAllChildAreChecked();
            $("#privillege-modal").modal("show")
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
           
            icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
            
        }
    })
    
    $("#ajouter").on("click", async (e) => {
        e.preventDefault();
        $("#ajouter_modal").modal("show")
    })
    
    // 
    
    $(".Collapsable").on("click", function () {

        $(this).parent().children().toggle();
        $(this).toggle();
    
    });
    $(".inputSousModule").on('click', async function (){
        let url;
        let sous_module = $(this).attr("data-module");
        if($(this).is(":checked")){
            $(this).parent().parent().find(".inputOperation").prop("checked", true);
            url = Routing.generate('parametre_user_sousmodule',{ sousModule: sous_module, user: user, type: "add" })
        }else{
            $(this).parent().parent().find(".inputOperation").prop("checked", false);
            url = Routing.generate('parametre_user_sousmodule',{ sousModule: sous_module, user: user, type: "remove" })

        }
        checkInputIfAllChildAreChecked()
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $(".inputModule").on('click', async function (){
        let url;
        let module = $(this).attr("data-id");
        if($(this).is(":checked")){
            $(this).parent().parent().find("input:checkbox").prop("checked", true);
            url = Routing.generate('parametre_user_module',{ module: module, user: user, type: "add" })
        }else{
            $(this).parent().parent().find("input:checkbox").prop("checked", false);
            url = Routing.generate('parametre_user_module',{ module: module, user: user, type: "remove" })

        }
        checkInputIfAllChildAreChecked();
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $(".inputOperation").on('click', async function (){
        let url;
        let operation = $(this).attr("data-operation");
        if($(this).is(":checked")){
            url = Routing.generate('parametre_user_operation',{ operation: operation, user: user, type: "add" })
        }else{
            url = Routing.generate('parametre_user_operation',{ operation: operation, user: user, type: "remove" })
            
        }
        checkInputIfAllChildAreChecked();
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $(".tous").on('click', async function (){
        let url;
        if($(this).is(":checked")){
            $(".tous").parent().parent().find("input:checkbox").prop("checked", true);
            url = Routing.generate('parametre_user_all',{ user: user, type: "add" })
        }else{
            $(".tous").parent().parent().find("input:checkbox").prop("checked", false);
            url = Routing.generate('parametre_user_all',{ user: user, type: "remove" })
        }
        checkInputIfAllChildAreChecked();
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })

    $("#ajouter_user").on("submit", async function(e) {
        e.preventDefault();
        const createCancelTokenHandler = createCancel();

        let formData = new FormData($(this)[0]);
        notyf.open({
            type:"info",
            message: "En cours...",
            duration: 90000,
        });    
        try {
            const request = await axios.post(
                Routing.generate('parametre_user_register_new'),
                formData,
                )
            ;
            const response = request.data;
            await notyf.dismissAll();
            console.log(response)
            $("#ajouter_modal").modal("hide")
            notyf.success(response); 
            table.ajax.reload()
        } catch (error) {
            console.log(error);
            const message = error.response.data;
            notyf.dismissAll();
            notyf.error(message);
        }
    })

    $("body").on("click", "#reinitialiser",async function(e){
        e.preventDefault();
        
        if(!usersArray || !user) {
            notyf.dismissAll();
            notyf.error('Veuillez selectionné un ou plusieurs users');
            return;
        }
        swalWithBootstrapButtons.fire({
            showClass: {
                popup: 'animatedSwal flipInX faster'
            },
            position: 'top',
            title: "Confirmation ?",
            text: "Voulez vous vraiment réinitialiser ?",
            type: "warning",
            showCancelButton: true,
            focusConfirm: true,
            showCloseButton: true,
            confirmButtonText: "<i class='fa fa-check'></i> Oui!",
            cancelButtonText: "<i class='fa fa-times'></i> No, Annuler!",
        }).then(async (result)  =>  {
        /* Read more about isConfirmed, isDenied below */
            if (result.isConfirmed) {
                notyf.open({
                    type:"info",
                    message: "En cours...",
                    duration: 9000000,
                    dismissible: false
                });
               
                var formData = new FormData();
                if(usersArray){
                    formData.append('usersId', JSON.stringify(usersArray));
                }else{
                    formData.append('usersId', JSON.stringify(user));
                }
                try {
                    const request = await axios.post(
                        Routing.generate('parametre_user_reinitialiser'), formData
                        )
                    ;
                    const response = request.data;

                
                    notyf.dismissAll();
                    notyf.success(response);

                    table.ajax.reload()

                    $("#checkall").prop("checked", false);
                    $("body .user_check").prop("checked", false);
                    usersArray = [];   
                    user = null;    
            
                } catch (error) {
                    console.log(error);
                    const message = error.response.data;
                    notyf.dismissAll();
                    notyf.error(message);
                }

            }
        })
    })

    $("#checkall").on('click', async function (){
        const userCheck = $("body .user_check");
        
        if ($("#checkall").prop("checked") == true) {
            userCheck.prop("checked", true);
            userCheck.map(function () {
                usersArray.push(this.value);
            });
        } else {
            userCheck.prop("checked", false);
            usersArray = [];
        }
        console.log(usersArray);
    })

    $('#list_users').on('click', 'input[type="checkbox"]', function(e) {
        e.stopPropagation();
        if ($(this).prop("checked") == true) {
            usersArray.push($(this).attr("id"));
        } else {
            const index = usersArray.indexOf($(this).attr("id"));
            usersArray.splice(index, 1);
        }
    })
    
})

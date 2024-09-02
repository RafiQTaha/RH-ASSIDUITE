
$(document).ready(function  () {
// var table = $("#datatables_gestion_inscription").DataTable({
//     lengthMenu: [
//         [10, 15, 25, 50, 100, 20000000000000],
//         [10, 15, 25, 50, 100, "All"],
//     ],
//     order: [[0, "desc"]],
//     ajax: "/inscription/groupes/list",
//     processing: true,
//     serverSide: true,
//     deferRender: true,
//     responsive: true,
//     scrollX: true,
//     drawCallback: function () {
//         idInscription.forEach((e) => {
//             $("body tr#" + e)
//             .find("input")
//             .prop("checked", true);
//         });
//         $("body tr#" + id_inscription).addClass('active_databales')
//     },
//     language: {
//         url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
//     },
// });

    $("#register").on("submit", async function(e) {
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
                Routing.generate('app_register_new'), 
                formData,
                {cancelToken: createCancelTokenHandler.token}
                )
            ;
            const response = request.data;
            await notyf.dismissAll();
            console.log(response)
            notyf.open({
                type:"info",
                message: response,
            }); 
            window.location = Routing.generate('app_login');
        } catch (error) {
            console.log(error);
            const message = error.response.data;
            notyf.dismissAll();
            notyf.error(message);
        }
    })


})


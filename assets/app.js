/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';


// start the Stimulus application
const $ = require("jquery");
const axios = require("axios");
const Swal = require("./components/includes/sweetalert2");
const moment = require('moment');


global.$ = global.jQuery = global.jquery = $;
global.axios = axios;
global.moment = moment;
global.Swal = Swal;
const SmoothScroll = require('./components/includes/smooth-scroll.polyfills.min.js');
global.SmoothScroll = SmoothScroll;

require('bootstrap/dist/js/bootstrap.bundle');
require('./components/includes/moment.js');
require('./components/includes/datepicker.min.js');
require('./components/includes/nouislider.min.js');
require('./components/includes/on-screen.umd.min.js');
require('./components/includes/simplebar.min.js');
require("select2");
require("select2/dist/css/select2.css");
require('./components/includes/volt.js');
require("summernote/dist/summernote-lite.min.css");
require("summernote/dist/summernote.js");
require("summernote/dist/lang/summernote-fr-FR.js");

require("@fortawesome/fontawesome-free/css/all.css");

require("./components/includes/datatables/core");
require("./components/includes/datatables/datatable-bs4");


const datatablesFrench = require("./components/includes/datatables_french.json");
global.datatablesFrench = datatablesFrench

import './bootstrap';


const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    },
})
global.Toast = Toast;
const swalWithBootstrapButtons = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-white mr-2 btn sySweetStyle',
        cancelButton: 'btn btn-warning btn sySweetStyle'
    },
    buttonsStyling: false
})

global.swalWithBootstrapButtons = swalWithBootstrapButtons;

import { Notyf } from 'notyf';
const notyf = new Notyf({
    position: {
        x: 'right',
        y: 'top',
    },
    duration: 5000,
    types: [
        {
            type: 'info',
            background: '#0948B3',
            icon: {
                className: 'fas fa-info-circle',
                tagName: 'span',
                color: '#fff'
            },
        },
        {
            type: 'warning',
            background: '#F5B759',
            icon: {
                className: 'fas fa-exclamation-triangle',
                tagName: 'span',
                color: '#fff'
            },
        }
    ],
    dismissible: true
});
global.notyf = notyf;


const routes = require("../public/js/fos_js_routes.json");
import Routing from "../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js";
const routing = Routing.setRoutingData(routes);
global.Routing = Routing;








let cancelToken
const createCancel = () => {
    notyf.dismissAll();
    if (typeof cancelToken != typeof undefined) {
        cancelToken.cancel("Operation canceled due to new request.")
    }
    cancelToken = axios.CancelToken.source()

    return cancelToken
}

global.createCancel = createCancel



// const renderSelect2 = () => {
//     $(".select").select2();
//     $('.modal .select').each(function() {  
//         var $p = $(this).parent(); 
//         $(this).select2({  
//          dropdownParent: $p  
//         });  
//     });
// }

// renderSelect2();

// global.renderSelect2 = renderSelect2;

// let dossierId = $('#dossierId').val();
// global.dossierId = dossierId;

// $('.dossierSelect').on('change', function(){
//     dossierId = $(this).val();
//     global.dossierId = dossierId;
// })

// $("#sitechoisir").on('click', function(){
//     var site_id = $("#site").val();

//     $.ajax({
//         type: "POST",
//         url: Routing.generate('app_redirect'),
//         data: { site_id: site_id },
//         dataType: "json", // Specify that the response is JSON

//         beforeSend: function() {
//             notyf.open({
//                 type:"info",
//                 message: "En cours...",
//                 duration: 90000,
//             });  
//         },

        
//         success: function(data){
//             console.log(data);
//             // var url = Routing.generate('app_redirect');
//             window.location.href = Routing.generate(data.redirect_url); // Change to the actual property in your response data

//             notyf.dismissAll();
            
//         },error:function(data){
//             notyf.dismissAll();
//             notyf.open({
//                 type:"error",
//                 message: "Merci de contacter SI !",
//                 duration: 90000,
//             }); 
//         }
//     });


// });
document.addEventListener('keydown', function (e) {
    if (e.which === 13) {
        swalWithBootstrapButtons.clickConfirm();
    }
});


$("#changement_password").on('click', function(){
    $('#modal_changement_password').modal('show')
})
$("#form_changement_password").on('submit',async function(e){
    e.preventDefault();
    notyf.open({
        type:"info",
        message: "En cours...",
        duration: 9000000,
    });
    var button = $(document.activeElement);
    button.prop('disabled', true)
    let formData = new FormData(this);
    try {
        const request = await axios.post(
            Routing.generate('app_changement_password'), formData
            )
        ;
        const response = request.data;
        
        window.location = Routing.generate('app_login');
       
        notyf.dismissAll();
        notyf.success(response);




  
    } catch (error) {
        button.prop('disabled', false)

        console.log(error);
        const message = error.response.data;
        notyf.dismissAll();
        notyf.error(message);
    }


})


$('#sidebar-toggle').on('click', function (event) {
    event.preventDefault();
    $("body .sidebar").toggleClass("contracted");
});


const loaderShow = () => {
    window.scrollTo(0,0)
    $('body').css('overflow','hidden')
    $('#loader').removeClass('d-none').addClass('d-flex')
}
const loaderHide = () => {
    window.scrollTo(0,0)
    $('body').css('overflow','auto')
    $('#loader').removeClass('d-flex').addClass('d-none')
}
global.loaderShow = loaderShow;
global.loaderHide = loaderHide;
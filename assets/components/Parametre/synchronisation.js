
$(document).ready(function  () {
    $(".block_page").html('Synchronisation')
    
    $("body .synchnisation_rh").on("click", async function(e) {
        e.preventDefault();
        $('.synchnisation_rh').addClass('disabled')
        syncSite()
    });
    
    const syncSite = async ()=>{
        const icon = $(".syn_site");
        icon.removeClass("fa-edit").addClass("fa-spinner fa-spin");
        // console.log(formData)
        try {
            // const request = await axios.post(Routing.generate('api_etablissement'));
            const request = await axios.post('/parametre/synchronisation/api_site');
            const response = request.data;
            $('.syn_site_content').text(' (Ajoutée: '+response.newRow+' Modifiée: '+response.updated+')').css('color','green')
            syncEmploye()
        } catch (error) {
            const message = error.response.data;
            $('.syn_site_content').text('('+message+')').css('color','red')
            $('.synchnisation_rh').removeClass('disabled')
        }
        icon.addClass("fa-edit").removeClass("fa-spinner fa-spin");
    }
        
    const syncEmploye = async ()=>{
        const icon = $(".syn_employe");
        icon.removeClass("fa-edit").addClass("fa-spinner fa-spin");
        try {
            // const request = await axios.post(Routing.generate('api_employe'));
            const request = await axios.post('/parametre/synchronisation/api_employe');
            const response = request.data;
            $('.syn_employe_content').text(' (Ajoutée: '+response.newRow+' Modifiée: '+response.updated+')').css('color','green')
            syncContract()
        } catch (error) {
            const message = error.response.data;
            $('.syn_employe_content').text('('+message+')').css('color','red')
            $('.synchnisation_rh').removeClass('disabled')
        }
        icon.addClass("fa-edit").removeClass("fa-spinner fa-spin");
    }
        
    const syncContract = async ()=>{
        const icon = $(".syn_contract");
        icon.removeClass("fa-edit").addClass("fa-spinner fa-spin");
        try {
            // const request = await axios.post(Routing.generate('api_contract'));
            const request = await axios.post('/parametre/synchronisation/api_contract');
            const response = request.data;
            $('.syn_contract_content').text(' (Ajoutée: '+response.newRow+' Modifiée: '+response.updated+')').css('color','green')
        } catch (error) {
            const message = error.response.data;
            $('.syn_contract_content').text('('+message+')').css('color','red')
        }
        $('.synchnisation_rh').removeClass('disabled')
        icon.addClass("fa-edit").removeClass("fa-spinner fa-spin");
    }
        
    
})
    
    
    
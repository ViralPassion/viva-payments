
jQuery(document).ready(function($){
    var card_logo_url;
  	var card_suuccess_url;
  	var card_type;
  	
  	
	$('#viva_card_no').validateCreditCard(function(result) {
      
            $( "#viva_card_name" ).val($("#billing_last_name").val()+' '+$("#billing_first_name").val());
            $("#viva_card_no").css({'color':'black'});
        console.log({Card_type: (result.card_type == null ? '-' : result.card_type.name),
                 	 Valid: result.valid,
                     Length_valid: result.length_valid,
                     Luhn_valid: result.luhn_valid});
      	if(result.card_type){
          if(result.card_type.name == "visa"){
              card_logo_url = viva_plugin_dir+'assets/visa.png';
          }
          if(result.card_type.name == "visa_electron"){
              card_logo_url = viva_plugin_dir+'assets/ve.png';
          }
          if(result.card_type.name == "mastercard"){
              card_logo_url = viva_plugin_dir+'assets/mc.png';
          }
          if(result.card_type.name == "maestro"){
              card_logo_url = viva_plugin_dir+'assets/me.png';
          }
          if(result.card_type.name == "discover"){
              card_logo_url = viva_plugin_dir+'assets/do.png';
          }
          if(result.card_type.name == "amex"){
              card_logo_url = viva_plugin_dir+'assets/amex.png';
          }
          if(result.valid){
            card_type = result.card_type.name;
          	card_suuccess_url = viva_plugin_dir+'assets/success.png';
            $( "#viva_card_exp" ).focus();
          }else{
          	card_suuccess_url ='';
            if($("#viva_card_no").val().length==16){
            	$("#viva_card_no").css({'color':'red'});
            }
          }
        }
      	else{
      		card_logo_url = viva_plugin_dir+'assets/cc.png';
        }
      	$("#viva_card_no").css({"background": "url("+card_logo_url+") no-repeat scroll 7px 4px, url("+card_suuccess_url+") no-repeat scroll 96%", "padding-left": "50px", "background-size": "35px, 20px"});
      
      
      
    },{accept: ['visa','visa_electron','mastercard','maestro','discover','amex','diners_club_international','diners_club_carte_blanche']});


	 $('#viva_card_exp').bind("change paste keyup", function() {
       
        $('#viva_card_exp').css({'color':'black',"background": "none"});
     	if ($('#viva_card_exp').val().indexOf("/") >= 0){
        	if($('#viva_card_exp').val().length==5){
            	var arr = $('#viva_card_exp').val().split('/');
              	if(arr[0]<=12 && arr[1]>=15){
                	$( "#viva_card_ccv" ).focus();
                  	$("#viva_card_exp").css({"background": " url("+viva_plugin_dir+'assets/success.png'+") no-repeat scroll 96%",  "background-size": " 20px"});
                }else{
                	$('#viva_card_exp').css({'color':'red'});
                }
            }
        }else{
        	if($('#viva_card_exp').val().length==2){
            	$('#viva_card_exp').val($('#viva_card_exp').val()+'/');
            }
        }
     });
  	$('#viva_card_ccv').bind("change paste keyup", function() {
    	
      $('#viva_card_ccv').css({'color':'black',"background": "none"});
      if(card_type != 'amex'){
      	if($('#viva_card_ccv').val().length == 3){
            $( "#viva_card_name" ).val($("#billing_last_name").val()+' '+$("#billing_first_name").val());
        	$( "#viva_card_name" ).focus();
          	$("#viva_card_ccv").css({"background": " url("+viva_plugin_dir+'assets/success.png'+") no-repeat scroll 96%",  "background-size": " 20px"});
        }
      }else{
      	if($('#viva_card_ccv').val().length == 4){
            $( "#viva_card_name" ).val($("#billing_last_name").val()+' '+$("#billing_first_name").val());
        	$( "#viva_card_name" ).focus();
          	$("#viva_card_ccv").css({"background": " url("+viva_plugin_dir+'assets/success.png'+") no-repeat scroll 96%",  "background-size": " 20px"});
        }
      }
    
    });

	$(function() {
      $('#viva_form').on('keydown', '.numonly', function(e){-1!==$.inArray(e.keyCode,[46,8,9,27,13,110,190])||/65|67|86|88/.test(e.keyCode)&&(!0===e.ctrlKey||!0===e.metaKey)||35<=e.keyCode&&40>=e.keyCode||(e.shiftKey||48>e.keyCode||57<e.keyCode)&&(96>e.keyCode||105<e.keyCode)&&e.preventDefault()});
       $('#viva_form').on('keydown', '.nobksp', function(e){if(e.keyCode == 8){$(this).val('')}});
    })


});




Array.prototype.forEach.call(document.body.querySelectorAll("*[data-mask]"), applyDataMask);

function applyDataMask(field) {
    var mask = field.dataset.mask.split('');
    
    // For now, this just strips everything that's not a number
    function stripMask(maskedData) {
        function isDigit(char) {
            return /\d/.test(char);
        }
        return maskedData.split('').filter(isDigit);
    }
    
    // Replace `_` characters with characters from `data`
    function applyMask(data) {
        return mask.map(function(char) {
            if (char != '_') return char;
            if (data.length == 0) return char;
            return data.shift();
        }).join('')
    }
    
    function reapplyMask(data) {
        return applyMask(stripMask(data));
    }
    
    function changed() {   
        var oldStart = field.selectionStart;
        var oldEnd = field.selectionEnd;
        
        field.value = reapplyMask(field.value);
        
        field.selectionStart = oldStart;
        field.selectionEnd = oldEnd;
    }
    
    field.addEventListener('click', changed)
    field.addEventListener('keyup', changed)
}






(function ($) {
  
    $(document).ready(function () {
        maxuploadsize = scriptParams.asqa_maximum_uploads;

$( ".asqa-editor-fade" ).click(function() { 
     
  setTimeout(function() { 
   $(".asqa-field-form_answer-post_attachment").prepend("<div id='attachareatitle'><b>Anhänge</b></div><script>function asqa_uploadclick2(){var clickedalready = false;var test = '';if(clickedalready==false){test = document.getElementById('form_answer-post_attachment').value;if (test==''){document.getElementById('form_answer-post_attachment').click();clickedalready = true;}}if(clickedalready==false){test = document.getElementById('form_answer-post_attachment2').value;if (test==''){document.getElementById('form_answer-post_attachment2').click();clickedalready = true;}}if(clickedalready==false){test = document.getElementById('form_answer-post_attachment3').value;if (test==''){document.getElementById('form_answer-post_attachment3').click();clickedalready = true;}}if(clickedalready==false){test = document.getElementById('form_answer-post_attachment4').value;if (test==''){document.getElementById('form_answer-post_attachment4').click();clickedalready = true;}}if(clickedalready==false){test = document.getElementById('form_answer-post_attachment5').value;if (test==''){document.getElementById('form_answer-post_attachment5').click();clickedalready = true;}}}function asqa_delete_file_in_list2(id){number = id.substr(16,1);if(number==1) { document.getElementById('form_answer-post_attachment').value = null;document.getElementById('asqa-file-name1').remove();document.getElementById('asqa-file-delete1').remove();}if(number==2) { document.getElementById('form_answer-post_attachment').value = null;document.getElementById('asqa-file-name2').remove();document.getElementById('asqa-file-delete2').remove();}if(number==3) { document.getElementById('form_answer-post_attachment3').value = null;document.getElementById('asqa-file-name3').remove();document.getElementById('asqa-file-delete3').remove(); }if(number==4) { document.getElementById('form_answer-post_attachment4').value = null;document.getElementById('asqa-file-name4').remove();document.getElementById('asqa-file-delete4').remove();  }if(number==5) { document.getElementById('form_answer-post_attachment5').value = null;document.getElementById('asqa-file-name5').remove();document.getElementById('asqa-file-delete5').remove();  }}</script>");

$(".asqa-field-form_answer-post_id.asqa-field-type-input").append("<span id='asqa_upload_button_fake' class='btn btn-default extf-btn-file' onclick='asqa_uploadclick2()' data-attachment-item-input=''>Dateien hochladen (Maximale Dateigröße: <b>"+maxuploadsize+" MB</b>)</span>");  
        $('#form_answer-post_attachment2').attr('name', 'form_answer-post_attachment[]');
        $('#form_answer-post_attachment3').attr('name', 'form_answer-post_attachment[]');
        $('#form_answer-post_attachment4').attr('name', 'form_answer-post_attachment[]');
        $('#form_answer-post_attachment5').attr('name', 'form_answer-post_attachment[]');
         $('#form_answer-post_attachment').css("display", "none");
        $('#form_answer-post_attachment2').css("display", "none");
        $('#form_answer-post_attachment3').css("display", "none");
        $('#form_answer-post_attachment4').css("display", "none");
        $('#form_answer-post_attachment5').css("display", "none");

        $( "#form_answer-post_attachment" ).change(function() {
             if(checkdoubles2("#form_answer-post_attachment")==true) {
             $('.asqa-field-form_answer-post_attachment .asqa-field-group-w .asqa-field-desc').css("display", "none");
 $( ".asqa-field-form_answer-post_attachment .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name1'>DATEINAME-DUMMY</div>");
    $('#asqa-file-name1').html( $('#form_answer-post_attachment').val().substring(12,$('#form_answer-post_attachment').val().length)); 
    $('#form_answer-post_attachment').css("display", "none");
 $( ".asqa-field-form_answer-post_attachment .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete1' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list2(this.parentNode.id)' value='entfernen'></div>");
 $('#asqa-file-delete1').css("display", "block");  

              }
        });


        $( "#form_answer-post_attachment2" ).change(function() {
             if(checkdoubles2("#form_answer-post_attachment2")==true) {
             $('.asqa-field-form_answer-post_attachment2 .asqa-field-group-w .asqa-field-desc').css("display", "none");
              $( ".asqa-field-form_answer-post_attachment2 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name2'>DATEINAME-DUMMY</div>");
 $( ".asqa-field-form_answer-post_attachment2 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete2' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list2(this.parentNode.id)' value='entfernen'></div>");
     $('#asqa-file-name2').html( $('#form_answer-post_attachment2').val().substring(12,$('#form_answer-post_attachment2').val().length));              
              $('#asqa-file-delete2').css("display", "block");  
         }   
        });

        $( "#form_answer-post_attachment3" ).change(function() {
             if(checkdoubles2("#form_answer-post_attachment3")==true) {
         
             $('.asqa-field-form_answer-post_attachment3 .asqa-field-group-w .asqa-field-desc').css("display", "none");
              $( ".asqa-field-form_answer-post_attachment3 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name3'>DATEINAME-DUMMY</div>"); 
          $( ".asqa-field-form_answer-post_attachment3 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete3' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list2(this.parentNode.id)' value='entfernen'></div>");
              $('#asqa-file-name3').html( $('#form_answer-post_attachment3').val().substring(12,$('#form_answer-post_attachment3').val().length));     
              $('#asqa-file-delete3').css("display", "block");  
          }  
        });

        $( "#form_answer-post_attachment4" ).change(function() {
             if(checkdoubles2("#form_answer-post_attachment4")==true) {

             $('.asqa-field-form_answer-post_attachment4 .asqa-field-group-w .asqa-field-desc').css("display", "none");
              $( ".asqa-field-form_answer-post_attachment4 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name4'>DATEINAME-DUMMY</div>"); 
 $( ".asqa-field-form_answer-post_attachment4 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete4' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list2(this.parentNode.id)' value='entfernen'></div>");
      $('#asqa-file-name4').html( $('#form_answer-post_attachment4').val().substring(12,$('#form_answer-post_attachment4').val().length));             
              $('#asqa-file-delete4').css("display", "block"); 
         }  
        });

        $( "#form_answer-post_attachment5" ).change(function() {
             checkdoubles2("#form_answer-post_attachment5");
              $( ".asqa-field-form_answer-post_attachment5 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name5'>DATEINAME-DUMMY</div>");
              $( ".asqa-field-form_answer-post_attachment5 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete5' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list2(this.parentNode.id)' value='entfernen'></div>");
     $('#asqa-file-name5').html( $('#form_answer-post_attachment5').val().substring(12,$('#form_answer-post_attachment5').val().length));               $('#asqa-file-delete5').css("display", "block"); 
        });
    



    }, 1000);

  




});
        //alert(maxuploadsize);
$(".asqa-field-form_question-post_attachment").prepend("<div id='attachareatitle'><b>Anhänge</b></div>");
$(".asqa-field-form_question-post_id.asqa-field-type-input").append("<span id='asqa_upload_button_fake' class='btn btn-default extf-btn-file' onclick='asqa_uploadclick()' data-attachment-item-input=''>Dateien hochladen (Maximale Dateigröße: <b>"+maxuploadsize+" MB</b>)</span>");  
        $('#form_question-post_attachment2').attr('name', 'form_question-post_attachment[]');
        $('#form_question-post_attachment3').attr('name', 'form_question-post_attachment[]');
        $('#form_question-post_attachment4').attr('name', 'form_question-post_attachment[]');
        $('#form_question-post_attachment5').attr('name', 'form_question-post_attachment[]');
         $('#form_question-post_attachment').css("display", "none");
        $('#form_question-post_attachment2').css("display", "none");
        $('#form_question-post_attachment3').css("display", "none");
        $('#form_question-post_attachment4').css("display", "none");
        $('#form_question-post_attachment5').css("display", "none");

        $( "#form_question-post_attachment" ).change(function() {
             if(checkdoubles("#form_question-post_attachment")==true) {
             $('.asqa-field-form_question-post_attachment .asqa-field-group-w .asqa-field-desc').css("display", "none");
 $( ".asqa-field-form_question-post_attachment .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name1'>DATEINAME-DUMMY</div>");
    $('#asqa-file-name1').html( $('#form_question-post_attachment').val().substring(12,$('#form_question-post_attachment').val().length)); 
    $('#form_question-post_attachment').css("display", "none");
 $( ".asqa-field-form_question-post_attachment .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete1' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list(this.parentNode.id)' value='entfernen'></div>");
 $('#asqa-file-delete1').css("display", "block");  

              }
        });


        $( "#form_question-post_attachment2" ).change(function() {
             if(checkdoubles("#form_question-post_attachment2")==true) {
             $('.asqa-field-form_question-post_attachment2 .asqa-field-group-w .asqa-field-desc').css("display", "none");
              $( ".asqa-field-form_question-post_attachment2 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name2'>DATEINAME-DUMMY</div>");
 $( ".asqa-field-form_question-post_attachment2 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete2' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list(this.parentNode.id)' value='entfernen'></div>");
     $('#asqa-file-name2').html( $('#form_question-post_attachment2').val().substring(12,$('#form_question-post_attachment2').val().length));              
              $('#asqa-file-delete2').css("display", "block");  
         }   
        });

        $( "#form_question-post_attachment3" ).change(function() {
             if(checkdoubles("#form_question-post_attachment3")==true) {
         
             $('.asqa-field-form_question-post_attachment3 .asqa-field-group-w .asqa-field-desc').css("display", "none");
              $( ".asqa-field-form_question-post_attachment3 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name3'>DATEINAME-DUMMY</div>"); 
          $( ".asqa-field-form_question-post_attachment3 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete3' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list(this.parentNode.id)' value='entfernen'></div>");
              $('#asqa-file-name3').html( $('#form_question-post_attachment3').val().substring(12,$('#form_question-post_attachment3').val().length));     
              $('#asqa-file-delete3').css("display", "block");  
          }  
        });

        $( "#form_question-post_attachment4" ).change(function() {
             if(checkdoubles("#form_question-post_attachment4")==true) {

             $('.asqa-field-form_question-post_attachment4 .asqa-field-group-w .asqa-field-desc').css("display", "none");
              $( ".asqa-field-form_question-post_attachment4 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name4'>DATEINAME-DUMMY</div>"); 
 $( ".asqa-field-form_question-post_attachment4 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete4' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list(this.parentNode.id)' value='entfernen'></div>");
      $('#asqa-file-name4').html( $('#form_question-post_attachment4').val().substring(12,$('#form_question-post_attachment4').val().length));             
              $('#asqa-file-delete4').css("display", "block"); 
         }  
        });

        $( "#form_question-post_attachment5" ).change(function() {
             checkdoubles("#form_question-post_attachment5");
              $( ".asqa-field-form_question-post_attachment5 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-name5'>DATEINAME-DUMMY</div>");
              $( ".asqa-field-form_question-post_attachment5 .asqa-field-group-w .asqa-upload-c" ).append("<div id='asqa-file-delete5' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list(this.parentNode.id)' value='entfernen'></div>");
     $('#asqa-file-name5').html( $('#form_question-post_attachment5').val().substring(12,$('#form_question-post_attachment5').val().length));               $('#asqa-file-delete5').css("display", "block"); 
        });

     
       

    
  



        $('textarea.autogrow, textarea#post_content').autogrow({
            onInitialize: true
        });

        $('.asqa-categories-list li .asqa-icon-arrow-down').on('click', function (e) {
            e.preventDefault();
            $(this).parent().next().slideToggle(200);
        });


        $('.asqa-radio-btn').on('click', function () {
            $(this).toggleClass('active');
        });

        $('.bootstrasqa-tagsinput > input').on('keyup', function (event) {
            $(this).css(width, 'auto');
        });

        $('.asqa-label-form-item').on('click', function (e) {
            e.preventDefault();
            $(this).toggleClass('active');
            var hidden = $(this).find('input[type="hidden"]');
            hidden.val(hidden.val() == '' ? $(this).data('label') : '');
        });

    });


    function asqa_uploadclick2(){
        alert("test1");
        var clickedalready = false;
        var test = "";
        if(clickedalready==false){
            test = document.getElementById('form_answer-post_attachment').value;
            if (test==""){
            document.getElementById('form_answer-post_attachment').click();
            clickedalready = true;      
            }
        }

        if(clickedalready==false){
            test = document.getElementById('form_answer-post_attachment2').value;
            if (test==""){
            document.getElementById('form_answer-post_attachment2').click();
            clickedalready = true;      
            }
        }

        if(clickedalready==false){
            test = document.getElementById('form_answer-post_attachment3').value;
            if (test==""){
            document.getElementById('form_answer-post_attachment3').click();
            clickedalready = true;      
            }
        }

        if(clickedalready==false){
            test = document.getElementById('form_answer-post_attachment4').value;
            if (test==""){
            document.getElementById('form_answer-post_attachment4').click();
            clickedalready = true;      
            }
        }

        if(clickedalready==false){
            test = document.getElementById('form_answer-post_attachment5').value;
            if (test==""){
            document.getElementById('form_answer-post_attachment5').click();
            clickedalready = true;      
            }
        }
        
    }

    function asqa_delete_file_in_list2(id){
        number = id.substr(16,1);
    
        if(number==1) { 
        document.getElementById("form_answer-post_attachment").value = null;
        document.getElementById("asqa-file-name1").remove();
        document.getElementById("asqa-file-delete1").remove();  
        }

        if(number==2) { 
        document.getElementById("form_answer-post_attachment2").value = null;
        document.getElementById("asqa-file-name2").remove();
        document.getElementById("asqa-file-delete2").remove();  
        }

        if(number==3) { 
        document.getElementById("form_answer-post_attachment3").value = null;
        document.getElementById("asqa-file-name3").remove();
        document.getElementById("asqa-file-delete3").remove();  
        }

        if(number==4) { 
        document.getElementById("form_answer-post_attachment4").value = null;
        document.getElementById("asqa-file-name4").remove();
        document.getElementById("asqa-file-delete4").remove();  
        }

        if(number==5) { 
        document.getElementById("form_answer-post_attachment5").value = null;
        document.getElementById("asqa-file-name5").remove();
        document.getElementById("asqa-file-delete5").remove();  
        }

        
        
    }



      function checkdoubles2(inputid){
           // alert("checkdoubles....");
var input1 = $( "#form_answer-post_attachment" ).val();  if (input1==""){ input1 = "input1";} 
var input2 = $( "#form_answer-post_attachment2" ).val();  if (input2==""){ input2 = "input2";} 
var input3 = $( "#form_answer-post_attachment3" ).val();  if (input3==""){ input3 = "input3";} 
var input4 = $( "#form_answer-post_attachment4" ).val();  if (input4==""){ input4 = "input4";} 
var input5 = $( "#form_answer-post_attachment5" ).val();  if (input5==""){ input5 = "input5";} 
var arr = 
[   
    input1,   
    input2,
    input3, 
    input4, 
    input5
    ];
console.log(arr);
if (hasDuplicates(arr)) {
    console.log("Duplicate elements found.");
    
    alert("Sie haben diese Datei doppelt gewählt. Die Doppelte Datei wird ignoriert.");
 
    $(inputid).val('');
    return false;

}
else {
    console.log("No Duplicates found.");
    return true;
}
 
        }
    function hasDuplicates(arr) {
    return new Set(arr).size !== arr.length;
}

       function checkdoubles(inputid){
           // alert("checkdoubles....");
var input1 = $( "#form_question-post_attachment" ).val();  if (input1==""){ input1 = "input1";} 
var input2 = $( "#form_question-post_attachment2" ).val();  if (input2==""){ input2 = "input2";} 
var input3 = $( "#form_question-post_attachment3" ).val();  if (input3==""){ input3 = "input3";} 
var input4 = $( "#form_question-post_attachment4" ).val();  if (input4==""){ input4 = "input4";} 
var input5 = $( "#form_question-post_attachment5" ).val();  if (input5==""){ input5 = "input5";} 
var arr = 
[   
    input1,   
    input2,
    input3, 
    input4, 
    input5
    ];
console.log(arr);
if (hasDuplicates(arr)) {
    console.log("Duplicate elements found.");
    
    alert("Sie haben diese Datei doppelt gewählt. Die Doppelte Datei wird ignoriert.");
 
    $(inputid).val('');
    return false;

}
else {
    console.log("No Duplicates found.");
    return true;
}
 
        }


    $('[asqa-loadmore]').on('click', function (e) {
        e.preventDefault();
        var self = this;
        var args = JSON.parse($(this).attr('asqa-loadmore'));
        args.action = 'asqa_ajax';

        if (typeof args.asqa_ajax_action === 'undefined')
            args.asqa_ajax_action = 'bp_loadmore';

        SmartQa.showLoading(this);
        SmartQa.ajax({
            data: args,
            success: function (data) {
                SmartQa.hideLoading(self);
                console.log(data.element);
                if (data.success) {
                    $(data.element).append(data.html);
                    $(self).attr('asqa-loadmore', JSON.stringify(data.args));
                    if (!data.args.current) {
                        $(self).hide();
                    }
                }
            }
        });
    });

})(jQuery);



(function ($) {
  
    $(document).ready(function () {
    maxuploadsize = scriptParams.asqa_maximum_uploads;
    //alert(maxuploadsize);
        $( "#form_question-post_attachment" ).addClass("asqa_special_upload");
        $(".asqa-field-form_question-post_attachment").append("<div id='asqa-uploadfiles' class='asqa-field-group-w'><div id='asqa-file-row' class='asqa-file-row'><div id='asqa-file-name' class='asqa-file-name'>dateiname</div><div id='asqa-file-delete' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list(this.parentNode.id)' value='entfernen'></div></div></div>");
        
        $(".asqa-field-form_question-post_attachment .asqa-upload-c").insertAfter(".asqa-field-form_question-post_attachment .asqa-field-desc");
        $("#asqa-uploadfiles").insertAfter(".asqa-field-form_question-post_attachment .asqa-form-label");

        $(".asqa-field-form_question-post_attachment .asqa-upload-c").append("<span id='asqa_upload_button_fake' class='btn btn-default extf-btn-file' onclick='asqa_uploadclick()' data-attachment-item-input=''>Dateien hochladen (Maximale Dateigröße: <b>"+maxuploadsize+" MB</b>)</span>");
        
        $('#form_question-post_attachment').attr("name", "form_question-post_attachment[]");
        $('#form_question-post_attachment').removeAttr("multiple");

     
        $( ".asqa_special_upload" ).change(function() {
            var derfilesarray = [];
            $('.asqa_special_upload').filter(':input').each(function(){
                derfilesarray.push(this.value);
            
            });
            console.log(derfilesarray);

            function hasDuplicates(arr) {
                //alert("f1 works");
                return new Set(arr).size !== arr.length;
            }

            if (hasDuplicates(derfilesarray)) {

                console.log("Duplicate elements found.");

                alert("Sie haben diese Datei doppelt hinzugefügt.\nDie Auswahl wird ignoriert.");
                //alert(this.value);
                this.value = "";

                return;
               
                }
                else {
                console.log("No Duplicates found.");
                }

            nummer = $( 'input[type="file"]').length;
            wen = nummer;
            nummer = nummer + 1;
            
            var newel = $('.asqa_special_upload:last').clone();
            var counter = this.id+"x-"+nummer;
            
            $(newel).attr("id",counter);
            $(newel).insertBefore(".asqa_special_upload:last"); 
            $( "#form_question-post_attachment" ).val('');

            counterx = counter + "-filenamedisplay";

            $("#asqa-uploadfiles").append('<div id="'+counterx+'" class="asqa-file-row" style="display:flex;"><div id="'+counterx+'asqa-file-name" class="asqa-file-name" style="display:none;">filedummy.txt</div><div id="'+counterx+'asqa-file-delete" class="asqa-file-delete" style="display:none;"><input type="button"  onclick="asqa_delete_file_in_list(this.parentNode.id)"  value="entfernen"></div></div>');
            var find = '#form_question-post_attachmentx-'+nummer+'-filenamedisplay';
            
            $(find).attr("style", "border-top:0;");

            var find2 = "#form_question-post_attachmentx-"+nummer+"-filenamedisplayasqa-file-name";
            var find3 = "#form_question-post_attachmentx-"+nummer+"-filenamedisplayasqa-file-delete";
            var find4 = "#form_question-post_attachmentx-"+nummer;
            var filename = $(find4).val().replace(/C:\\fakepath\\/i, '');
           
            $(find2).html(filename);
            $(find2).attr("style", "display:block;");
            $(find3).attr("style", "display:block;");

            if(nummer>5){   
                $("#asqa_upload_button_fake").attr('onclick','');
                $("#asqa_upload_button_fake").html('Maximalanzahl erreicht!');
                $("#asqa_maximum_attachs").attr('style','color:red;');
            } 

     });

        $( ".asqa-editor-fade" ).click(function() {

        myInterval = setInterval(function() { 
        var testinger = $( "#asqa_answer_uploader" ).text();
        var testr = testinger.substring(0,17);
        //alert(testr);
        $( "#form_answer-post_attachment" ).addClass("asqa_special_upload_a");
        $(".asqa-field-form_answer-post_attachment").append("<script>function asqa_delete_file_in_list_a(id){const myArray = id.split('-');var buttontext = 'Dateien hochladen (Maximale Dateigröße: <b>"+maxuploadsize+" MB</b>)'; var number = myArray[2];var find1 = 'form_answer-post_attachmentx-'+number;var find5='asqa_answer_uploader'; var find2 = 'form_answer-post_attachmentx-'+number+'-filenamedisplay';document.getElementById(find1).remove();document.getElementById(find2).remove(); document.getElementById(find5).innerHTML=buttontext;document.getElementById('asqa_maximum_attachs').setAttribute('style','color:#666;');}</script><div id='asqa-uploadfiles' class='asqa-field-group-w'><div id='asqa-file-row' class='asqa-file-row'><div id='asqa-file-name' class='asqa-file-name'>dateiname</div><div id='asqa-file-delete' class='asqa-file-delete'><input type='button' onclick='asqa_delete_file_in_list_a(this.parentNode.id)' value='entfernen'></div></div></div>");
        
        $(".asqa-field-form_answer-post_attachment .asqa-upload-c").insertAfter(".asqa-field-form_answer-post_attachment .asqa-field-desc");
        $("#asqa-uploadfiles").insertAfter(".asqa-field-form_answer-post_attachment .asqa-form-label");

        $(".asqa-field-form_answer-post_attachment .asqa-upload-c").append("<span class='btn btn-default extf-btn-file' id='asqa_answer_uploader' data-attachment-item-input=''>Dateien hochladen (Maximale Dateigröße: <b>"+maxuploadsize+" MB</b>)</span>");

        $( "#asqa_answer_uploader" ).click(function() {
            nummer3 = $( 'input[type="file"]').length;
            if(nummer3<6){
        document.getElementById('form_answer-post_attachment').click(); }  });
        
        $('#form_answer-post_attachment').attr("name", "form_answer-post_attachment[]");
        $('#form_answer-post_attachment').removeAttr("multiple");

        $( ".asqa_special_upload_a" ).change(function() { //alert("changed!");
              var derfilesarray = [];
            $('.asqa_special_upload_a').filter(':input').each(function(){
                derfilesarray.push(this.value);
            
            });
            console.log(derfilesarray);

            function hasDuplicates(arr) {
                //alert("f1 works");
                return new Set(arr).size !== arr.length;
            }

            if (hasDuplicates(derfilesarray)) {

                console.log("Duplicate elements found.");

                alert("Sie haben diese Datei doppelt hinzugefügt.\nDie Auswahl wird ignoriert.");
                //alert(this.value);
                this.value = "";

                return;
               
                }
                else {
                console.log("No Duplicates found.");
                }

        nummer = $( 'input[type="file"]').length;
        wen = nummer;
        nummer = nummer + 1;
        
        var newel = $('.asqa_special_upload_a:last').clone();
        var counter = this.id+"x-"+nummer;
        
        $(newel).attr("id",counter);
        $(newel).insertBefore(".asqa_special_upload_a:last"); 
        $( "#form_answer-post_attachment" ).val('');

        counterx = counter + "-filenamedisplay";

        $("#asqa-uploadfiles").append('<div id="'+counterx+'" class="asqa-file-row" style="display:flex;"><div id="'+counterx+'asqa-file-name" class="asqa-file-name" style="display:none;">filedummy.txt</div><div id="'+counterx+'asqa-file-delete" class="asqa-file-delete" style="display:none;"><input type="button"  onclick="asqa_delete_file_in_list_a(this.parentNode.id)"  value="entfernen"></div></div>');
        var find = '#form_answer-post_attachmentx-'+nummer+'-filenamedisplay';
        
        $(find).attr("style", "border-top:0;");

        var find2 = "#form_answer-post_attachmentx-"+nummer+"-filenamedisplayasqa-file-name";
        var find3 = "#form_answer-post_attachmentx-"+nummer+"-filenamedisplayasqa-file-delete";
        var find4 = "#form_answer-post_attachmentx-"+nummer;
        var filename = $(find4).val().replace(/C:\\fakepath\\/i, '');
       
        $(find2).html(filename);
        $(find2).attr("style", "display:block;");
        $(find3).attr("style", "display:block;");

        
        if(nummer>5){   
                $("#asqa_answer_uploader").attr('onclick','');
                $("#asqa_answer_uploader").html('Maximalanzahl erreicht!');
                $("#asqa_maximum_attachs").attr('style','color:red;');
            } 


        
        });
        testinger = $( "#asqa_answer_uploader" ).text();
        testr = testinger.substr(0,17);
        //alert(testr);
        if(testr=='Dateien hochladen'){
         //   alert('fertig');
        clearInterval(myInterval); 
        }

        }, 500);
    
        });


        $( ".asqa-attachment-item__btn-del" ).click(function() {
            var title = this.title;
            if (confirm("Achtung! Sie sind gerade dabei diese\nDatei["+title+"] endgültig zu löschen.\n\nSind Sie sich absolut sicher?") == true) {

                par = this.id.split("-");
                nummer = par[3];
                                                            
                document.getElementById("asqa-attachment-item-id-"+nummer).remove();

                x = document.getElementsByClassName("asqa-attachment-item").length;
                if (x<1){ document.getElementById("asqa-display-attachments").remove();}

                 //call php function with ajax
                asqaredirect = "#";
                $.ajax({
                        type: 'POST',
                        url: ajaxurl,
                        data: {
                           action: 'asqa_delete_attachment_forced',
                           attachmentid: nummer,
                        },  
                       success: function(resp) {
                                  if( resp && resp.code != undefined && resp.code == 'success' ) {
                                    alert(resp.message);
                                   
                                    } else {
                                    alert(resp.message);
                                  
                                    }
                                  },
                                  error: function() {
                                    alert('There was some error performing!');
                                  }
                        });

            } 
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



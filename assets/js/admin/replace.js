
jQuery(document).ready(function ($) {
   
    
     function cpmw_chnage_title(evt){    
        let data = document.querySelectorAll('.csf-cloneable-value ');   
        $.each(data, function (index, value) {
        //    setTimeout(() => {
            let replaced_text ="";
            if ($(this).text().indexOf("| 1")!="-1"){
                if ($(this).text().indexOf("Binance") != "-1" || $(this).text().indexOf("Ethereum Main") != "-1"){
                    replaced_text = $(this).text().replace("| 1", '<span class="cpmw_enabled">Enabled</span>')
                }else{
                    replaced_text = $(this).text().replace("| 1", '<span class="cpmw_enabled">Enabled</span>')
                }
                
            }
            else if ($(this).text().indexOf("| 0") != "-1"){
                if ($(this).text().indexOf("Binance") != "-1" || $(this).text().indexOf("Ethereum Main") != "-1") {
                    replaced_text = $(this).text().replace("| 0", '<span class="cpmw_disabled">Disabled</span>')
                } else {
                replaced_text = $(this).text().replace("| 0", '<span class="cpmw_disabled">Disabled</span>')
                }
            }
            else if ($(this).text().indexOf(" | ") != "-1") {
                if ($(this).text().indexOf("Binance") != "-1" || $(this).text().indexOf("Ethereum Main") != "-1") {
                    replaced_text = $(this).text().replace("| ", '<span class="cpmw_disabled">Disabled</span>')
                } else {
                replaced_text = $(this).text().replace(" | ", '<span class="cpmw_disabled">Disabled</span>')
                }
            }
            else{
                replaced_text = $(this).html()
            }
               
            $(this).html(replaced_text);
             
       // }, 100);
            
        });
    }
        function cpmwp_dynamic_title_change(evt){
           // var $this = $(evt);    
            let data = $(evt).find('.csf-cloneable-value ');
            $.each(data, function (index, value) {
             //   setTimeout(() => {
                    let replaced_text = "";
                    if ($(this).text().indexOf("| 1") != "-1") {
                        if ($(this).text().indexOf("Binance") != "-1" || $(this).text().indexOf("Ethereum Main") != "-1") {
                            replaced_text = $(this).text().replace("| 1", '<span class="cpmw_enabled">Enabled</span>')
                        } else {
                        replaced_text = $(this).text().replace("| 1", '<span class="cpmw_enabled">Enabled</span>')
                        }
                    }
                    else if ($(this).text().indexOf("| 0") != "-1") {
                        if ($(this).text().indexOf("Binance") != "-1" || $(this).text().indexOf("Ethereum Main") != "-1") {
                            replaced_text = $(this).text().replace("| 0", '<span class="cpmw_disabled">Disabled</span>')
                        } else {
                        replaced_text = $(this).text().replace("| 0", '<span class="cpmw_disabled">Disabled</span>')
                        }
                    }
                    else if ($(this).text().indexOf(" | ") != "-1") {
                        if ($(this).text().indexOf("Binance") != "-1" || $(this).text().indexOf("Ethereum Main") != "-1") {
                            replaced_text = $(this).text().replace("| ", '<span class="cpmw_disabled">Disabled</span>')
                        } else {
                        replaced_text = $(this).text().replace(" | ", '<span class="cpmw_disabled">Disabled</span>')
                        }
                    }
                    else {
                        replaced_text = $(this).html()
                    }

                    $(this).html(replaced_text);

              //  }, 100);

            });
            //console.log($this)
        }
    
    let input = document.querySelectorAll('.csf-cloneable-item')

    $(input).on('click change', function () {

        cpmwp_dynamic_title_change(this);
    })
    cpmw_chnage_title();
})
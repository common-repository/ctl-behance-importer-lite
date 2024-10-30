function onCTLBehanceImporterLiteOpenAddWorkDialog(){
    
    s_oCTLBehanceImporterLiteCurSelection = null;
    
     jQuery.post(
        ajaxurl,
        {
            'action': 'ctl-behance-importer-lite',
            'execute' : 'choose-work-dialog'
        }).done(
        function(response){
            tinyMCE.activeEditor.windowManager.open( {
                title : g_oLocalizationShortcodes["Add Work"],
                width : 320,
                height: 200,
                resizable : true,
                maximizable : true                        
            });

            tinymce.activeEditor.windowManager.windows[0]["$el"].find(".mce-window-body").html(response);
            tinymce.activeEditor.windowManager.windows[0]["$el"].find(".mce-foot").remove();
        }
    ).fail( function(){
        alert(g_oLocalizationShortcodes["Ooops! Something Went Wrong!"]);
    });
}

function __onCTLBehanceImporterLiteOpenAddWorkDialog(){
    if (typeof onCTLBehanceImporterLiteOpenAddWorkDialog === "function") { 
        onCTLBehanceImporterLiteOpenAddWorkDialog();                        
    }else{
        alert(g_oLocalizationShortcodes["To Add a Work switch the editor to Visual Mode"]);
    }

}

var s_oCTLBehanceImporterLiteCurSelection = null;

jQuery(window).ready(function(){

    

    jQuery(document).on("mousedown click",".ctl-behance-importer-lite-shortcode-work-list-wrapper li", function () {
        jQuery(".ctl-behance-importer-lite-shortcode-work-list-wrapper li").each(function () {
            jQuery(this).removeClass("ctl-behance-importer-lite-shortcode-work-list-wrapper-li-selected");
        });

        jQuery(this).addClass("ctl-behance-importer-lite-shortcode-work-list-wrapper-li-selected");
        jQuery(".ctl-behance-importer-lite-shortcode-filter input").val(jQuery(this).find("span").text());

        s_oCTLBehanceImporterLiteCurSelection = jQuery(this);
    });

    jQuery(document).on('input',".ctl-behance-importer-lite-shortcode-filter input", function(){
        var szKey = jQuery.trim(jQuery(this).val()).toLowerCase();
        var bFound = false;

        jQuery(".ctl-behance-importer-lite-shortcode-work-list-wrapper li").each(function(){

            jQuery(this).removeClass("ctl-behance-importer-lite-shortcode-work-list-wrapper-li-selected");

            var szName = jQuery.trim(jQuery(this).text()).toLowerCase();
            var szSrcName = jQuery.trim(jQuery(this).text());

            var index = szName.indexOf(szKey);
            if( index >= 0 ){
                var szNewString = szSrcName.substr(0,index) +
                    "<strong>" +  szSrcName.substr(index,szKey.length) + "</strong>" +
                    szSrcName.substr(index+szKey.length, szSrcName.length - (index + szKey.length));
                bFound = true;
                jQuery(this).find("span").html(szNewString);
                jQuery(this).css("display","block");
            }else{
                jQuery(this).css("display","none");
            }
        });

        if( szKey === "" || !bFound ){
            jQuery(".ctl-behance-importer-lite-shortcode-output").val('');
        }else{
            _ctl_behance_importer_lite_write_work_shortcode();
        }
    });

    jQuery(".ctl-behance-importer-lite-shortcode-work-list-wrapper li").each(function(){
        jQuery(this).on("mousedown click",function(){
            
            console.log("click");

            jQuery(".ctl-behance-importer-lite-shortcode-work-list-wrapper li").each(function(){
                jQuery(this).removeClass("ctl-behance-importer-lite-shortcode-work-list-wrapper-li-selected");
            });

            jQuery(this).addClass("ctl-behance-importer-lite-shortcode-work-list-wrapper-li-selected");
            jQuery(".ctl-behance-importer-lite-shortcode-filter input").val( jQuery(this).find("span").text() );
            _ctl_behance_importer_lite_write_work_shortcode(jQuery(this));

        });
    });
});


function _ctl_behance_importer_lite_write_work_shortcode(){
    
    if(!s_oCTLBehanceImporterLiteCurSelection){
        return;
    }
    
    jQuery(".ctl-behance-importer-lite-shortcode-output").attr("data-work-id-behance", s_oCTLBehanceImporterLiteCurSelection.attr
    ("data-work-id-behance") );
    jQuery(".ctl-behance-importer-lite-shortcode-output").val('[ctl_behance_importer id_behance_work="'+
    s_oCTLBehanceImporterLiteCurSelection.attr("data-work-id-behance") +'" mode="work"]');
}

function ctl_behance_importer_lite_shortcode_close(){
    top.tinymce.activeEditor.windowManager.close();
}

function ctl_behance_importer_lite_shortcode_insert_gallery() {

    _ctl_behance_importer_lite_write_gallery_shortcode();

    top.tinymce.activeEditor.insertContent(
            jQuery(".ctl-behance-importer-lite-shortcode-output").val());
    top.tinymce.activeEditor.windowManager.close();
}

function ctl_behance_importer_lite_shortcode_insert_work(){

    _ctl_behance_importer_lite_write_work_shortcode();

    if (jQuery(".ctl-behance-importer-lite-shortcode-output").val() === "") {
        return;
    }

    var idLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationShortcodes["Loading"]);

    jQuery.post(
        ajaxurl,
        {
            'action': 'ctl-behance-importer-lite',
            'execute': 'get-work-html',
            'id_behance' : jQuery(".ctl-behance-importer-lite-shortcode-output").attr("data-work-id-behance")
        }).done( function(response){
            response =
                    '[ctl_behance_importer desc="' + g_oLocalizationShortcodes["DO NOT REMOVE THIS SHORTCODE! WE NEED IT TO LINK THIS POST TO A GALLERY THAT INCLUDE THIS WORK. THIS SHORTCODE WILL NOT APPEAR IN THE POST"] + '" id_behance_work="'+
                        jQuery(".ctl-behance-importer-lite-shortcode-output").attr("data-work-id-behance") +
                      '" mode="ghost_link"]' + response;

            ctlBehanceImporterLiteCloseDlg(idLoadingDlg);
            top.tinymce.activeEditor.execCommand('mceInsertRawHTML',false, response);
            top.tinymce.activeEditor.windowManager.close();
        }
    ).fail( function(){
        ctlBehanceImporterLiteCloseDlg(idLoadingDlg);
        alert(g_oLocalizationShortcodes["Ooops! Something Went Wrong!"]);
    });
}

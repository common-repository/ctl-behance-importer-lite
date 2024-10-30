function ctlBehanceImporterLiteFilter( 
                            szInputField, szRowToHide, 
                            szRowTitle, szCSSShowStyle ){
                                
                            if(!szCSSShowStyle){
                                szCSSShowStyle = "table-row";
                            }
                                
    jQuery(szInputField).on('input', function(){
        var szKey = jQuery.trim(jQuery(this).val()).toLowerCase();

        jQuery(szRowToHide).each(function(){
            var indexTags = -1;
            
            var indexName = "";
            if( szRowToHide === szRowTitle){
                indexName = jQuery(this).text().toLowerCase().indexOf(szKey);
            }else{
               indexName = jQuery(this).find(szRowTitle).text().toLowerCase().indexOf(szKey); 
            }
           
            if( indexTags >= 0 || indexName >= 0){
                jQuery(this).css("display",szCSSShowStyle);
            }else{
                jQuery(this).css("display","none");
            }
        });
    });  
}

function ctlBehanceImporterLiteMakeCode() {
    var code = "";
    var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    for( var i=0; i < 32; i++ )
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    return code;
}

function ctlBehanceImporterLiteGetUrlVar( sParam ){
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
    return null;
}

function ctlBehanceImporterLiteGetUrlVars( urlVars ) {
    urlVars = urlVars.trim();
    var oFinalData = new Array();
    var hashes = urlVars.split('&');
    for (var i = 0; i < hashes.length; i++) {
        var hash = hashes[i].split('=');
        oFinalData[hash[0]] = hash[1];
    }
    return oFinalData;
}

function ctlBehanceImporterLiteRemoveURLParameter(url, parameter) {
    //prefer to use l.search if you have a location/link object
    var urlparts= url.split('?');
    if (urlparts.length>=2) {

        var prefix= encodeURIComponent(parameter)+'=';
        var pars= urlparts[1].split(/[&;]/g);

        //reverse iteration as may be destructive
        for (var i= pars.length; i-- > 0;) {
            //idiom for string.startsWith
            if (pars[i].lastIndexOf(prefix, 0) !== -1) {
                pars.splice(i, 1);
            }
        }

        url= urlparts[0]+'?'+pars.join('&');
        return url;
    } else {
        return url;
    }
}

function ctlBehanceImporterLiteNumberFormat(number, decimals, dec_point, thousands_sep) {

    number = (number + '')
        .replace(/[^0-9+\-Ee.]/g, '');
    var n = !isFinite(+number) ? 0 : +number,
        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
        s = '',
        toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + (Math.round(n * k) / k)
                    .toFixed(prec);
        };
    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
        .split('.');
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
    }
    if ((s[1] || '')
            .length < prec) {
        s[1] = s[1] || '';
        s[1] += new Array(prec - s[1].length + 1)
            .join('0');
    }
    return s.join(dec);
}

function ctlBehanceImporterLiteCloseDlg( idDlg ){
    jQuery('#'+idDlg).remove();

}

function ctlBehanceImporterLiteLoadingSetMessage(id, szMsg) {
    jQuery("#"+id).find("p").text(szMsg);
}

function ctlBehanceImporterLiteShowBlockPanel(){
    var szHtml = "";
    var id = ctlBehanceImporterLiteMakeCode();
    szHtml += "<div id='"+id+"' class='ctl-behance-importer-lite-loading-dlg-wrapper'>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-block'></div>";
    szHtml += "</div>";

    jQuery("body").append(szHtml);
    jQuery("#"+id +" .ctl-behance-importer-lite-dlg-block").addClass("ctl-behance-importer-lite-dlg-block-show");

    return id;
}

function ctlBehanceImporterLiteShowLoading(szMsg, bStop, szStopLabel){
    var szHtml = "";
    var id = ctlBehanceImporterLiteMakeCode();
    szHtml += "<div id='"+id+"' class='ctl-behance-importer-lite-loading-dlg-wrapper'>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-block'></div>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content'>";
    szHtml += "<p>"+szMsg+"</p>";
    szHtml += "<i class='animate-spin ctl-behance-importer-lite-icon-arrows-cw'></i>";

    if( bStop ){
        szHtml += "<div class='ctl-behance-importer-lite-loading-btn-container'>";
            szHtml += "<div class='button-primary ctl-behance-importer-lite-btn-stop'>" +
            szStopLabel + "</div>";
        szHtml += "</div>";
    }

    szHtml += "</div>";
    szHtml += "</div>";

    jQuery("body").append(szHtml);

    jQuery("#"+id +" .ctl-behance-importer-lite-dlg-block").addClass("ctl-behance-importer-lite-dlg-block-show");
    jQuery("#"+id +" .ctl-behance-importer-lite-dlg-content").addClass("ctl-behance-importer-lite-dlg-content-show");

    return id;
}


function ctlBehanceImporterLiteShowWork(szTitle, szContent){
    var szHtml = "";
    var id = ctlBehanceImporterLiteMakeCode();
    szHtml += "<div id='"+id+"' class='ctl-behance-importer-lite-dlg-wrapper ctl-behance-importer-lite-dlg-wrapper-full-content'>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-block ctl-behance-importer-lite-dlg-action-close'></div>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content ctl-behance-importer-lite-dlg-wrapper-work'>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-close ctl-behance-importer-lite-dlg-action-close'><i class='ctl-behance-importer-lite-icon-cancel'></i></div>";    
    szHtml += "<h1 class='ctl-behance-importer-lite-dlg-title'>"+szTitle+"</h1>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content-work'>";
    szHtml += szContent;
    szHtml += "</div>";
    szHtml += "</div>";
    szHtml += "</div>";

    
    jQuery("body,html").addClass("ctl-behance-importer-lite-fixed-body");
    jQuery("body").append(szHtml);

    ctlBehanceImporterResizeWorkDialogFullContent(jQuery("#"+id +" .ctl-behance-importer-lite-dlg-content-work"));
    ctlBehanceImporterShowDialog(id);
     
    return id;
}


function ctlBehanceImporterLiteShowPopupWork(szTitle, szDesc, szCover, aFields, aTags, szColor){
    var szHtml = "";
    var id = ctlBehanceImporterLiteMakeCode();
    szHtml += "<div id='"+id+"' class='ctl-behance-importer-lite-dlg-wrapper ctl-behance-importer-lite-dlg-wrapper-small-content'>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-block ctl-behance-importer-lite-dlg-action-close'></div>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content ctl-behance-importer-lite-dlg-work-popup'>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-close ctl-behance-importer-lite-dlg-action-close'><i class='ctl-behance-importer-lite-icon-cancel'></i></div>";    
    
    szHtml += "<div class='ctl-behance-importer-lite-dlg-small-popup-header'>";
    szHtml += "<h1 class='ctl-behance-importer-lite-dlg-title'>"+szTitle+"</h1>";
    if( aFields.length > 0 ){
        szHtml += "<ul class='ctl-behance-importer-lite-dlg-content-work-fields'>";
        for( var i = 0; i < aFields.length; i++ ){
           szHtml += "<li>"+ aFields[i] +",</li>"; 
        }
        szHtml += "</ul>";  
    }    
    szHtml += "</div>";
    
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content-work'>";
    
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content-left'><img style='border: 1px solid "+ szColor +"' src='"+ szCover +"'/></div>";
    szHtml += "<div class='ctl-behance-importer-lite-dlg-content-right'>";
    szHtml += "<p>"+szDesc+"</p>";

    if( aTags.length > 0 ){
        szHtml += "<ul class='ctl-behance-importer-lite-dlg-content-work-tags'>";
        for( var i = 0; i < aTags.length; i++ ){
           szHtml += "<li style='background-color: "+szColor+"'>"+ aTags[i] +"</li>"; 
        }
        szHtml += "</ul>";  
    }

    szHtml += "</div>";
    szHtml += "<div class='ctl-behance-importer-lite-gallery-clear'></div>";
    
    szHtml += "</div>";
    szHtml += "</div>";
    szHtml += "</div>";

    
    jQuery("body,html").addClass("ctl-behance-importer-lite-fixed-body");
    jQuery("body").append(szHtml);

    ctlBehanceImporterResizeWorkDialogSmallContent(id);
    ctlBehanceImporterShowDialog(id);
     
    return id;
}

function ctlBehanceImporterLiteShowDialog( id ){
    setTimeout(function(){
        jQuery("#"+id +" .ctl-behance-importer-lite-dlg-block").addClass("ctl-behance-importer-lite-dlg-block-show");
    }, 200);
    setTimeout(function(){
        jQuery("#"+id +" .ctl-behance-importer-lite-dlg-content").addClass("ctl-behance-importer-lite-dlg-content-show");
    }, 600);
}

function ctlBehanceImporterLiteResizeWorkDialogFullContent( oNode ){
    
    var idDlg = oNode.parents(".ctl-behance-importer-lite-dlg-wrapper").attr("id");
    var iTitleH = jQuery("#"+ idDlg + " .ctl-behance-importer-lite-dlg-title" ).height();
    var iWrapperWorkH = jQuery("#"+ idDlg + " .ctl-behance-importer-lite-dlg-wrapper-work" ).height();
    
    overlay = document.getElementById(idDlg);
  //  overlay.scrollTop = 0;
    oNode.css( "height", (iWrapperWorkH-iTitleH-15) +  "px");  
}
function ctlBehanceImporterLiteResizeWorkDialogSmallContent( id ){
    jQuery("#"+ id + " .ctl-behance-importer-lite-dlg-content-work" ).css("height", "auto");
    
    var iTitleH = jQuery("#"+ id + " .ctl-behance-importer-lite-dlg-small-popup-header" ).height();
    var iWrapperWorkH = jQuery("#"+ id + " .ctl-behance-importer-lite-dlg-content-work" ).height();
    var iContentH = jQuery("#"+ id + " .ctl-behance-importer-lite-dlg-content" ).height();
    var iWinH = jQuery(window).height();
    
    if( iContentH > iWinH ){
        var iFinalH = (iWinH-iTitleH-30)*0.7;    
        jQuery("#"+ id + " .ctl-behance-importer-lite-dlg-content-work" ).css( "height", iFinalH +  "px");         
    }
}

jQuery(window).resize(function() {
    jQuery(".ctl-behance-importer-lite-dlg-wrapper-full-content" ).each( function(){
        ctlBehanceImporterResizeWorkDialogFullContent(jQuery(this));
    });
    jQuery(".ctl-behance-importer-lite-dlg-wrapper-small-content" ).each( function(){
        ctlBehanceImporterResizeWorkDialogSmallContent(jQuery(this).attr("id"));
    });    
});

jQuery(window).ready(function(){
    jQuery(document).on("click", ".ctl-behance-importer-lite-dlg-action-close",  function(){
        jQuery("body,html").removeClass("ctl-behance-importer-lite-fixed-body");   
        ctlBehanceImporterCloseDlg( 
                    jQuery(this).parents(".ctl-behance-importer-lite-dlg-wrapper").attr("id"));

        });
});



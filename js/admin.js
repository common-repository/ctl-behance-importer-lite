
    jQuery(window).ready(function(){

        jQuery(".ctl-behance-importer-lite-img-guide").each( function(){
            jQuery(this).attr("data-size","300");
            jQuery(this).click(function(){
                if( jQuery(this).attr("data-size") === "300"){
                    jQuery(this).attr("data-size","100");
                    jQuery(this).find("img").addClass("ctl-behance-importer-lite-img-class-100");
                }else{
                    jQuery(this).attr("data-size","300");
                    jQuery(this).find("img").removeClass("ctl-behance-importer-lite-img-class-100");
                }
            });
        });

        ctlBehanceImporterLiteFilter(".works-filter", ".list-works tr", ".work-title");

        jQuery(".ctl-behance-importer-lite-action-delete-work").on("click", function(e) {
            e.preventDefault();
            if (confirm(g_oLocalizationAdmin["Are you sure to delete the work from db? This action isn't reversible!"])) {
                _idCTLBehanceImporterLiteLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationAdmin["Loading"]);
                __ctlBehanceImporterLiteDeleteWork(jQuery(this).parents("tr"));
            }
        });

        jQuery(".ctl-behance-importer-lite-action-import-work").on("click", function(e) {
            e.preventDefault();
            __ctlBehanceImporterLiteCacheWork( jQuery(this).parents("tr"), jQuery(this) );
        });

        jQuery(".ctl-behance-importer-lite-action-import-summary").on("click", function(e){
            e.preventDefault();
            _bCTLBehanceImporterLiteStopImporter = false;
            _idCTLBehanceImporterLiteLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationAdmin["Loading"],true,
                                                          g_oLocalizationAdmin["Stop"]);
            __ctlBehanceImporterLiteInitStopButton();
            __ctlBehanceImporterLiteGetSummary();
        });

        jQuery(".ctl-behance-importer-lite-action-import-works").on("click", function(e){
            e.preventDefault();
            _idCTLBehanceImporterLiteLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationAdmin["Loading"], true, 
                                                          g_oLocalizationAdmin["Stop"]);
            __ctlBehanceImporterLiteInitStopButton();
            __ctlBehanceImporterLiteCacheWorks();
        });

        jQuery(".ctl-behance-importer-lite-action-import-images").on("click", function(e){
            e.preventDefault();
            _idCTLBehanceImporterLiteLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationAdmin["Loading"],true, 
                                                          g_oLocalizationAdmin["Stop"]);
            __ctlBehanceImporterLiteInitStopButton();
            __ctlBehanceImporterLiteCacheImages();
        });

        jQuery(".ctl-behance-importer-lite-action-delete-works").on("click", function(e){
            e.preventDefault();
            if (confirm(g_oLocalizationAdmin["Are you sure to delete all works from db? This action isn't reversible and all galleries will be lost!"])) {
                _idCTLBehanceImporterLiteLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationAdmin["Loading"]);
                __ctlBehanceImporterLiteDeleteWorks();
            }
        });

    });

    var _idCTLBehanceImporterLiteLoadingDlg;
    var _bCTLBehanceImporterLiteStopImporter;

    function __ctlBehanceImporterLiteInitStopButton(){
        jQuery("#"+_idCTLBehanceImporterLiteLoadingDlg + " .ctl-behance-importer-btn-stop").on("click", function(){
            if( jQuery(this).attr("disabled") !== "disabled" ){
                jQuery(this).attr("disabled","disabled");
                _bCTLBehanceImporterLiteStopImporter = true;
            }
        });
    }

    function __ctlBehanceImporterLiteCacheImages(){
        if(_bCTLBehanceImporterLiteStopImporter === true ){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            window.location.reload();
            return;
        }

        var bContinue = false;

        jQuery("#the-list tr").each(function(){
            if( jQuery(this).find(".column-work-state").attr("data-state") === "work_cached"){
                bContinue = true;

                var oNodeTR = jQuery(this);
                ctlBehanceImporterLiteLoadingSetMessage(_idCTLBehanceImporterLiteLoadingDlg, g_oLocalizationAdmin["Caching Images for Work"] +
                    jQuery(this).find(".column-work-title strong").text() );

                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'ctl-behance-importer-lite',
                        'execute':   "import-work-images",
                        'id' : jQuery(this).attr("data-work-id")
                    }).done( function(response){
                        var oData = ctlBehanceImporterLiteGetUrlVars(response);

                        if( oData["res"] === "true" ){
                            oNodeTR.find(".column-work-state").attr("data-state", "full_cached");
                            var szTrafficLightUrl = oNodeTR.find(".column-work-state").find("img").attr("src");
                            szTrafficLightUrl = szTrafficLightUrl.replace("yellow", "green");
                            oNodeTR.find(".column-work-state").find("img").attr("src",szTrafficLightUrl);

                            ctlBehanceImporterLiteLoadingSetMessage(_idCTLBehanceImporterLiteLoadingDlg,
                                g_oLocalizationAdmin["Loading Next Work"] );
                            setTimeout(__ctlBehanceImporterLiteCacheImages, 1000);
                        }else{
                            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                        }
                    }).fail( function(){
                        ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                        alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
                    });

                return false;
            }
        });

        if( bContinue === false ){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            window.location.reload();
        }
    }

    function __ctlBehanceImporterLiteCacheWorkImages( oNodeTR, oNodeActionLink ){
        jQuery.post(
            ajaxurl,
            {
                'action': 'ctl-behance-importer-lite',
                'execute':   "import-work-images",
                'id' : oNodeTR.attr("data-work-id")
            }).done(
            function(response){
                var oData = ctlBehanceImporterLiteGetUrlVars(response);

                if( oData["res"] === "true" ){
                    oNodeTR.find(".column-work-state").attr("data-state", "full_cached");
                    var szTrafficLightUrl = oNodeTR.find(".column-work-state").find("img").attr("src");
                    szTrafficLightUrl = szTrafficLightUrl.replace("yellow", "green");
                    oNodeTR.find(".column-work-state").find("img").attr("src",szTrafficLightUrl);
                    
                    if(oNodeActionLink){
                        oNodeActionLink.remove();
                    }
                    
                    ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                }else{
                    ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                }
            }
        ).fail( function(){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
        });
    }

    function __ctlBehanceImporterLiteCacheWork( oNodeTR, oNodeActionLink ){

        _idCTLBehanceImporterLiteLoadingDlg = ctlBehanceImporterLiteShowLoading(g_oLocalizationAdmin["Loading"]);

        jQuery.post(
            ajaxurl,
            {
                'action': 'ctl-behance-importer-lite',
                'execute':   "import-work-data",
                'id' : oNodeTR.attr("data-work-id")
            }).done(
            function(response){
                var oData = ctlBehanceImporterLiteGetUrlVars(response);

                if( oData["res"] === "true" ){
                    oNodeTR.find(".column-work-state").attr("data-state", "work_cached");
                    var szTrafficLightUrl = oNodeTR.find(".column-work-state").find("img").attr("src");
                    szTrafficLightUrl = szTrafficLightUrl.replace("red", "yellow");
                    oNodeTR.find(".column-work-state").find("img").attr("src",szTrafficLightUrl);

                    __ctlBehanceImporterLiteCacheWorkImages(oNodeTR, oNodeActionLink);
                }else{
                    ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                }
            }
        ).fail( function(){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
        });
    }

    function __ctlBehanceImporterLiteCacheWorks(){

        if(_bCTLBehanceImporterLiteStopImporter === true ){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            window.location.reload();
            return;
        }

        var bContinue = false;

        jQuery("#the-list tr").each(function(){
            if( jQuery(this).find(".column-work-state").attr("data-state") === "summary"){
                var oNodeTR = jQuery(this);

                ctlBehanceImporterLiteLoadingSetMessage(_idCTLBehanceImporterLiteLoadingDlg, g_oLocalizationAdmin["Import"] +
                    " " + jQuery(this).find(".column-work-title strong").text() );

                jQuery.post(
                    ajaxurl,
                    {
                        'action': 'ctl-behance-importer-lite',
                        'execute':   "import-work-data",
                        'id' : jQuery(this).attr("data-work-id")
                    }).done(
                    function(response){
                        var oData = ctlBehanceImporterLiteGetUrlVars(response);

                        if( oData["res"] === "true" ){
                            oNodeTR.find(".column-work-state").attr("data-state", "work_cached");
                            var szTrafficLightUrl = oNodeTR.find(".column-work-state").find("img").attr("src");
                            szTrafficLightUrl = szTrafficLightUrl.replace("red", "yellow");
                            oNodeTR.find(".column-work-state").find("img").attr("src",szTrafficLightUrl);
                        }else{
                            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                        }
                    }
                ).fail( function(){
                    ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                    alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
                });
                bContinue = true;
                return false;
            }
        });

        if( bContinue === true ){
            setTimeout(__ctlBehanceImporterLiteCacheWorks, 10000);
        }else{
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            window.location.reload();
        }
    }

    function __ctlBehanceImporterLiteDeleteWork( oNodeTR ){
        jQuery.post(
            ajaxurl,
            {
                'action': 'ctl-behance-importer-lite',
                'execute':   "delete-work",
                'id' : oNodeTR.attr("data-work-id")
            }).done(
            function(response){
                var oData = ctlBehanceImporterLiteGetUrlVars(response);

                ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);

                if( oData["res"] === "true" ){
                    oNodeTR.remove();
                }else{
                    alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
                }
            }
        ).fail( function(){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
        });
    }

    function __ctlBehanceImporterLiteDeleteWorks(){
        jQuery.post(
            ajaxurl,
            {
                'action': 'ctl-behance-importer-lite',
                'execute':   "delete-works"
            }).done(
            function(response){
                var oData = ctlBehanceImporterLiteGetUrlVars(response);

                ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);

                if( oData["res"] === "true" ){
                    window.location.href = window.location.href + "&msg=works_deleted";
                }else{
                    alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
                }
            }
        ).fail( function(){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
        });
    }

    function __ctlBehanceImporterLiteGetSummary(){


        if(_bCTLBehanceImporterLiteStopImporter === true ){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            window.location.href = ctlBehanceImporterLiteRemoveURLParameter(window.location.href, "msg") + "&msg=some_works_imported_updated";
            return;
        }

        ctlBehanceImporterLiteLoadingSetMessage(_idCTLBehanceImporterLiteLoadingDlg, g_oLocalizationAdmin["Getting summary, page"] + " 1" );

        jQuery.post(
            ajaxurl,
            {
                'action': 'ctl-behance-importer-lite',
                'execute': 'import-works-summary',
                'page' : 1
            }).done(
            function(response){
                var oData = ctlBehanceImporterLiteGetUrlVars(response);

                if( oData["res"] === "true" && parseInt(oData["projects"]) > 0 ){
                    ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
                    window.location.href = ctlBehanceImporterLiteRemoveURLParameter(window.location.href, "msg") + "&msg=works_imported_updated";
                }
            }
        ).fail( function(){
            ctlBehanceImporterLiteCloseDlg(_idCTLBehanceImporterLiteLoadingDlg);
            alert(g_oLocalizationAdmin["Ooops! Something Went Wrong!"]);
        });
    }

    function __ctl_behance_importer_lite_clear_filter(){
        jQuery(".ctl-behance-importer-shortcode-work-list-wrapper li").each(function () {
            jQuery(this).removeClass("ctl-behance-importer-shortcode-work-list-wrapper-li-selected");
            jQuery(this).find("span").html(
                    jQuery.trim(jQuery(this).text()) );
        });
    }           


    function ctlBehanceImporterLiteInsertAtIndex(idNode, i, szContent) {
        if(i < 0){
            i = 0;
        }

        var oNode;
        if(i===0){
            oNode = jQuery(szContent).prependTo(jQuery(idNode));
        }else{
            oNode = jQuery(szContent).insertAfter(jQuery(idNode).children().eq(i-1));
        }
    }

    function ctlBehanceImporterLiteMoveDownAtIndex(idNode, i, szContent) {
        if(i < 0){
            return false;
        }

        var oNode;
        if(i===0){
            oNode = jQuery(szContent).prependTo(jQuery(idNode));
            return true;
        }else if( i < jQuery("#selected-works>tbody").children().length ){
            oNode = jQuery(szContent).insertAfter(jQuery(idNode).children().eq(i));
            return true;
        }
        return false;
    }

    function ctlBehanceImporterLiteMoveUpAtIndex(idNode, i, szContent) {
        if(i < 0){
            return false;
        }

        var oNode;
        if(i===0){
            oNode = jQuery(szContent).prependTo(jQuery(idNode));
            return true;
        }else if( i < jQuery("#selected-works>tbody").children().length ){
            oNode = jQuery(szContent).insertAfter(jQuery(idNode).children().eq(i-1));
            return true;
        }
        return false;
    }    
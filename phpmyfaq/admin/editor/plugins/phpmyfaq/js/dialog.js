tinyMCEPopup.requireLangPack();

var phpmyfaqDialog = {
    init : function() {
        $('#suggestbox').keyup(function() {
            var search = $('#suggestbox').val();
            if (search.length > 0) { 
                $.ajax({ 
                    type:    "POST", 
                    url:     "../../../index.php?action=ajax&ajax=records&ajaxaction=search_records",
                    data:    "search=" + search,
                    success: function(searchresults) 
                    { 
                        $("#suggestions").empty(); 
                        if (searchresults.length > 0)  { 
                            $("#suggestions").append(searchresults);
                        } 
                    }
                });
            }
        });
    },

    insert : function() {
        // build the HTML anchor, add it to editor content and close the popup
        var url    = $('input:radio[name=faqURL]:checked').val();
        var title  = $('input:radio[name=faqURL]:checked').parent().text();
        var anchor = '<a class="intfaqlink" href="' + url + '">' + title + '</a>';
        tinyMCEPopup.editor.execCommand('mceInsertContent', false, anchor);
        tinyMCEPopup.close();
    }
};

tinyMCEPopup.onInit.add(phpmyfaqDialog.init, phpmyfaqDialog);

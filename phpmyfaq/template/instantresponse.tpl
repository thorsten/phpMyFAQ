    <h2>{msgInstantResponse}</h2>

    <p>{msgDescriptionInstantResponse}</p>

    <form id="instantform" action="?action=instantresponse" method="post">
    <input id="ajaxlanguage" name="ajaxlanguage" type="hidden" value="{ajaxlanguage}" />
    <fieldset>
        <legend>{msgSearchWord}</legend>

        <input class="inputfield" id="instantfield" type="text" name="search" value="{searchString}" />

    </fieldset>
    </form>
    <script type="text/javascript">
    //<![CDATA[
        $('#instantfield').keyup(function() {
            
            var search   = $('#instantfield').val();
            var language = $('#ajaxlanguage').val();
            
            if (search.length > 0) { 
                $.ajax({ 
                    type:    "POST", 
                    url:     "ajaxresponse.php", 
                    data:    "search=" + search + "&ajaxlanguage=" + language, 
                    success: function(searchresults) 
                    { 
                        $("#instantresponse").empty(); 
                        if (searchresults.length > 0)  { 
                            $("#instantresponse").append(searchresults).;
                        } 
                    } 
                });
            }
            
        });    
    //]]>
    </script>

    <div id="instantresponse">
    {printInstantResponse}
    </div>
            <h2>{msgInstantResponse}</h2>
            
            <section class="well" id="searchBox">
                <form id="instantform" action="?action=instantresponse" method="post" class="form-search">
                    <input id="instantfield" type="search" name="search" value="{searchString}"
                           class="input-xxlarge search-query" placeholder="{msgDescriptionInstantResponse}"
                           onfocus="autoSuggest(); return false;"/>
                    <input id="ajaxlanguage" name="ajaxlanguage" type="hidden" value="{ajaxlanguage}" />
                </form>
            </section>

            <div id="instantresponse">
            {printInstantResponse}
            </div>
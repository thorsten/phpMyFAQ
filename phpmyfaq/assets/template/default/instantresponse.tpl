            <h2>{msgInstantResponse}</h2>
            
            <section class="well" id="searchBox">
                <form id="instantform" action="?action=instantresponse" method="post" class="form-search" accept-charset="utf-8">
                    <input id="instantfield" type="search" name="search" value="{searchString}"
                           class="input-xlarge search-query" placeholder="{msgDescriptionInstantResponse}"/>
                    <input id="ajaxlanguage" name="ajaxlanguage" type="hidden" value="{ajaxlanguage}" />
                </form>
            </section>

            <div id="instantresponse">
            {printInstantResponse}
            </div>
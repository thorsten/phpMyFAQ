            <h2>{msgInstantResponse}</h2>
            
            <aside id="searchBox">
                <form id="instantform" action="?action=instantresponse" method="post">
                    <input id="instantfield" type="search" name="search" value="{searchString}"
                           placeholder="{msgDescriptionInstantResponse}" onfocus="autoSuggest(); return false;"/>
                    <input id="ajaxlanguage" name="ajaxlanguage" type="hidden" value="{ajaxlanguage}" />
                </form>
                
                <div id="instantresponse">
                {printInstantResponse}
                </div>
            </aside>
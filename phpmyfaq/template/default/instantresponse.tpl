            <h2>{msgInstantResponse}</h2>
            
            <aside id="searchBox">
                <p>{msgDescriptionInstantResponse}</p>
                <form id="instantform" action="?action=instantresponse" method="post">
                    <fieldset>
                    <legend>{msgSearchWord}</legend>
                        <input id="instantfield" type="search" name="search" value="{searchString}" />
                        <input id="ajaxlanguage" name="ajaxlanguage" type="hidden" value="{ajaxlanguage}" />
                    </fieldset>
                </form>
                
                <div id="instantresponse">
                {printInstantResponse}
                </div>
            </aside>
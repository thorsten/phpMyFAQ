            <h2>{msgSearch}</h2>
            
            {printResult}
            
            <aside id="searchBox">
            <form action="{writeSendAdress}" method="get">
                <fieldset>
                <legend>{msgSearchWord}</legend>
                
                <input id="searchfield" type="search" name="search" size="50" value="{searchString}" autofocus="true">
                <input class="submit" type="submit" name="submit" value="{msgSearch}" />
                <input type="hidden" name="action" value="search" /><br />
                
                <label>{searchOnAllLanguages}</label>
                <input type="checkbox"{checkedAllLanguages} name="langs" value="all" />
                
                <label>{selectCategories}</label>
                <select name="searchcategory" size="1">
                <option value="%" selected="selected">{allCategories}</option>
                {printCategoryOptions}
                </select>
                
                <p>{openSearchLink}</p>
                </fieldset>
                
                <div id="mostpopularsearches">
                <p>{msgMostPopularSearches} {printMostPopularSearches}</p>
                </div>
            </form>
            </aside>
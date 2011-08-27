<section>
            <header>
                <h2>{msgAdvancedSearch}</h2>
            </header>
    
            {printResult}
            
            <aside id="searchBox">
            <form action="{writeSendAdress}" method="get">

                <input id="searchfield" type="search" name="search" size="50" value="{searchString}"
                       autofocus="autofocus">
                <input type="submit" name="submit" value="{msgSearch}" />
                <input type="hidden" name="action" value="search" />

                <p>
                    <label>{searchOnAllLanguages}</label>
                    <input type="checkbox"{checkedAllLanguages} name="langs" value="all" />
                </p>

                <p>
                    <label>{selectCategories}</label>
                    <select name="searchcategory" size="1">
                    <option value="%" selected="selected">{allCategories}</option>
                    {printCategoryOptions}
                    </select>
                </p>
                
                <div id="mostpopularsearches">
                    <p><strong>{msgMostPopularSearches}</strong></p>
                    {printMostPopularSearches}
                </div>
                
                <p>{openSearchLink}</p>
            </form>
            </aside>
        </section>
<section>
            <header>
                <h2>{msgAdvancedSearch}</h2>
            </header>
    
            {printResult}
            
            <section class="well" id="searchBox">
            <form action="{writeSendAdress}" method="get" class="form-search">

                <div class="control-group">
                    <div class="input-append">
                        <input id="searchfield" type="search" name="search" value="{searchString}" autofocus
                               class="input-xlarge search-query">
                        <button class="btn btn-primary" type="submit" name="submit">
                            {msgSearch}
                        </button>
                        <input type="hidden" name="action" value="search" />
                    </div>
                    <label class="checkbox inline">
                    <input type="checkbox"{checkedAllLanguages} name="langs" id="langs" value="all" />
                     {searchOnAllLanguages}
                    </label>
                </div>

                <div class="control-group">
                    <label class="control-label">{selectCategories}</label>
                    <div class="controls">
                        <select name="searchcategory" size="1">
                        <option value="%" selected="selected">{allCategories}</option>
                        {printCategoryOptions}
                        </select>
                    </div>
                </div>

                <div class="pull-right">
                    <small>{openSearchLink}</small>
                </div>
            </form>
            </section>
                
                <p id="mostpopularsearches">
                    <h4>{msgMostPopularSearches}</h4>
                    {printMostPopularSearches}
                </p>

        </section>
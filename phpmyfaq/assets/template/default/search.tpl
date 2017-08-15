<section>
            [searchTagsSection]
            <div class="clearfix">
                <ul class="pmf-tags">
                    {searchTags}
                </ul>
            </div>
            [/searchTagsSection]

            [tagListSection]
            <h3>{msgTags}</h3>
            <div class="clearfix">
                <ul class="pmf-tags">
                {tagList}
                </ul>
            </div>
            [/tagListSection]

            [relatedTags]
            <h4>{relatedTagsHeader}</h4>
            <div class="clearfix">
                <ul class="pmf-tags">
                    {relatedTags}
                </ul>
            </div>
            [/relatedTags]

            {printResult}

            [searchBoxSection]
            <section class="well" id="searchBox">
                <form action="{writeSendAdress}" method="get">
                    <input type="hidden" name="action" value="search">

                    <div class="input-group pmf-search-advanced">
                        <input id="searchfield" type="search" name="search" value="{searchString}"
                               class="form-control input-lg" placeholder="{msgSearch}">
                            <span class="input-group-addon">
                                <button type="submit" class="btn btn-lg" aria-label="{msgSearch}">
                                    <span class="fa fa-search"></span>
                                </button>
                            </span>
                    </div>


                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" {checkedAllLanguages} name="langs" id="langs" value="all">
                                {searchOnAllLanguages}
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            {selectCategories}
                        </label>
                        <select class="form-control input-lg" name="searchcategory" size="1">
                            <option value="%" selected="selected">{allCategories}</option>
                            {printCategoryOptions}
                        </select>
                    </div>

                </form>
            </section>
            [/searchBoxSection]

            [popularSearchesSection]
            <h4>{msgMostPopularSearches}</h4>
            <div class="clearfix">
                <ul class="pmf-tags">
                    {printMostPopularSearches}
                </ul>
            </div>
            [/popularSearchesSection]

        </section>
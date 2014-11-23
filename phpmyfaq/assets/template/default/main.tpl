        [tagListSection]
        <div class="well pull-left" style="width:60%; margin-right: 10px;">
            <h3>{msgTags}</h3>
            {tagList}
        </div>
        [/tagListSection]

        <section>
            <header>
                <h2>{writeNewsHeader} {writeNewsRSS}</h2>
            </header>
            <article>
                {writeNews}
            </article>
            <p>{showAllNews}</p>
            <p class="text-center">{writeNumberOfArticles}</p>
        </section>

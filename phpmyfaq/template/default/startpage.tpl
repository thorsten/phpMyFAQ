<section>
            <header>
                <h3>{writeTopTenHeader} <a href="feed/topten/rss.php" target="_blank"><img src="images/feed.png" width="16" height="16" alt="RSS" /></a></h3>
            </header>
            <ol>
                [toptenList]
                <li><a href="{toptenUrl}">{toptenTitle}</a> ({toptenVisits})</li>
                [/toptenList]
                [toptenListError]
                <li>{errorMsgTopTen}</li>
                [/toptenListError]
            </ol>
        </section>

        <section>
            <header>
                <h3>{writeNewestHeader} <a href="feed/latest/rss.php" target="_blank"><img src="images/feed.png" width="16" height="16" alt="RSS" /></a></h3>
            </header>
            <ol>
                [latestEntriesList]
                <li><a href="{latestEntriesUrl}">{latestEntriesTitle}</a> ({latestEntriesDate})</li>
                [/latestEntriesList]
                [latestEntriesListError]
                <li>{errorMsgLatest}</li>
                [/latestEntriesListError]
            </ol>
        </section>
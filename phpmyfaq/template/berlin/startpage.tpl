                <div class="content">
                    <div id="topten">
                    <h3>{writeTopTenHeader}</h3>
                    <ol>
                        [toptenList]
                        <li><a href="{toptenUrl}">{toptenTitle}</a> ({toptenVisits})</li>
                        [/toptenList]
                        [toptenListError]
                        <li>{errorMsgTopTen}</li>
                        [/toptenListError]
                    </ol>
                    </div>
                </div>

                <div class="content">
                    <div id="latest">
                    <h3>{writeNewestHeader}</h3>
                    <ol>
                        [latestEntriesList]
                        <li><a href="{latestEntriesUrl}">{latestEntriesTitle}</a> ({latestEntriesDate})</li>
                        [/latestEntriesList]
                        [latestEntriesListError]
                        <li>{errorMsgLatest}</li>
                        [/latestEntriesListError]
                    </ol>
                    </div>
                </div>
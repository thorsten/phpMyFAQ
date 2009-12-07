                <div class="content">
                    <div id="topten">
                    <h3>{writeTopTenHeader}</h3>
                    <table>
                        [toptenList]
                        <tr>
                            <td><a href="{toptenUrl}">{toptenTitle}</a></td>
                            <td>{toptenVisits}</td>
                        </tr>
                        [/toptenList]
                        [toptenListError]
                        <tr><td colspan="2">{errorMsgTopTen}</td></tr>
                        [/toptenListError]
                    </table>
                    </div>
                </div>

                <div class="content">
                    <div id="latest">
                    <h3>{writeNewestHeader}</h3>
                    <table>
                        [latestEntriesList]
                        <tr>
                            <td>{latestEntriesDate}</td>
                            <td><a href="{latestEntriesUrl}">{latestEntriesTitle}</a></td>
                        </tr>
                        [/latestEntriesList]
                        [latestEntriesListError]
                        <tr><td colspan="2">{errorMsgLatest}</td></tr>
                        [/latestEntriesListError]
                    </table>
                    </div>
                </div>

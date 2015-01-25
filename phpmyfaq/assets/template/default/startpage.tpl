        [socialLinks]
        <section class="well">
            <div id="social">
                <a target="_blank" href="{link_pdf}" rel="nofollow" title="{writePDFTag}">
                    <span class="fa-stack fa-2x">
                        <i class="fa fa-square fa-stack-2x"></i>
                        <i class="fa fa-file-pdf-o fa-stack-1x fa-inverse"></i>
                    </span>
                </a>
                <a href="javascript:window.print();" rel="nofollow" title="{writePrintMsgTag}">
                    <span class="fa-stack fa-2x">
                        <i class="fa fa-square fa-stack-2x"></i>
                        <i class="fa fa-print fa-stack-1x fa-inverse"></i>
                    </span>
                </a>
                <a href="{link_email}" title="{writeSend2FriendMsgTag}">
                    <i class="fa fa-envelope-square fa-4x"></i>
                </a>
                {shareOnFacebook}
                {shareOnTwitter}
            </div>
            <div id="facebookLikeButton">
                {facebookLikeButton}
            </div>
        </section>
        [/socialLinks]
        <section class="well">
            <header>
                <h3>{writeTopTenHeader} <a href="feed/topten/rss.php" target="_blank"><i class="fa fa-rss"></i></a></h3>
            </header>
            <ol>
                [toptenList]
                <li><a class="topten" data-toggle="tooltip" data-placement="top" title="{toptenPreview}" href="{toptenUrl}">{toptenTitle}</a> <small>({toptenVisits})</small></li>
                [/toptenList]
                [toptenListError]
                <li>{errorMsgTopTen}</li>
                [/toptenListError]
            </ol>
        </section>

        <section class="well">
            <header>
                <h3>{writeNewestHeader} <a href="feed/latest/rss.php" target="_blank"><i class="fa fa-rss"></i></a></h3>
            </header>
            <ol>
                [latestEntriesList]
                <li><a class="latest-entries" data-toggle="tooltip" data-placement="top" title="{latestEntriesPreview}" href="{latestEntriesUrl}">{latestEntriesTitle}</a> <small>({latestEntriesDate})</small></li>
                [/latestEntriesList]
                [latestEntriesListError]
                <li>{errorMsgLatest}</li>
                [/latestEntriesListError]
            </ol>
        </section>
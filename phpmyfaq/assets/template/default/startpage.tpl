        [socialLinks]
        <section class="well">
            <div id="social">
                {shareOnFacebook}
                {shareOnTwitter}
                {shareOnDigg}
                <a href="{link_email}">
                    <img src="assets/img/email.png" alt="{writeSend2FriendMsgTag}" title="{writeSend2FriendMsgTag}" width="32" height="32" >
                </a>
                <a target="_blank" href="{link_pdf}">
                    <img src="assets/img/pdf.png" alt="{writePDFTag}" title="{writePDFTag}" width="32" height="32" >
                </a>
                <a href="javascript:window.print();">
                    <img src="assets/img/print.png" alt="{writePrintMsgTag}" title="{writePrintMsgTag}" width="32" height="32" >
                </a>
            </div>
            <div id="facebookLikeButton">
                {facebookLikeButton}
            </div>
        </section>
        [/socialLinks]
        <section class="well">
            <header>
                <h3>{writeTopTenHeader} <a href="feed/topten/rss.php" target="_blank"><img src="assets/img/feed.png" width="16" height="16" alt="RSS" /></a></h3>
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
                <h3>{writeNewestHeader} <a href="feed/latest/rss.php" target="_blank"><img src="assets/img/feed.png" width="16" height="16" alt="RSS" /></a></h3>
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
        [socialLinks]

        <section class="pmf-aside-widget">
            <div class="pmf-aside-widget-body pmf-social-links">
                <a target="_blank" href="{link_pdf}" rel="nofollow" title="{writePDFTag}">
                    <span class="fa-stack fa-lg">
                        <i aria-hidden="true" class="fa fa-square-o fa-stack-2x"></i>
                        <i aria-hidden="true" class="fa fa-file-pdf-o fa-stack-1x"></i>
                    </span>
                </a>
                <a href="javascript:window.print();" rel="nofollow" title="{writePrintMsgTag}">
                    <span class="fa-stack fa-lg">
                        <i aria-hidden="true" class="fa fa-square-o fa-stack-2x"></i>
                        <i aria-hidden="true" class="fa fa-print fa-stack-1x"></i>
                    </span>
                </a>
                <a href="{link_email}" title="{writeSend2FriendMsgTag}">
                    <span class="fa-stack fa-lg">
                        <i aria-hidden="true" class="fa fa-square-o fa-stack-2x"></i>
                        <i aria-hidden="true" class="fa fa-envelope fa-stack-1x"></i>
                    </span>
                </a>
                {shareOnFacebook}
                {shareOnTwitter}
                {facebookLikeButton}
            </div>
        </section>
        [/socialLinks]


        <section class="pmf-aside-widget">
            <header>
                <h3>{writeTopTenHeader} {rssFeedTopTen}</h3>
            </header>
            <div class="pmf-aside-widget-body">
                <ol class="tpmf-list">
                    [toptenList]
                    <li><a class="topten" data-toggle="tooltip" data-placement="top" title="{toptenPreview}" href="{toptenUrl}">{toptenTitle}</a> <small>({toptenVisits})</small></li>
                    [/toptenList]
                    [toptenListError]
                    <li>{errorMsgTopTen}</li>
                    [/toptenListError]
                </ol>
            </div>
        </section>

        <section class="pmf-aside-widget">
            <header>
                <h3>{writeNewestHeader} {rssFeedLatest}</h3>
            </header>
            <div class="pmf-aside-widget-body">
                <ol class="pmf-list">
                    [latestEntriesList]
                    <li><a class="latest-entries" data-toggle="tooltip" data-placement="top" title="{latestEntriesPreview}" href="{latestEntriesUrl}">{latestEntriesTitle}</a> <small>({latestEntriesDate})</small></li>
                    [/latestEntriesList]
                    [latestEntriesListError]
                    <li>{errorMsgLatest}</li>
                    [/latestEntriesListError]
                </ol>
            </div>
        </section>

        [socialLinks]
        <section class="well">
            <div id="social">
                {shareOnFacebook}
                {shareOnTwitter}
                <a href="{link_email}">
                    <i class="fa fa-envelope-square fa-4x"></i>
                </a>
                <a target="_blank" href="{link_pdf}" rel="nofollow">
                    <span class="fa-stack fa-2x">
                        <i class="fa fa-square fa-stack-2x"></i>
                        <i class="fa fa-file-pdf-o fa-stack-1x fa-inverse"></i>
                    </span>
                </a>
                <a href="javascript:window.print();" rel="nofollow">
                    <span class="fa-stack fa-2x">
                        <i class="fa fa-square fa-stack-2x"></i>
                        <i class="fa fa-print fa-stack-1x fa-inverse"></i>
                    </span>
                </a>
            </div>
            <div id="facebookLikeButton">
                {facebookLikeButton}
            </div>
        </section>
        [/socialLinks]
        <section class="well">
            <header>
                <h3>{msgAllCatArticles}</h3>
            </header>
            <div id="allCategoryArticles-content">
            {allCatArticles}
            </div>
        </section>
        <section class="well">
            <header>
                <h3>{writeTagCloudHeader}</h3>
            </header>
            <div id="tagcloud-content">
            {writeTags}
            </div>
        </section>

            <div id="breadcrumbs">
                {writeRubrik}
            </div>
            
            <header>
                <div id="solution_id">
                    <a href="{solution_id_link}">
                        ID #{solution_id}
                    </a>
                </div>
                <h2>{writeThema}</h2>
            </header>
            
            <article id="answer">
            {writeContent}
            </article>
            <script>
                $(function(){
                    $('a[rel="tooltip"]').tooltip();
                });
            </script>

            <div id="faqAccordion" class="accordion">
                <div class="accordion-group">
                    <div class="accordion-heading">
                        <a class="accordion-toggle" href="#collapseOne" data-parent="#faqAccordion" data-toggle="collapse">Categories</a>
                    </div>
                    <div id="collapseOne" class="accordion-body collapse in">
                        <div class="accordion-inner">{writeArticleCategories}</div>
                    </div>
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" href="#collapseTwo" data-parent="#faqAccordion" data-toggle="collapse">{writeTagHeader}</a>
                        </div>
                        <div id="collapseTwo" class="accordion-body collapse">
                            <div class="accordion-inner">{writeArticleTags}</div>
                        </div>
                    </div>
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" href="#collapseThree" data-parent="#faqAccordion" data-toggle="collapse">{writeRelatedArticlesHeader}</a>
                        </div>
                        <div id="collapseThree" class="accordion-body collapse">
                            <div class="accordion-inner">{writeRelatedArticles}</div>
                        </div>
                    </div>
                </div>
            </div>

            <ul id="tab" class="nav nav-tabs">
                <li><a href="#authorInfo" data-toggle="tab">{msg_about_faq}</a></li>
                <li><a href="#votingForm" data-toggle="tab">{msgVoteUseability}</a></li>
                [switchLanguage]
                <li><a href="#switchAvailableLanguage" data-toggle="tab">{msgChangeLanguage}</a></li>
                [/switchLanguage]
                [addTranslation]
                <li><a href="#addTranslation" data-toggle="tab">{msgTranslate}</a></li>
                [/addTranslation]
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="authorInfo">
                    {writeDateMsg}<br />{writeAuthor}<br />{writeRevision}<br />{editThisEntry}
                </div>
                <div class="tab-pane" id="votingForm">
                    <form action="#" method="post" class="form-inline">
                        <input type="hidden" name="artikel" value="{saveVotingID}" />
                        <div id="votings"></div>
                        <div class="star-rating"><s><s><s><s><s></s></s></s></s></s></div>
                        <p><strong>{msgAverageVote}</strong><span id="rating">{printVotings}</span></p>
                    </form>
                </div>
                <div class="tab-pane" id="switchAvailableLanguage">
                    {switchLanguage}
                </div>
                <div class="tab-pane" id="addTranslation">
                    <form action="{translationUrl}" method="post">
                        {languageSelection}
                            <button class="btn btn-primary" type="submit" name="submit">
                                {msgTranslateSubmit}
                            </button>
                    </form>
                </div>
            </div>

            <p>{writeCommentMsg}</p>

            <!-- Comment Form -->
            <a name="comment"></a>
            <div id="commentForm" style="display: none;">
                <form id="formValues" action="#" method="post" class="form-horizontal">
                    <input type="hidden" name="id" id="id" value="{id}" />
                    <input type="hidden" name="lang" id="lang" value="{lang}" />
                    <input type="hidden" name="type" id="type" value="faq" />

                    <div class="control-group">
                        <label class="control-label" for="user">{msgNewContentName}</label>
                        <div class="controls">
                            <input type="text" id="user" name="user" value="{defaultContentName}" required />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="mail">{msgNewContentMail}</label>
                        <div class="controls">
                            <input type="email" id="mail" name="mail" value="{defaultContentMail}" required />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="comment_text">{msgYourComment}</label>
                        <div class="controls">
                            <textarea id="comment_text" name="comment_text" required></textarea>
                        </div>
                    </div>

                    {captchaFieldset}

                    <div class="form-actions">
                        <button class="btn btn-primary" id="submitcomment" type="submit">
                            {msgNewContentSubmit}
                        </button>
                    </div>
                </form>
            </div>
            <!-- /Comment Form -->

            <div id="loader"></div>
            <div id="comments">
                {writeComments}
            </div>

            <script>
                $(function() {
                    $("div.star-rating > s, div.star-rating-rtl > s").on("click", function(e) {
                        var numStars = $(e.target).parentsUntil("div").length + 1;
                        saveVoting('faq', {id}, numStars);
                    });
                });

                $('form#formValues').on('submit', function (e) {
                    e.preventDefault();
                    saveFormValues('savecomment', 'comment');
                    return false;
                });

            </script>
            <script src="assets/js/syntaxhighlighter/scripts/shCore.js"></script>
            <script src="assets/js/syntaxhighlighter/scripts/shAutoloader.js"></script>
            <script>
            SyntaxHighlighter.autoloader(
                'js jscript javascript  assets/js/syntaxhighlighter/scripts/shBrushJScript.js',
                'applescript            assets/js/syntaxhighlighter/scripts/shBrushAppleScript.js',
                'xml xhtml xslt html    assets/js/syntaxhighlighter/scripts/shBrushXml.js',
                'bash shell             assets/js/syntaxhighlighter/scripts/shBrushBash.js',
                'php                    assets/js/syntaxhighlighter/scripts/shBrushPhp.js',
                'sql                    assets/js/syntaxhighlighter/scripts/shBrushSql.js'
            );
            SyntaxHighlighter.all();
            </script>
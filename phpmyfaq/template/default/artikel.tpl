            <div id="breadcrumbs">
                {writeRubrik}
            </div>
            
            <header>
                <div id="solution_id">ID #{solution_id}</div>
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
                    <form action="#" method="post" style="display: inline;">
                    <fieldset>
                        <legend>{msgVoteUseability}</legend>
                        <input type="hidden" name="artikel" value="{saveVotingID}" />
                        <div id="votings"></div>
                        <div id="votingstars">
                            <input class="voting" type="radio" name="vote" value="1" />
                            <input class="voting" type="radio" name="vote" value="2" />
                            <input class="voting" type="radio" name="vote" value="3" />
                            <input class="voting" type="radio" name="vote" value="4" />
                            <input class="voting" type="radio" name="vote" value="5" />
                            <span><strong>{msgAverageVote}</strong><span id="rating">{printVotings}</span></span>
                        </div>
                    </fieldset>
                    </form>
                </div>
                <div class="tab-pane" id="switchAvailableLanguage">
                    {switchLanguage}
                </div>
                <div class="tab-pane" id="addTranslation">
                    <form action="{translationUrl}" method="post">
                        {languageSelection}
                        <input type="submit" name="submit" value="{msgTranslateSubmit}" />
                    </form>
                </div>
            </div>

            <p>{writeCommentMsg}</p>

            <!-- Comment Form -->
            <a name="comment"></a>
            <div id="commentForm" style="display: none;">
                <form id="formValues" action="#" method="post">
                    <input type="hidden" name="id" id="id" value="{id}" />
                    <input type="hidden" name="lang" id="lang" value="{lang}" />
                    <input type="hidden" name="type" id="type" value="faq" />

                    <p>
                        <label for="user">{msgNewContentName}</label>
                        <input type="text" id="user" name="user" value="{defaultContentName}" size="50" required="required" />
                    </p>

                    <p>
                        <label for="mail">{msgNewContentMail}</label>
                        <input type="email" id="mail" name="mail" value="{defaultContentMail}" size="50" required="required" />
                    </p>

                    <p>
                        <label for="comment_text">{msgYourComment}</label>
                        <textarea cols="37" rows="10" id="comment_text" name="comment_text" required="required" /></textarea>
                    </p>

                    <p>
                        {captchaFieldset}
                    </p>

                    <p>
                        <input class="submit" id="submitcomment" type="submit" value="{msgNewContentSubmit}" />
                    </p>
                </form>
            </div>
            <!-- /Comment Form -->

            <div id="loader"></div>
            <div id="comments">
                {writeComments}
            </div>

            <script src="js/plugins/rating/jquery.rating.pack.js"></script>
            <script>
            $('.voting').rating({
                callback: function(value, link){
                    saveVoting('faq', {id}, value);
                }
            });
            $(function() {
                $('#submitcomment').click(function() {
                    saveFormValues('savecomment', 'comment');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>
            <script src="js/syntaxhighlighter/scripts/shCore.js"></script>
            <script src="js/syntaxhighlighter/scripts/shAutoloader.js"></script>
            <script>
            SyntaxHighlighter.autoloader(
                'js jscript javascript  inc/js/syntaxhighlighter/scripts/shBrushJScript.js',
                'applescript            inc/js/syntaxhighlighter/scripts/shBrushAppleScript.js',
                'xml xhtml xslt html    inc/js/syntaxhighlighter/scripts/shBrushXml.js',
                'bash shell             inc/js/syntaxhighlighter/scripts/shBrushBash.js',
                'php                    inc/js/syntaxhighlighter/scripts/shBrushPhp.js',
                'sql                    inc/js/syntaxhighlighter/scripts/shBrushSql.js'
            );
            SyntaxHighlighter.all();
            </script>
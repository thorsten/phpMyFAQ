            <div id="breadcrumbs">
                {writeRubrik}
            </div>
            
            <header>
                <div id="solution_id">ID #{solution_id}</div>
                <h2>{writeThema}</h2>
            </header>
            
            <article>
            {writeContent}
            </article>
            
            <!-- Article Categories Listing -->
            {writeArticleCategories}
            
            <!-- Tags -->
            <p><strong>{writeTagHeader}</strong> {writeArticleTags}</p>

            <!-- Related Articles -->
            <p><strong>{writeRelatedArticlesHeader}</strong>{writeRelatedArticles}</p>

            <div id="faqTabs">
                <ul class="faqTabNav">
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('authorInfo')">
                            {msg_about_faq}
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('votingForm')">
                            {msgVoteUseability}
                        </a>
                    </li>
                    [switchLanguage]
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('switchAvailableLanguage')">
                            {msgChangeLanguage}
                        </a>
                    </li>
                    [/switchLanguage]
                    [addTranslation]
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('addTranslation')">
                            {msgTranslate}
                        </a>
                    </li>
                    [/addTranslation]
                </ul>
                <div class="faqTabContent" id="authorInfo" style="display: none;">
                    {writeDateMsg}<br />{writeAuthor}<br />{writeRevision}<br />{editThisEntry}
                </div>
                <div class="faqTabContent" id="votingForm" style="display: none;">
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
                <div class="faqTabContent" id="switchAvailableLanguage" style="display: none;">
                    {switchLanguage}
                </div>
                <div class="faqTabContent" id="addTranslation" style="display: none;">
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

            <script src="inc/js/plugins/rating/jquery.rating.pack.js"></script>
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
            <script src="inc/js/syntaxhighlighter/scripts/shCore.js"></script>
            <script src="inc/js/syntaxhighlighter/scripts/shAutoloader.js"></script>
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
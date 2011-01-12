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
                            About this FAQ
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('votingForm')">
                            Rate this FAQ
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('switchAvailableLanguage')">
                            Change language
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('addTranslation')">
                            Translate this FAQ
                        </a>
                    </li>
                </ul>
                <div class="faqTabContent" id="authorInfo" style="display: none;">
                    {writeDateMsg}<br />{writeAuthor}<br />{writeRevision}<br />{editThisEntry}
                </div>
                <div class="faqTabContent" id="votingForm" style="display: none;">
                    <form action="#" method="post" style="display: inline;">
                    <fieldset>
                        <legend>{msgVoteUseability}</legend>
                        <input type="hidden" name="artikel" value="{saveVotingID}" />

                        <p align="center"><strong>{msgAverageVote}</strong> {printVotings}</p>
                        <div id="votings"></div>
                        <p align="center">
                            <div class="votingStars">
                                <label><input class="radio" id="voting-1" type="radio" name="vote" value="1" /> 1</label>
                                <label><input class="radio" id="voting-2" type="radio" name="vote" value="1.5" /> 1.5</label>
                                <label><input class="radio" id="voting-3" type="radio" name="vote" value="2" /> 2</label>
                                <label><input class="radio" id="voting-4" type="radio" name="vote" value="2.5" /> 2.5</label>
                                <label><input class="radio" id="voting-5" type="radio" name="vote" value="3" /> 3</label>
                                <label><input class="radio" id="voting-6" type="radio" name="vote" value="3.5" /> 3.5</label>
                                <label><input class="radio" id="voting-7" type="radio" name="vote" value="4" /> 4</label>
                                <label><input class="radio" id="voting-8" type="radio" name="vote" value="4.5" /> 4.5</label>
                                <label><input class="radio" id="voting-9" type="radio" name="vote" value="5" /> 5</label>
                            </div>
                            <input class="submit" id="submitvoting" type="submit" name="submit" value="{msgVoteSubmit}" />
                        </p>
                    </fieldset>
                    </form>
                </div>
                <div class="faqTabContent" id="switchAvailableLanguage" style="display: none;">
                    {switchLanguage}
                </div>
                <div class="faqTabContent" id="addTranslation" style="display: none;">
                    {msgTranslate}
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

            <script type="text/javascript" >
            $(function() {
                $('#submitcomment').click(function() {
                    saveFormValues('savecomment', 'comment');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shCore.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushBash.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushCpp.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushCSharp.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushCss.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushDelphi.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushDiff.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushGroovy.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushJava.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushJScript.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushPerl.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushPhp.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushPlain.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushPython.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushRuby.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushScala.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushSql.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushVb.js"></script>
            <script type="text/javascript" src="inc/js/syntaxhighlighter/scripts/shBrushXml.js"></script>
            <link type="text/css" rel="stylesheet" href="inc/js/syntaxhighlighter/styles/shCore.css"/>
            <link type="text/css" rel="stylesheet" href="inc/js/syntaxhighlighter/styles/shThemeDefault.css"/>
            <script type="text/javascript">
                SyntaxHighlighter.config.clipboardSwf = 'inc/js/syntaxhighlighter/scripts/clipboard.swf';
                SyntaxHighlighter.all();
            </script>
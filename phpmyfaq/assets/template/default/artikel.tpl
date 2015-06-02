            <div id="breadcrumbs" class="hidden-print">
                {writeRubrik}
            </div>

            <header>
                <div class="pull-right hidden-print" id="solution_id">
                    <a class="label label-info" href="{solution_id_link}">
                        ID #{solution_id}
                    </a>
                </div>
                <h2>{writeThema}</h2>
            </header>

            <hr>

            <article class="answer">
                {writeContent}
                [tagsAvailable]
                {renderTags}
                [/tagsAvailable]
                [relatedFaqs]
                <aside id="faqAccordion" class="accordion related-faqs hidden-print">
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" href="#collapseRelatedFaqs" data-parent="#faqAccordion" data-toggle="collapse">
                                {renderRelatedArticlesHeader}
                            </a>
                        </div>
                        <div id="collapseRelatedFaqs" class="accordion-body collapse">
                            <div class="accordion-inner">{renderRelatedArticles}</div>
                        </div>
                    </div>
                </aside>
                [/relatedFaqs]
                [relatedCategories]
                <aside id="faqAccordion" class="accordion related-categories hidden-print">
                    <div class="accordion-group">
                        <div class="accordion-heading">
                            <a class="accordion-toggle" href="#collapseRelatedCategories" data-parent="#faqAccordion" data-toggle="collapse">
                                {renderRelatedCategoriesHeader}
                            </a>
                        </div>
                        <div id="collapseRelatedCategories" class="accordion-body collapse">
                            <div class="accordion-inner">{renderRelatedCategories}</div>
                        </div>
                    </div>
                </aside>
                [/relatedCategories]
            </article>

            <script>
                $(function(){
                    $('abbr[rel="tooltip"]').tooltip();
                });
            </script>

            <ul id="tab" class="nav nav-tabs hidden-print">
                <li class="active"><a href="#authorInfo" data-toggle="tab">{msg_about_faq}</a></li>
                <li><a href="#votingForm" data-toggle="tab">{msgVoteUseability}</a></li>
                [switchLanguage]
                <li><a href="#switchAvailableLanguage" data-toggle="tab">{msgChangeLanguage}</a></li>
                [/switchLanguage]
                [addTranslation]
                <li><a href="#addTranslation" data-toggle="tab">{msgTranslate}</a></li>
                [/addTranslation]
                <li>{editThisEntry}</li>
            </ul>

            <div class="tab-content faq-information">
                <div class="tab-pane active" id="authorInfo">
                    <dl class="dl-horizontal">
                    {writeDateMsg}
                    {writeAuthor}
                    {writeRevision}
                    </dl>
                </div>
                <div class="tab-pane hidden-print" id="votingForm">
                    <form action="#" method="post" class="form-inline" accept-charset="utf-8">
                        <input type="hidden" name="artikel" value="{saveVotingID}" />
                        <div id="votings"></div>
                        <div class="star-rating">
                            <span data-stars="5">☆</span>
                            <span data-stars="4">☆</span>
                            <span data-stars="3">☆</span>
                            <span data-stars="2">☆</span>
                            <span data-stars="1">☆</span>
                        </div>
                        <div class="pull-right">
                            <strong>{msgAverageVote}</strong><span id="rating">{printVotings}</span>
                        </div>
                    </form>
                </div>
                <div class="tab-pane hidden-print" id="switchAvailableLanguage">
                    {switchLanguage}
                </div>
                <div class="tab-pane hidden-print" id="addTranslation">
                    <form action="{translationUrl}" method="post" class="form-inline" accept-charset="utf-8">
                        {languageSelection}
                        <button class="btn btn-primary" type="submit" name="submit">
                            {msgTranslateSubmit}
                        </button>
                    </form>
                </div>
            </div>

            <p class="hidden-print">{writeCommentMsg}</p>

            <a id="comment"></a>
            <div id="commentForm" class="hide">
                <form id="formValues" action="#" method="post" class="form-horizontal" accept-charset="utf-8">
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

            <div id="loader"></div>
            <div id="comments">
                {writeComments}
            </div>

            <script>
                $(function() {
                    $("div.star-rating > span").on("click", function(e) {
                        var numStars = $(e.target).data("stars");
                        saveVoting('faq', {id}, numStars);
                    });
                });

                $('form#formValues').on('submit', function (e) {
                    e.preventDefault();
                    saveFormValues('savecomment', 'comment');
                    return false;
                });

            </script>
            <style scoped>
                @import "{baseHref}assets/js/syntaxhighlighter/styles/shCore.css";
                @import "{baseHref}assets/js/syntaxhighlighter/styles/shThemeDefault.css";
            </style>
            <script src="{baseHref}assets/js/syntaxhighlighter/scripts/shCore.js"></script>
            <script src="{baseHref}assets/js/syntaxhighlighter/scripts/shAutoloader.js"></script>
            <script>
            SyntaxHighlighter.autoloader(
                'js jscript javascript  assets/js/syntaxhighlighter/scripts/shBrushJScript.js',
                'applescript            assets/js/syntaxhighlighter/scripts/shBrushAppleScript.js',
                'xml xhtml xslt html    assets/js/syntaxhighlighter/scripts/shBrushXml.js',
                'bash shell             assets/js/syntaxhighlighter/scripts/shBrushBash.js',
                'php                    assets/js/syntaxhighlighter/scripts/shBrushPhp.js',
                'sql                    assets/js/syntaxhighlighter/scripts/shBrushSql.js',
                'java                   assets/js/syntaxhighlighter/scripts/shBrushJava.js',     
                'ruby                   assets/js/syntaxhighlighter/scripts/shBrushRuby.js', 
                'css                    assets/js/syntaxhighlighter/scripts/shBrushCss.js',     
                'perl                   assets/js/syntaxhighlighter/scripts/shBrushPerl.js',
                'python                   assets/js/syntaxhighlighter/scripts/shBrushPython.js'          
            );
            SyntaxHighlighter.all();
            </script>

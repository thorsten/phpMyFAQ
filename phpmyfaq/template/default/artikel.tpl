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
            <!-- /Article Categories Listing -->
            
            <!-- Tags -->
            <p><strong>{writeTagHeader}</strong> {writeArticleTags}</p>
            <!-- /Tags -->

            <!-- Related Articles -->
            <p><strong>{writeRelatedArticlesHeader}</strong>{writeRelatedArticles}</p>
            <!-- / Related Articles -->

            <!-- Article Info -->
            <p>{writeDateMsg}<br />{writeAuthor}<br />{writeRevision}<br />{editThisEntry}</p>
            <!-- /Article Info -->

            {switchLanguage}
            
            <!-- Translation Form -->
            <div class="translation">
            {translationForm}
            </div>
            <!-- /Translation Form -->

            <!-- Voting Form -->
            <div id="voting">
                <form action="{saveVotingPATH}" method="post" style="display: inline;">
                <fieldset>
                    <legend>{msgVoteUseability}</legend>
                    <input type="hidden" name="artikel" value="{saveVotingID}" />

                    <p align="center"><strong>{msgAverageVote}</strong> {printVotings}</p>
                    <p align="center">{msgVoteBad}
                    <input name="vote" type="range" min="0" max="5" step="1" value="5">
                    {msgVoteGood}<br />
                    <input class="submit voting" type="submit" name="submit" value="{msgVoteSubmit}" />
                    </p>
                </fieldset>
                </form>
            </div>
            <!-- /Voting Form -->
        
            <p>{writeCommentMsg}</p>

            <!-- Comment Form -->
            <a name="comment"></a>
            <div id="commentForm" style="display: none;">
                <form action="#" method="post">
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
                        <input class="submit submitcomment" type="submit" value="{msgNewContentSubmit}" />
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
                $('.submitcomment').click(function() {
                    var id      = $("#id").val();
                    var lang    = $("#lang").val();
                    var type    = $("#type").val();
                    var user    = $("#user").val();
                    var email   = $("#mail").val();
                    var comment = $("#comment_text").val();

                    var dataString = 'user='+ user + '&email=' + email + '&comment=' + comment +
                                     '&id=' + id + '&lang=' + lang + '&type=' + type;


                    if (user == '' || email == '' || comment == '') {
                        alert('please add something more');
                    } else {
                        $("#loader").show();
                        $("#loader").fadeIn(400).html('<img src="images/ajax-loader.gif" />Loading Comment...');

                        $.ajax({
                            type:     'post',
                            url:      'ajaxservice.php?action=savecomment',
                            data:     dataString,
                            dataType: 'json',
                            cache:   false,
                            success: function(json) {
                                // @todo add missing check on json.error and json.success
                                $("#comments").html('<p>' + json.success + '</p>');
                                $("#comments").fadeIn("slow");
                                $("#loader").hide();
                                // @todo add reload of #comments
                            }
                        });
                    }

                    return false;
                });
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

            <header>
                <h2 id="article_category">{writeRubrik}</h2>
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
            
            <div id="action">
            <a href="{link_digg}" target="_blank"><img src="images/digg.png" alt="{writeDiggMsgTag}" title="{writeDiggMsgTag}" width="16" height="16" border="0" class="recordIcons" /></a> 
            <a href="{link_facebook}" target="_blank"><img src="images/facebook.png" alt="{writeFacebookMsgTag}" title="{writeFacebookMsgTag}" width="16" height="16" border="0" class="recordIcons" /></a> 
            <a href="javascript:window.print();"><img src="images/print.gif" alt="{writePrintMsgTag}" title="{writePrintMsgTag}" width="16" height="16" border="0" class="recordIcons" /></a> 
            <a href="{link_email}"><img src="images/email.gif" alt="{writeSend2FriendMsgTag}" title="{writeSend2FriendMsgTag}" width="16" height="16" border="0" class="recordIcons" /></a> 
            <a target="_blank" href="{link_pdf}"><img src="images/pdf.gif" alt="{writePDFTag}" title="{writePDFTag}" width="16" height="16" border="0" class="recordIcons" /></a>
            {facebookeLikeButton}
            </div>

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
    <input class="submit" type="submit" name="submit" value="{msgVoteSubmit}" />
    </p>
    </fieldset>
    </form>
    </div>
    <!-- /Voting Form -->

    <p>{writeCommentMsg}</p>

    <!-- Comment Form -->
    <div id="comment" style="display: none;"><a name="comment"></a>
    <form action="{writeSendAdress}" method="post">
    <input type="hidden" name="id" value="{id}" />
    <input type="hidden" name="lang" value="{lang}" />
    <input type="hidden" name="type" value="faq" />

    <fieldset>
    <legend>{msgWriteComment}</legend>

        <label for="user">{msgNewContentName}</label>
        <input type="text" id="user" name="user" value="{defaultContentName}" size="50" required="true"><br />

        <label for="mail">{msgNewContentMail}</label>
        <input type="email" id="mail" name="mail" value="{defaultContentMail}" size="50" required="true"><br />

        <label for="comment_text">{msgYourComment}</label>
        <textarea cols="37" rows="10" id="comment_text" name="comment" required="true"></textarea><br />

    </fieldset>

    {captchaFieldset}

    <div style="text-align: center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>
    <br />

    </form>
    </div>
    <!-- /Comment Form -->

    {writeComments}

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
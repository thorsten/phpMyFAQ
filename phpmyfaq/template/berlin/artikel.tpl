<h2 id="article_category">{writeRubrik}</h2>
<div id="solution_id">ID #{solution_id}</div>
<h2>{writeThema}</h2>

    <!-- Article -->
    <div id="article_content">{writeContent}</div>
    <!-- /Article -->

    <!-- Article Categories Listing -->
    {writeArticleCategories}
    <!-- /Article Categories Listing -->

    <!-- Article Info -->
    <p><span id="popularity" style="display: none;">{writePopularity}</span>{writeDateMsg}<br />{writeAuthor}<br />{writeRevision}<br />{editThisEntry}</p>
    <!-- /Article Info -->

    <p>
    <a href="javascript:window.print();"><img src="images/print.gif" alt="{writePrintMsgTag}" title="{writePrintMsgTag}" width="16" height="16" border="0" class="recordIcons" />{writePrintMsgTag}</a>
    &nbsp;&nbsp;&nbsp;&nbsp; 
    <a target="_blank" href="{link_pdf}"><img src="images/pdf.gif" alt="{writePDFTag}" title="{writePDFTag}" width="16" height="16" border="0" class="recordIcons" />{writePDFTag}</a>
    </p>

    <!-- Voting Form -->
    <div id="voting">
    <form action="{saveVotingPATH}" method="post" style="display: inline;">
    <br/>
    <h2>{msgVoteUseability}</h2>
    <input type="hidden" name="artikel" value="{saveVotingID}" />
    <p><strong>{msgAverageVote}</strong> {printVotings}</p>
    <strong>Ihre Bewertung:</strong><br/>
    <p>{msgVoteBad}
    <input class="radio" type="radio" name="vote" value="1" /> 1
    <input class="radio" type="radio" name="vote" value="2" /> 2
    <input class="radio" type="radio" name="vote" value="3" /> 3
    <input class="radio" type="radio" name="vote" value="4" /> 4
    <input class="radio" type="radio" name="vote" value="5" /> 5
    {msgVoteGood}
    <input class="submit" type="submit" name="submit" value="{msgVoteSubmit}" />
    </p>

    </form>
    </div>
    <!-- /Voting Form -->

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
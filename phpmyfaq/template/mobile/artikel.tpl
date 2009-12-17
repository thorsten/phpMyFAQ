<h2 id="article_category">{writeRubrik}</h2>
<h2>{writeThema}</h2>

    <!-- Article -->
    <div id="article_content">{writeContent}</div>
    <!-- /Article -->

    <!-- Tags -->
    <p><strong>{writeTagHeader}</strong> {writeArticleTags}</p>
    <!-- /Tags -->

    <!-- Related Articles -->
    <p><strong>{writeRelatedArticlesHeader}</strong>{writeRelatedArticles}</p>
    <!-- / Related Articles -->

    <!-- Article Info -->
    <p><span id="popularity" style="display: none;">{writePopularity}</span>{writeDateMsg}<br />{writeAuthor}<br />{writeRevision}<br />{editThisEntry}</p>
    <!-- /Article Info -->

    <!-- Voting Form -->
    <div id="voting">
    <form action="{saveVotingPATH}" method="post" style="display: inline;">
    <fieldset>
    <legend>{msgVoteUseability}</legend>
    <input type="hidden" name="artikel" value="{saveVotingID}" />
    <p align="center"><strong>{msgAverageVote}</strong> {printVotings}</p>
    <p align="center">{msgVoteBad}
    <input class="radio" type="radio" name="vote" value="1" /> 1
    <input class="radio" type="radio" name="vote" value="2" /> 2
    <input class="radio" type="radio" name="vote" value="3" /> 3
    <input class="radio" type="radio" name="vote" value="4" /> 4
    <input class="radio" type="radio" name="vote" value="5" /> 5
    {msgVoteGood}<br />
    <input class="submit" type="submit" name="submit" value="{msgVoteSubmit}" />
    </p>
    </fieldset>
    </form>
    </div>
    <!-- /Voting Form -->

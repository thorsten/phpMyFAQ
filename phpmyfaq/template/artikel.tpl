<h2><em>{writeRubrik}:</em> {writeThema}</h2>
    <!-- Article -->
    <p>{writeContent}</p>
    <!-- /Article -->
    <p>{writeDateMsg}<br />{writeAuthor}</p>
    
    {switchLanguage}
    <p>
    <img src="images/print.gif" alt="Print" width="16" height="16" border="0" /> {writePrintMsg}
    <img src="images/email.gif" alt="Send2friends" width="16" height="16" border="0" /> {writeSend2FriendMsg}
    <img src="images/pdf.gif" alt="PDF" width="16" height="16" border="0" /> {writePDF}
    <img src="images/xml.gif" alt="XML" width="24" height="16" border="0" /> {writeXMLMsg}
    </p>

    <!-- Voting Form -->
    <form action="{saveVotingPATH}" method="post" style="display: inline;">
    <fieldset>
    <legend>{msgVoteUseability}</legend>
    <input type="hidden" name="artikel" value="{saveVotingID}" />
    <input type="hidden" name="userip" value="{saveVotingIP}" />
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
    <!-- /Voting Form -->

    <p>{writeCommentMsg} | {writeListMsg}</p>
    {writeComments}
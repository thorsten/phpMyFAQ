    <!-- begin news -->
    <div id="news">
    <h2>{writeNewsHeader}{writeNewsRSS}</h2>
    <h2 id="news_header">{writeHeader}</h2>

    <!-- News -->
    <div id="news_content">{writeContent}</div>
    <!-- /News -->

    <!-- News Info -->
    <p>{writeDateMsg}<br />{writeAuthor}<br />{editThisEntry}</p>
    <!-- /News Info -->

    <p>{writeCommentMsg}</p>

    <!-- Comment Form -->
    <a name="comment"></a><div id="comment" style="display: none;">
    <form action="{writeSendAdress}" method="post">
    <input type="hidden" name="newsid" value="{newsId}" />
    <input type="hidden" name="newslang" value="{newsLang}" />
    <input type="hidden" name="type" value="news" />
    <input type="hidden" name="spamid" value="{spamid}" />

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

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>
    <br />

    </form>
    </div>
    <!-- /Comment Form -->

    {writeComments}
    </div>


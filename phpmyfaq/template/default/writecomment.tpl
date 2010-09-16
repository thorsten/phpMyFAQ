<h2>{msgCommentHeader}</h2>

    <form action="{writeSendAdress}" method="post">
    <input type="hidden" name="id" value="{ID}" />
    <input type="hidden" name="lang" value="{LANG}" />

    <fieldset>
    <legend>{writeThema}</legend>

    <label for="user">{msgNewContentName}</label>
    <input type="text" name="user" value="{defaultContentName}" size="50" required="true"><br />

    <label for="mail">{msgNewContentMail}</label>
    <input type="email" name="mail" value="{defaultContentMail}" size="50" required="true"><br />

    <label for="comment">{msgYourComment}</label>
    <textarea cols="37" rows="10" name="comment" required="true"></textarea><br />

    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>

	</form>
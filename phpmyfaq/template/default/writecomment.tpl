<h2>{msgCommentHeader}</h2>

    <form action="{writeSendAdress}" method="post">
    <input type="hidden" name="id" value="{ID}" />
    <input type="hidden" name="lang" value="{LANG}" />

    <fieldset>
    <legend>{writeThema}</legend>

    <label for="user" class="left">{msgNewContentName}</label>
    <input class="inputfield" type="text" name="user" value="{defaultContentName}" size="50" /><br />

    <label for="mail" class="left">{msgNewContentMail}</label>
    <input class="inputfield" type="email" name="mail" value="{defaultContentMail}" size="50" /><br />

    <label for="comment" class="left">{msgYourComment}</label>
    <textarea class="inputarea" cols="37" rows="10" name="comment"></textarea><br />

    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>

	</form>
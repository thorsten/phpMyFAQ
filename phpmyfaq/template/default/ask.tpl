<h2>{msgQuestion}</h2>

	<p>{msgNewQuestion}</p>

	<form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgQuestion}</legend>

    <label for="username">{msgNewContentName}</label>
    <input type="text" name="username" id="username" value="{defaultContentName}" size="50" required="true"><br />

    <label for="usermail">{msgNewContentMail}</label>
    <input type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="50" required="true"><br />

    <label for="rubrik">{msgAskCategory}</label>
    <select name="rubrik" id="rubrik" required="true">
    {printCategoryOptions}
    </select><br />

    <label for="content">{msgAskYourQuestion}</label>
    <textarea cols="45" rows="10" name="content" id="content" required="true"></textarea><br />

    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>

    <br />
    </form>
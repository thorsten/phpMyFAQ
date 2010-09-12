<h2>{msgQuestion}</h2>

	<p>{msgNewQuestion}</p>

	<form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgQuestion}</legend>

    <label for="username" class="left">{msgNewContentName}</label>
    <input class="inputfield" type="text" name="username" id="username" value="{defaultContentName}" size="50" /><br />

    <label for="usermail" class="left">{msgNewContentMail}</label>
    <input class="inputfield" type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="50" /><br />

    <label for="rubrik" class="left">{msgAskCategory}</label>
    <select name="rubrik" id="rubrik">
    {printCategoryOptions}
    </select><br />

    <label for="content" class="left">{msgAskYourQuestion}</label>
    <textarea class="inputarea" cols="45" rows="10" name="content" id="content"></textarea><br />

    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>

    <br />
    </form>
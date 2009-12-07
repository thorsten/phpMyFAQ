<h2>{msgQuestion}</h2>

	<p>{msgNewQuestion}</p>

	<form action="{writeSendAdress}" method="post" style="display: inline">

    <div>
        <label for="username" class="left">{msgNewContentName}</label>
        <input class="inputfield" type="text" name="username" id="username" value="{defaultContentName}" size="50" />
    </div>
    <div>
        <label for="usermail" class="left">{msgNewContentMail}</label>
        <input class="inputfield" type="text" name="usermail" id="usermail" value="{defaultContentMail}" size="50" />
    </div>
    <div>
        <label for="rubrik" class="left">{msgAskCategory}</label>
        <select name="rubrik" id="rubrik">
            {printCategoryOptions}
        </select><br />
    </div>
    <div>
        <label for="content" class="left">{msgAskYourQuestion}</label>
        <textarea class="left" name="content" rows="10" cols="50"></textarea><br />
    </div>
    <div>
        {captchaFieldset}
    </div>

    <div style="padding-left: 150px">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>

    <br />
    </form>
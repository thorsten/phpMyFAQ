<h2>{msgNewContentHeader}</h2>

    <p>{msgNewContentAddon}</p>
    <form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgNewContentHeader}</legend>

    <label for="username">{msgNewContentName}</label>
    <input type="text" name="username" id="username" value="{defaultContentName}" size="37" required="true"><br />

    <label for="usermail">{msgNewContentMail}</label>
    <input type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="37" required="true"><br />

    <label for="rubrik">{msgNewContentCategory}</label>
    <select name="rubrik[]" id="rubrik" multiple="multiple" size="3" required="true">
    {printCategoryOptions}
    </select><br />

    <label for="thema">{msgNewContentTheme}</label>
    <textarea cols="37" rows="3" name="thema" id="thema" required="true">{printQuestion}</textarea><br />

    <label for="content">{msgNewContentArticle}</label>
    <textarea cols="37" rows="10" name="content" id="content" required="true"></textarea><br />

    <label for="keywords">{msgNewContentKeywords}</label>
    <input type="text" name="keywords" id="keywords" size="37" /><br />

    <label for="contentlink">{msgNewContentLink}</label>
    <input type="url" name="contentlink" id="contentlink" size="37" value="http://" /><br />
    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>
    <br />

    </form>

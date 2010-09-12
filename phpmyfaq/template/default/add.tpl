<h2>{msgNewContentHeader}</h2>

    <p>{msgNewContentAddon}</p>
    <form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgNewContentHeader}</legend>

    <label for="username" class="left">{msgNewContentName}</label>
    <input class="inputfield" type="text" name="username" id="username" value="{defaultContentName}" size="37" /><br />

    <label for="usermail" class="left">{msgNewContentMail}</label>
    <input class="inputfield" type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="37" /><br />

    <label for="rubrik" class="left">{msgNewContentCategory}</label>
    <select name="rubrik[]" id="rubrik" multiple="multiple" size="3">
    {printCategoryOptions}
    </select><br />

    <label for="thema" class="left">{msgNewContentTheme}</label>
    <textarea class="inputarea" cols="37" rows="3" name="thema" id="thema">{printQuestion}</textarea><br />

    <label for="content" class="left">{msgNewContentArticle}</label>
    <textarea class="inputarea" cols="37" rows="10" name="content" id="content"></textarea><br />

    <label for="keywords" class="left">{msgNewContentKeywords}</label>
    <input class="inputfield" type="text" name="keywords" id="keywords" size="37" /><br />

    <label for="contentlink" class="left">{msgNewContentLink}</label>
    <input class="inputfield" type="url" name="contentlink" id="contentlink" size="37" value="http://" /><br />
    </fieldset>

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
    </div>
    <br />

    </form>

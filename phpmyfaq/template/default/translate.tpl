<h2>{msgNewTranslationHeader}</h2>

    <p>{msgNewTranslationAddon}</p>

    <!-- start source article -->
    <fieldset>
    <legend>{msgNewTransSourcePane}</legend>
    <h3>{writeSourceTitle}</h3>
    <div id="article_content">{writeSourceContent}</div>
    </fieldset>
    <!-- end source article -->

    <!-- start user article translation -->
    <script type="text/javascript" src="admin/editor/tiny_mce.js"></script>
    <form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgNewTranslationPane}</legend>

    <textarea class="inputarea" cols="60" rows="3" name="thema" id="thema">{writeSourceTitle}</textarea><br />
    <br />
    <textarea class="inputarea" cols="60" rows="10" name="translated_content" id="translated_content">{writeSourceContent}</textarea><br />

    <label for="keywords" class="left">{msgNewTranslationKeywords}</label>
    <input class="inputfield" type="text" name="keywords" id="keywords" size="37" value="{writeSourceKeywords}"/><br />

    <br />
    <label for="username" class="left">{msgNewTranslationName}</label>
    <input class="inputfield" type="text" name="username" id="username" value="{defaultContentName}" size="37" /><br />

    <label for="usermail" class="left">{msgNewTranslationMail}</label>
    <input class="inputfield" type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="37" /><br />

    <input type="hidden" name="faqid" id="faqid" value="{writeSourceFaqId}" />
    <input type="hidden" name="faqlanguage" id="faqlanguage" value="{writeTransFaqLanguage}" />
    <input type="hidden" name="contentlink" id="contentlink" value="http://" />
    </fieldset>
    <!-- end user article translation -->

    {captchaFieldset}

    <div style="text-align:center;">
        <input class="submit" type="submit" name="submit" value="{msgNewTranslationSubmit}" />
    </div>
    <br />

    </form>

    <!-- tinyMCE -->
    <script type="text/javascript">
    <!--
        tinyMCE.init({
            mode : "exact",
            language : "{tinyMCELanguage}",
            elements : "translated_content",
            editor_deselector : "mceNoEditor",
            theme : "simple"
        });
    //-->
    </script>
    <!-- /tinyMCE -->

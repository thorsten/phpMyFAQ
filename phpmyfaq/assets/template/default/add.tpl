<h2>{msgNewContentHeader}</h2>
            
            <p>{msgNewContentAddon}</p>
            <form id="formValues" action="#" method="post" class="form-horizontal">
                <input type="hidden" name="lang" id="lang" value="{lang}" />
                <input type="hidden" value="{openQuestionID}" id="openQuestionID" name="openQuestionID" />
                
                <div class="control-group">
                    <label class="control-label" for="name">{msgNewContentName}</label>
                    <div class="controls">
                        <input type="text" name="name" id="name" value="{defaultContentName}" required>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">{msgNewContentMail}:</label>
                    <div class="controls">
                        <input type="email" name="email" id="email" value="{defaultContentMail}" required>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="rubrik">{msgNewContentCategory}</label>
                    <div class="controls">
                        <select name="rubrik[]" id="rubrik" multiple="multiple" size="5" required>
                        {printCategoryOptions}
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="question">{msgNewContentTheme}</label>
                    <div class="controls">
                        <textarea cols="37" rows="3" name="question" id="question" required="required" {readonly}>{printQuestion}</textarea>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="answer">{msgNewContentArticle}</label>
                    <div class="controls">
                        <textarea cols="37" rows="10" name="answer" id="answer" required="required"></textarea>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="keywords">{msgNewContentKeywords}</label>
                    <div class="controls">
                        <input type="text" name="keywords" id="keywords" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="contentlink">{msgNewContentLink}</label>
                    <div class="controls">
                        <input type="url" name="contentlink" id="contentlink" size="37" value="http://" />
                    </div>
                </div>

                <div class="control-group">
                    {captchaFieldset}
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" id="submitfaq">
                        {msgNewContentSubmit}
                    </button>
                </div>
            </form>

            <div id="loader"></div>
            <div id="faqs"></div>

            [enableWysiwygEditor]
            <script src="admin/editor/tiny_mce.js?{currentTimestamp}"></script>
            <script type="text/javascript">
                $(document).ready(function() {
                    tinyMCE.init({
                        mode : "exact",
                        language : "en",
                        elements : "answer",
                        theme : "advanced",
                        plugins : "fullscreen",
                        theme_advanced_buttons1 : "bold,italic,underline,|,strikethrough,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,undo,redo,link,unlink,|,fullscreen",
                        theme_advanced_buttons2 : "",
                        theme_advanced_buttons3 : "",
                        theme_advanced_toolbar_location : "top",
                        theme_advanced_toolbar_align : "left",
                        theme_advanced_statusbar_location : "bottom",
                        use_native_selects : true,
                        entity_encoding : "raw",
                        extended_valid_elements : "code"
                    });
                });
            </script>
            [/enableWysiwygEditor]

            <script type="text/javascript">
                $(document).ready(function() {
                    $('#submitfaq').click(function() {
                        if (undefined !== tinyMCE) {
                            tinyMCE.get("answer").setContent(tinyMCE.activeEditor.getContent());
                            document.getElementById("answer").value = tinyMCE.activeEditor.getContent();
                        }
                        saveFormValues('savefaq', 'faq');
                    });
                    $('form#formValues').submit(function() { return false; });
                });
            </script>


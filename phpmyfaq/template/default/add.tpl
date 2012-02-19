<h2>{msgNewContentHeader}</h2>
            
            <p>{msgNewContentAddon}</p>
            <form id="formValues" action="#" method="post" class="form-horizontal">
                <input type="hidden" name="lang" id="lang" value="{lang}" />
                <input type="hidden" value="{openQuestionID}" id="openQuestionID" name="openQuestionID" />
                
                <div class="control-group">
                    <label class="control-label" class="control-label" for="name">{msgNewContentName}</label>
                    <div class="controls">
                        <input type="text" name="name" id="name" value="{defaultContentName}"required />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">{msgNewContentMail}:</label>
                    <div class="controls">
                        <input type="email" name="email" id="email" value="{defaultContentMail}" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="rubrik">{msgNewContentCategory}</label>
                    <div class="controls">
                        <select name="rubrik[]" id="rubrik" multiple="multiple" size="5" required="true" />
                        {printCategoryOptions}
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="question">{msgNewContentTheme}</label>
                    <div class="controls">
                        <textarea cols="37" rows="3" name="question" id="question" required="required" {readonly} />{printQuestion}</textarea>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="answer">{msgNewContentArticle}</label>
                    <div class="controls">
                        <textarea cols="37" rows="10" name="answer" id="answer" required="required" /></textarea>
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
                    <input class="btn-primary" type="submit" id="submitfaq" value="{msgNewContentSubmit}" />
                </div>
            </form>

            <div id="loader"></div>
            <div id="faqs"></div>

            <script type="text/javascript" >
            $(function() {
                $('#submitfaq').click(function() {
                    saveFormValues('savefaq', 'faq');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>

<h2>{msgNewContentHeader}</h2>
            
            <p>{msgNewContentAddon}</p>
            <form id="formValues" action="#" method="post">
                <input type="hidden" name="lang" id="lang" value="{lang}" />
                
                <p>
                    <label for="name">{msgNewContentName}</label>
                    <input type="text" name="name" id="name" value="{defaultContentName}" size="37"
                           required="required" autofocus="autofocus" />
                </p>

                <p>
                <label for="email">{msgNewContentMail}</label>
                <input type="email" name="email" id="email" value="{defaultContentMail}" size="37"
                       required="required" />

                <p>
                <label for="rubrik">{msgNewContentCategory}</label>
                <select name="rubrik[]" id="rubrik" multiple="multiple" size="5" required="true" />
                {printCategoryOptions}
                </select>
                </p>

                <p>
                <label for="question">{msgNewContentTheme}</label>
                <textarea cols="37" rows="3" name="question" id="question" required="required" />{printQuestion}</textarea>
                </p>

                <p>
                <label for="answer">{msgNewContentArticle}</label>
                <textarea cols="37" rows="10" name="answer" id="answer" required="required" /></textarea>
                </p>

                <p>
                <label for="keywords">{msgNewContentKeywords}</label>
                <input type="text" name="keywords" id="keywords" size="37" />
                </p>

                <p>
                <label for="contentlink">{msgNewContentLink}</label>
                <input type="url" name="contentlink" id="contentlink" size="37" value="http://" />
                </p>

                <p>
                {captchaFieldset}
                </p>

                <p>
                    <input class="submit" type="submit" id="submitfaq" value="{msgNewContentSubmit}" />
                </p>
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

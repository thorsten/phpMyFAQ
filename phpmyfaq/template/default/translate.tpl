<h2>{msgNewTranslationHeader}</h2>

            <p>{msgNewTranslationAddon}</p>

            <!-- start user article translation -->
            <header>
                <h3>{msgNewTranslationPane}</h3>
            </header>

            <form id="formValues" action="#" method="post">
                <input type="hidden" name="faqid" id="faqid" value="{writeSourceFaqId}" />
                <input type="hidden" name="faqlanguage" id="faqlanguage" value="{writeTransFaqLanguage}" />
                <input type="hidden" name="rubrik[]" value="{categoryId}">
                <input type="hidden" name="contentlink" id="contentlink" value="http://" />

                <p>
                    <label for="question">{msgNewTranslationQuestion}</label>
                    <textarea cols="60" rows="3" name="question" id="question"
                              required="required" />{writeSourceTitle}</textarea>
                </p>

                <p>
                    <label for="translated_answer">{msgNewTranslationAnswer}</label>
                    <textarea cols="60" rows="10" name="translated_answer"
                              id="translated_answer"
                              required="required" />{writeSourceContent}</textarea>
                </p>

                <p>
                    <label for="keywords">{msgNewTranslationKeywords}</label>
                    <input type="text" name="keywords" id="keywords" size="37"
                           value="{writeSourceKeywords}"/>
                </p>

                <p>
                    <label for="name">{msgNewTranslationName}</label>
                    <input type="text" name="name" id="name"
                           value="{defaultContentName}" size="37"
                           required="required" />
                </p>

                <p>
                    <label for="email">{msgNewTranslationMail}</label>
                    <input type="email" name="email" id="mail"
                           value="{defaultContentMail}" size="37"
                           required="required" />
                </p>
                <!-- end user article translation -->

                {captchaFieldset}

                <p>
                <input class="submit" type="submit" name="submit" id="submitfaq"
                       value="{msgNewTranslationSubmit}" />
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
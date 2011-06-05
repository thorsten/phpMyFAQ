<section>
            <header>
                <h2>{msgNewTranslationHeader}</h2>
            </header>

            <p>{msgNewTranslationAddon}</p>

            <!-- start user article translation -->
            <header>
                <h3>{msgNewTranslationPane}</h3>
            </header>

            <form action="{writeSendAdress}" method="post" style="display: inline">
                <input type="hidden" name="faqid" id="faqid" value="{writeSourceFaqId}" />
                <input type="hidden" name="faqlanguage" id="faqlanguage" value="{writeTransFaqLanguage}" />
                <input type="hidden" name="contentlink" id="contentlink" value="http://" />

                <p>
                    <label for="question">{msgNewTranslationQuestion}</label>
                    <textarea cols="60" rows="3" name="question" id="question" required="true" />{writeSourceTitle}</textarea>
                </p>

                <p>
                    <label for="translated_answer">{msgNewTranslationAnswer}</label>
                    <textarea cols="60" rows="10" name="translated_answer" id="translated_answer" required="true" />{writeSourceContent}</textarea>
                </p>

                <p>
                    <label for="keywords">{msgNewTranslationKeywords}</label>
                    <input type="text" name="keywords" id="keywords" size="37" value="{writeSourceKeywords}"/>
                </p>

                <p>
                    <label for="username">{msgNewTranslationName}</label>
                    <input type="text" name="username" id="username" value="{defaultContentName}" size="37" required="true" />
                </p>

                <p>
                    <label for="usermail">{msgNewTranslationMail}</label>
                    <input type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="37" required="true" />
                </p>
                <!-- end user article translation -->

                {captchaFieldset}

                <p>
                <input class="submit" type="submit" name="submit" value="{msgNewTranslationSubmit}" />
                </p>

            </form>
        </section>
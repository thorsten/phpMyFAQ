<h2>{msgNewContentHeader}</h2>
            
            <p>{msgNewContentAddon}</p>
            <form action="{writeSendAdress}" method="post" style="display: inline">
                <p>
                    <label for="username">{msgNewContentName}</label>
                    <input type="text" name="username" id="username" value="{defaultContentName}" size="37" required="required" />
                </p>

                <p>
                <label for="usermail">{msgNewContentMail}</label>
                <input type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="37" required="required" />

                <p>
                <label for="rubrik">{msgNewContentCategory}</label>
                <select name="rubrik[]" id="rubrik" multiple="multiple" size="5" required="required" />
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
                    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
                </p>
            </form>
<h2>{msgQuestion}</h2>
            
            <p>{msgNewQuestion}</p>
            
            <form action="{writeSendAdress}" method="post" style="display: inline">

                <p>
                    <label for="username">{msgNewContentName}</label>
                    <input type="text" name="username" id="username" value="{defaultContentName}" size="50" required="required" />
                </p>

                <p>
                    <label for="usermail">{msgNewContentMail}</label>
                    <input type="email" name="usermail" id="usermail" value="{defaultContentMail}" size="50" required="required" />
                </p>

                <p>
                    <label for="category">{msgAskCategory}</label>
                    <select name="category" id="category" required="required" />
                    {printCategoryOptions}
                    </select>
                </p>

                <p>
                    <label for="question">{msgAskYourQuestion}</label>
                    <textarea cols="45" rows="10" name="question" id="question" required="required" /></textarea>
                </p>

                <p>
                    {captchaFieldset}
                <p>
                    <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}">
                </p>
            </form>
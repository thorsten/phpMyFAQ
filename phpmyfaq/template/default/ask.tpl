<h2>{msgQuestion}</h2>
            
            <p>{msgNewQuestion}</p>
            
            <form id="formValues" action="#" method="post">

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
                    <input class="submit" type="submit" id="submitquestion" value="{msgNewContentSubmit}">
                </p>
            </form>

            <div id="loader"></div>
            <div id="questions"></div>

            <script type="text/javascript" >
            $(function() {
                $('#submitquestion').click(function() {
                    saveFormValues('savequestion', 'question');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>
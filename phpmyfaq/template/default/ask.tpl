<h2>{msgQuestion}</h2>

            <div id="questionForm">
                <p>{msgNewQuestion}</p>
            </div>
            <div id="answerForm">

            </div>

            <div id="answers"></div>

            <form id="formValues" action="#" method="post">
                <input type="hidden" name="lang" id="lang" value="{lang}" />

                <p>
                    <label for="name">{msgNewContentName}</label>
                    <input type="text" name="name" id="name" value="{defaultContentName}" size="50"
                           required="required" autofocus="autofocus" />
                </p>

                <p>
                    <label for="email">{msgNewContentMail}</label>
                    <input type="email" name="email" id="email" value="{defaultContentMail}" size="50"
                           required="required" />
                </p>

                <p>
                    <label for="category">{msgAskCategory}</label>
                    <select name="category" id="category" required="required" />
                    {printCategoryOptions}
                    </select>
                </p>

                <p>
                    <label for="question">{msgAskYourQuestion}</label>
                    <textarea cols="45" rows="5" name="question" id="question" required="required" /></textarea>
                </p>

                <p>
                    {captchaFieldset}
                </p>

                <div id="loader"></div>
                <div id="qerror"></div>
                
                <input class="submit" type="submit" id="submitquestion" value="{msgNewContentSubmit}">

            </form>

            <script type="text/javascript" >
            $(function() {
                $('#submitquestion').click(function() {
                    checkQuestion();
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>
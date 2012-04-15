<section>
            <header>
                <h2>{msgQuestion}</h2>
            </header>

            <p>
            [adequateAnswers]
            {answers}
            [/adequateAnswers]
            </p>
    
            [messageSaveQuestion]
            <div id="message_save_question">
            <p>{Message}</p>
            </div>
            [/messageSaveQuestion]
    
            [messageQuestionFound]
            <script>
            function mailQuestion()
            {
                $.get("index.php",
                      {action: 'savequestion', domail: 1, code: '{Code}'},
                      function() {
                          document.location = 'index.php?action=savequestion&thankyou=1';
                      }
                );
            }
            </script>
            <div id="message_save_question">
            <p>{Message}</p>
            <input type="button" value="{BtnText}" onclick="mailQuestion()" />
            </div>
            [/messageQuestionFound]
        </section>
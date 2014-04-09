<section>
            <header>
                <h2>
                    {msgOpenQuestions}
                    <a href="feed/openquestions/rss.php" target="_blank">
                        <i class="fa fa-rss"></i>
                    </a>
                </h2>
            </header>

            <p>{msgQuestionText}</p>

            <table class="table table-striped">
            <tr>
                <th>{msgDate_User}</th>
                <th colspan="2">{msgQuestion2}</th>
            </tr>
            {printOpenQuestions}
            </table>
    
        </section>
<section>
            <header>
                <h2>
                    {msgOpenQuestions}
                    <a href="feed/openquestions/rss.php" target="_blank">
                        <img src="assets/img/feed.png" width="16" height="16" alt="RSS" />
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
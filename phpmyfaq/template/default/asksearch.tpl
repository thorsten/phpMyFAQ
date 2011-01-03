<header>
                <h2>{msgQuestion}</h2>
            </header>

            <form action="{writeSendAdress}" method="post">
                <input type="hidden" name="username" value="{postUsername}" />
                <input type="hidden" name="usermail" value="{postUsermail}" />
                <input type="hidden" name="rubrik" value="{postRubrik}" />
                <input type="hidden" name="content" value="{postContent}" />

                <p><strong>{msgAskYourQuestion}</strong></p>
                {msgContent}

                <p>
                    <input class="submit" type="submit" name="submit" value="{msgQuestion}" />
                </p>
            </form>
    
            {printResult}
    <!-- begin news -->
    <section>
        <header>
            <h2>{writeNewsHeader}{writeNewsRSS}</h2>
            <h2 id="news_header">{writeHeader}</h2>
        </header>
        <!-- News -->
        <div id="news_content">{writeContent}</div>
        <!-- /News -->

        <!-- News Info -->
        <p>{writeDateMsg}<br />{writeAuthor}<br />{editThisEntry}</p>
        <!-- /News Info -->

        <p>{writeCommentMsg}</p>

        <!-- Comment Form -->
        <a name="comment"></a>
        <div id="comment" style="display: none;">
        <form action="{writeSendAdress}" method="post">
            <input type="hidden" name="newsid" value="{newsId}" />
            <input type="hidden" name="newslang" value="{newsLang}" />
            <input type="hidden" name="type" value="news" />
            <input type="hidden" name="spamid" value="{spamid}" />

            <p>
                <label for="user">{msgNewContentName}</label>
                <input type="text" id="user" name="user" value="{defaultContentName}" size="50" required="required" />
            </p>

            <p>
                <label for="mail">{msgNewContentMail}</label>
                <input type="email" id="mail" name="mail" value="{defaultContentMail}" size="50" required="required" />
            </p>

            <p>
                <label for="comment_text">{msgYourComment}</label>
                <textarea cols="37" rows="10" id="comment_text" name="comment" required="required" /></textarea>
            </p>

            <p>
                {captchaFieldset}
            </p>

            <p>
                <input class="submit" type="submit" name="submit" value="{msgNewContentSubmit}" />
            </p>

        </form>
        </div>
        <!-- /Comment Form -->

        {writeComments}
        </section>
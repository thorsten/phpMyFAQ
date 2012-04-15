        <!-- begin news -->
        <section>
            <header>
                <h2>{writeNewsHeader}{writeNewsRSS}</h2>
            </header>
            <!-- News -->
            <article>
                <header>
                    <h3>{writeHeader}</h3>
                </header>
                {writeContent}
            </article>
            <!-- /News -->

            <div id="faqTabs">
                <ul class="faqTabNav">
                    <li>
                        <a href="javascript:void(0);" onclick="infoBox('authorInfo')">
                            About this news
                        </a>
                    </li>
                </ul>
                <div class="faqTabContent" id="authorInfo" style="display: none;">
                    {writeDateMsg}<br />{writeAuthor}<br />{editThisEntry}
                </div>
            </div>

            <p>{writeCommentMsg}</p>

            <!-- Comment Form -->
            <a name="comment"></a>
            <div id="commentForm" style="display: none;">
                <form id="formValues" action="#" method="post" class="form-horizontal">
                    <input type="hidden" name="newsid" value="{newsId}" />
                    <input type="hidden" name="lang" value="{newsLang}" />
                    <input type="hidden" name="type" value="news" />

                    <div class="control-group">
                        <label class="control-label" for="user">{msgNewContentName}</label>
                        <div class="controls">
                            <input type="text" id="user" name="user" value="{defaultContentName}" required="required" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="mail">{msgNewContentMail}</label>
                        <div class="controls">
                            <input type="email" id="mail" name="mail" value="{defaultContentMail}" required="required" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="comment_text">{msgYourComment}</label>
                        <div class="controls">
                            <textarea id="comment_text" name="comment_text" required="required" /></textarea>
                        </div>
                    </div>

                    <div class="control-group">
                    {captchaFieldset}
                    </div>

                    <div class="form-actions">
                        <input class="btn-primary" id="submitcomment" type="submit" value="{msgNewContentSubmit}" />
                    </div>

                </form>
            </div>
            <!-- /Comment Form -->

            <div id="loader"></div>
            <div id="comments">
                {writeComments}
            </div>

            <script type="text/javascript" >
            $(function() {
                $('#submitcomment').click(function() {
                    saveFormValues('savecomment', 'comment');
                });
                $('form#formValues').submit(function() { return false; });
            });
            </script>

        </section>
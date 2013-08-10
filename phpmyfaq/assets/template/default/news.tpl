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

            <ul id="tab" class="nav nav-tabs">
                <li><a href="#authorInfo" data-toggle="tab">{msgAboutThisNews}</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="authorInfo">
                {writeDateMsg}<br />{writeAuthor}<br />{editThisEntry}
                </div>
            </div>

            <p>{writeCommentMsg}</p>

            <!-- Comment Form -->
            <a name="comment"></a>
            <div id="commentForm" style="display: none;">
                <form id="formValues" action="#" method="post" class="form-horizontal" accept-charset="utf-8">
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

                    {captchaFieldset}

                    <div class="form-actions">
                        <button class="btn btn-primary" id="submitcomment" type="submit">
                            {msgNewContentSubmit}
                        </button>
                    </div>

                </form>
            </div>
            <!-- /Comment Form -->

            <div id="loader"></div>
            <div id="comments">
                {writeComments}
            </div>

            <script type="text/javascript" >
            $('form#formValues').on('submit', function (e) {
                e.preventDefault();
                saveFormValues('savecomment', 'comment');
                return false;
            });
            </script>

        </section>
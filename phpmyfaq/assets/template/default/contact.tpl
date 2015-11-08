<h2>{msgContact}</h2>
            <p>{msgContactOwnText}</p>
            <p><strong>{msgContactEMail}</strong></p>

            <div id="loader"></div>
            <div id="contacts"></div>

            <form class="form-horizontal" id="formValues" action="#" method="post">
                <input type="hidden" name="lang" id="lang" value="{lang}">

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="name">{msgNewContentName}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="name" id="name" value="{defaultContentName}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="email">{msgNewContentMail}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="email" id="email" value="{defaultContentMail}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="question">{msgMessage}</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" cols="37" rows="5" name="question" id="question" required></textarea>
                    </div>
                </div>

                {captchaFieldset}

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button class="btn btn-primary" type="submit" id="submitcontact">
                            {msgS2FButton}
                        </button>
                    </div>
                </div>
            </form>

            <script>
                $(function() {
                    $('#submitcontact').click(function() {
                        saveFormValues('sendcontact', 'contact');
                    });
                    $('form#formValues').submit(function() { return false; });
                });
            </script>
            
            <!-- PLEASE DO NOT REMOVE THE COPYRIGHT NOTICE -->
            <div id="copyright">
                <hr>
                <small>
                    Template/CSS by <a href="http://www.rinne.info">Thorsten Rinne</a><br>
                    Original phpMyFAQ logo by <a href="http://www.lieven.be/">Lieven Op De Beeck</a><br>
                    <i class="fa fa-apple"></i> Available on the
                    <a target="_blank" href="https://itunes.apple.com/en/app/phpmyfaq/id977896957">App Store</a><br>
                    &copy; 2001 - 2015 by <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/2.0/">Mozilla Public License</a>.
                    All rights reserved.<br>
                </small>
            </div>
            <!-- PLEASE DO NOT REMOVE THE COPYRIGHT NOTICE -->

<h2>{msgContact}</h2>
            <p>{msgContactOwnText}</p>
            <p><strong>{msgContactEMail}</strong></p>

            <div id="loader"></div>
            <div id="contacts"></div>

            <form class="form-horizontal" id="formValues" action="#" method="post" accept-charset="utf-8">
                <input type="hidden" name="lang" id="lang" value="{lang}" />

                <div class="control-group">
                    <label class="control-label" for="name">{msgNewContentName}</label>
                    <div class="controls">
                        <input type="text" name="name" id="name" value="{defaultContentName}" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="email">{msgNewContentMail}</label>
                    <div class="controls">
                        <input type="email" name="email" id="email" value="{defaultContentMail}" required="required" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label" for="question">{msgMessage}</label>
                    <div class="controls">
                        <textarea cols="37" rows="5" name="question" id="question" required="required" /></textarea>
                    </div>
                </div>

                {captchaFieldset}

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit" id="submitcontact">
                        {msgS2FButton}
                    </button>
                </div>
            </form>

            <script type="text/javascript" >
                $(function() {
                    $('#submitcontact').click(function() {
                        saveFormValues('sendcontact', 'contact');
                    });
                    $('form#formValues').submit(function() { return false; });
                });
            </script>
            
            <!-- PLEASE DO NOT REMOVE THE COPYRIGHT NOTICE -->
            <div id="copyright">
                <small>
                &copy; 2001 - 2013 by <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/2.0/">Mozilla Public License</a>.
                All rights reserved.<br>
                Template/CSS by <a href="http://www.rinne.info">Thorsten Rinne</a>,
                phpMyFAQ logo by <a href="http://www.lieven.be/">Lieven Op De Beeck</a>.
                </small>
            </div>
            <!-- PLEASE DO NOT REMOVE THE COPYRIGHT NOTICE -->

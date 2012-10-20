<h2>{msgContact}</h2>
            <p>{msgContactOwnText}</p>
            <p><strong>{msgContactEMail}</strong></p>

            <div id="loader"></div>
            <div id="contacts"></div>

            <form class="form-horizontal" id="formValues" action="#" method="post">
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

                <div class="control-group">
                    {captchaFieldset}
                </div>

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
            
            <!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->
            <div id="version"><a href="http://www.phpmyfaq.de"><img src="assets/img/logo.png" width="88" height="31" alt="powered by phpMyFAQ {version}" title="powered by phpMyFAQ {version}" /></a></div>
            <div id="copyright">&copy; 2001 - 2012 by <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/MPL-1.1.html">Mozilla Public License</a>. All rights reserved.assets/template/CSS by <a href="http://www.rinne.info">Thorsten Rinne</a>.phpMyFAQ logo by <a href="http://www.lieven.be/">Lieven Op De Beeck</a></div>
            <!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->

<section>
            <p>{msgContactOwnText}</p>
            <p><strong>{msgContactEMail}</strong></p>

            <div id="loader"></div>
            <div id="contacts"></div>

            <form class="form-horizontal" id="formValues" action="#" method="post">
                <input type="hidden" name="lang" id="lang" value="{lang}">

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="name">{msgNewContentName}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" name="name" id="name" value="{defaultContentName}"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="email">{msgNewContentMail}</label>
                    <div class="col-sm-9">
                        <input type="email" class="form-control" name="email" id="email" value="{defaultContentMail}"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label" for="question">{msgMessage}</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" cols="37" rows="5" name="question" id="question"
                                  required></textarea>
                    </div>
                </div>

                {captchaFieldset}

                <div class="form-group">
                    <div class="col-sm-12 text-right">
                        <button class="btn btn-primary btn-lg" type="submit" id="submitcontact">
                            {msgS2FButton}
                        </button>
                    </div>
                </div>
            </form>

            
            <!-- PLEASE DO NOT REMOVE THE COPYRIGHT NOTICE -->
            <div class="text-center">
                <hr>
                <small>
                    Template/CSS by <a href="http://www.rinne.info">Thorsten Rinne</a><br>
                    Original phpMyFAQ logo by <b>Lieven Op De Beeck</b><br>
                    <a href="https://geo.itunes.apple.com/de/app/phpmyfaq/id977896957?mt=8" style="display:inline-block;overflow:hidden;background:url(https://linkmaker.itunes.apple.com/images/badges/en-us/badge_appstore-lrg.svg) no-repeat;width:165px;height:40px;margin:10px;"></a><br>
                    &copy; 2001 - 2019 by <a href="https://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/2.0/">Mozilla Public License</a>.
                    All rights reserved.<br>
                </small>
            </div>
            <!-- PLEASE DO NOT REMOVE THE COPYRIGHT NOTICE -->


        </section>

        <script>
            $(function() {
                $('#submitcontact').on('click', function () {
                    saveFormValues('sendcontact', 'contact');
                });
                $('form#formValues').submit(function() { return false; });
            });
        </script>

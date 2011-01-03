<h2>{msgContact}</h2>
            <p>{msgContactOwnText}</p>
            <p><strong>{msgContactEMail}</strong></p>
            <form action="{writeSendAdress}" method="post" style="display: inline">
            
                <p>
                    <label for="name">{msgNewContentName}</label>
                    <input type="text" name="name" id="name" value="{defaultContentName}" size="40" required="required" />
                </p>

                <p>
                    <label for="email">{msgNewContentMail}</label>
                    <input type="email" name="email" id="email" value="{defaultContentMail}" size="40" required="required" />
                </p>

                <p>
                    <label for="question">{msgMessage}</label>
                    <textarea cols="37" rows="5" name="question" id="question" required="required" /></textarea>
                </p>

                <p>
                    {captchaFieldset}
                </p>

                <p>
                    <input class="submit" type="submit" name="submit" value="{msgS2FButton}" />
                </p>
            </form>
            
            <!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->
            <div id="version"><a href="http://www.phpmyfaq.de"><img src="images/logo.png" width="88" height="31" alt="powered by phpMyFAQ {version}" title="powered by phpMyFAQ {version}" /></a></div>
            <div id="copyright">&copy; 2001 - 2011 by <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/MPL-1.1.html">Mozilla Public License</a>. All rights reserved.Template/CSS by <a href="http://www.rinne.info">Thorsten Rinne</a>phpMyFAQ logo by <a href="http://www.lieven.be/">Lieven Op De Beeck</a></div>
            <!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->

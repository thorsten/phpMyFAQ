<h2>{msgContact}</h2>
            <p>{msgContactOwnText}</p>
            <p><strong>{msgContactEMail}</strong></p>
            <form action="{writeSendAdress}" method="post" style="display: inline">
            <fieldset>
            <legend>{msgContact}</legend>
                
                <label for="name">{msgNewContentName}</label><br>
                <input type="text" name="name" value="{defaultContentName}" size="40" required="true"><br>
                
                <label for="email">{msgNewContentMail}</label><br>
                <input type="email" name="email" value="{defaultContentMail}" size="40" required="true"><br>
                
                <label for="question">{msgMessage}</label><br>
                <textarea cols="37" rows="5" name="question" required="true"></textarea><br>
                </fieldset>
                
                {captchaFieldset}
                
                <div style="text-align:center;">
                <input class="submit" type="submit" name="submit" value="{msgS2FButton}" />
                </div>
            </form>
            
            <!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->
            <div id="version"><a href="http://www.phpmyfaq.de"><img src="images/logo.png" width="88" height="31" alt="powered by phpMyFAQ {version}" title="powered by phpMyFAQ {version}" /></a></div>
            <div id="copyright">&copy; 2001 - 2010 by <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/MPL-1.1.html">Mozilla Public License</a>. All rights reserved.<br>Template/CSS by <a href="http://www.rinne.info">Thorsten Rinne</a><br>phpMyFAQ logo by <a href="http://www.lieven.be/">Lieven Op De Beeck</a></div>
            <!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->
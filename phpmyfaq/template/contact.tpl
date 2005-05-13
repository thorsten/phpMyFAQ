<h2>{msgContact}</h2>
	<p>{msgContactOwnText}</p>
	<p><strong>{msgContactEMail}</strong></p>
	<form action="{writeSendAdress}" method="post" style="display: inline">
    <fieldset>
    <legend>{msgContact}</legend>
    
	<label for="name">{msgNewContentName}</label>
    <input class="inputfield" type="text" name="name" value="{defaultContentName}" size="40" /><br />
	
	<label for="email">{msgNewContentMail}</label>
    <input class="inputfield" type="text" name="email" value="{defaultContentMail}" size="40" /><br />
	
	<label for="question">{msgMessage}</label>
    <textarea class="inputarea" cols="38" rows="5" name="question"></textarea><br />
	
    <input class="submit" type="submit" name="submit" value="{msgS2FButton}" />
	
    </fieldset>
    </form>
    
	<!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->
	<div id="version"><a href="http://www.phpmyfaq.de"><img src="images/logo.png" width="88" height="31" alt="powered by phpMyFAQ {version}" title="powered by phpMyFAQ {version}" /></a></div>
	<div id="copyright">&copy; 2001 - 2005 by <a href="http://www.phpmyfaq.de/">phpMyFAQ Team</a> under the <a href="http://www.mozilla.org/MPL/MPL-1.1.html">Mozilla Public License</a>. All rights reserved.<br />Template/CSS by <a href="http://www.thorsten-rinne.de">Thorsten Rinne</a> and <a href="http://www.grochtdreis.de">Jens Grochtdreis</a></div>
	<!-- DO NOT REMOVE THE COPYRIGHT NOTICE -->

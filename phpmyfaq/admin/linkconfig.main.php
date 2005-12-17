<?php
/**
* $Id: linkconfig.main.php,v 1.1 2005-12-17 19:32:55 thorstenr Exp $
*
* LinkVerifier configuration
*
* Usage:
*   index.php?aktion=linkconfig
*
* Configures link verifier
*
* @author           Minoru TODA <todam@netjapan.co.jp>
* @since            2005-11-07
* @copyright       (c) 2005 NetJapan, Inc.
*
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License"); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
* 
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*
* The Initial Developer of the Original Code is released for external use 
* with permission from NetJapan, Inc. IT Administration Group.
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Define number of entries per page
$entriesPerPage = 10;

/*
 * Enumerate linkconfig specific parameters into array
 */
function enumScriptParameters() {
	global $params, $_REQUEST;
	
	$scriptParameters = array('uin' => FALSE,
	                          'aktion' => 'linkconfig',
                              'sortby' => 'id',
							  'sortorder' => 'DESC',
	                          'type' => 'WARN',
							  'page' => '1');

	$params = array();
	foreach ($scriptParameters as $_key => $_default) {
		if (isset($_REQUEST[$_key])) {
			$params[$_key] = $_REQUEST[$_key];
		} else {
			if ($_default !== FALSE) {
				$params[$_key] = $_default;
			}
		}
	}
}

/*
 * Generate URLs with modified parameters
 *
 * @param    mixed   $optparams
 * @return   string  URL to script
 */
function issueURL($optparams = array()) {
	global $params, $_SERVER;
	
	$_params = array();
	foreach ($params as $_key => $_value) {
		$_params[$_key] = $_value;
	}
	
	foreach ($optparams as $_key => $_value) {
		if ($_value === FALSE) {
			unset($_params[$_key]);
		} else {
			$_params[$_key] = $_value;
		}
	}
	
	$_url = $_SERVER['PHP_SELF'];
	$_separator = '?';
	
	foreach ($_params as $_key => $_value) {
		$_url .= $_separator.$_key.'='.urlencode($_value);
		$_separator = '&amp;';
	}
	
	return $_url;
}

/*
 * Show table for list type selection
 */
function showListTypeSelection()
{
	global $params, $PMF_LANG;
?>
	<table class="linkconfig" id="typeselection">
	<tr>
	<?php
		$_description = '';
		foreach (array('WARN' => 'ad_linkcheck_config_warnlist',
		               'IGNORE' => 'ad_linkcheck_config_ignorelist') as $_type => $_name) {
			if ($params['type'] == $_type) {
				$_description = $PMF_LANG[$_name.'_description'];
			}
			?>
		<td class="spacer">&nbsp;</td>
		<td class="<?php print ($params['type'] == $_type ? 'selected' : 'selectable'); ?>">
			<a href="<?php print issueURL(array('type' => $_type, 'page' => 1)); ?>">
				<?php print $PMF_LANG[$_name]; ?>
			</a>
		</td>
		<?php
			}
	?>
		<td class="spacer">&nbsp;</td>
	</tr>
	</table>
<?php
	print $_description;
}

/*
 * Add new entry into faqlinkverifyrules table
 *
 * @param   string $type
 * @param   string $url
 * @param   string $reason
 */
function addVerifyRule($type = '', $url = '', $reason = '') {
	global $db, $user;

	if ($type != '' && $url != '') {
		$query = sprintf("INSERT INTO %sfaqlinkverifyrules (id, type, url, reason, enabled, locked, owner, dtInsertDate, dtUpdateDate) VALUES (%d, '%s', '%s', '%s', 'y', 'n', '%s', '%s', '%s')", SQLPREFIX, $db->nextID(SQLPREFIX."faqlinkverifyrules", "id"), $db->escape_string($type), $db->escape_string($url), $db->escape_string($reason), $db->escape_string($user), $db->escape_string(date('Y-m-d H:m:s')), $db->escape_string(date('Y-m-d H:m:s')));
		$db->query($query);
	}


}


/*
 *
 */


// load parameters into array
enumScriptParameters();


?>
<h2><?php print $PMF_LANG['ad_linkcheck_config_title']; ?></h2>
<?php
$linkverifier = new link_verifier();		
if ($linkverifier->isReady() == FALSE) {
	print $PMF_LANG['ad_linkcheck_config_disabled'];
	return;
}

showListTypeSelection();

	$_admin = (isset($permission['editconfig']) && $permission['editconfig'] ? TRUE : FALSE);		
	if (isset($_POST['rowcount'])) {
		for ($i = 0; $i < $_POST['rowcount']; $i++) {
			// load form posts
			$posts = array();
			foreach (array('id' => FALSE, 'url' => '', 'reason' => '', 'enabled' => 'n', 'locked' => 'n', 'chown' => 'n', 'delete' => 'n') as $_key => $_default) {

				if (isset($_POST[$_key][$i])) {
					$posts[$_key] = $_POST[$_key][$i];
				} else {
					$posts[$_key] = $_default;
				}
			}
			
			switch($posts['id']) {
				case 'NEW':
					addVerifyRule($params['type'], $posts['url'], $posts['reason']);
					break;

				default: 
					$query = sprintf("SELECT * FROM %sfaqlinkverifyrules WHERE type='%s' AND id=%d LIMIT 0,1",SQLPREFIX, $db->escape_string($params['type']), $posts['id']);
					$row = FALSE;
					$result = $db->query($query);
					if ($db->num_rows($result) > 0) {
						$row = $db->fetch_object($result);
						$_owner = ($row->owner == $user ? TRUE : FALSE);
						// check if chown ?
						if ((!$_owner) && ($posts['chown'] == 'y') && ($_admin || ($posts['locked'] == 'n'))) {
							$query = sprintf("UPDATE %sfaqlinkverifyrules SET owner = '%s', dtUpdateDate = '%s' WHERE id = %d", SQLPREFIX, $db->escape_string($user), $db->escape_string(date('Y-m-d H:m:s')), $posts['id']);
							$db->query($query);
							$_owner = TRUE;
							break;
						}
						// check whether we need to unlock
						if (($_owner || $_admin) && ($row->locked == 'y') && ($posts['locked'] == 'n')) {
							$query = sprintf("UPDATE %sfaqlinkverifyrules SET locked='n' WHERE id=%d", SQLPREFIX, $posts['id']);
							$db->query($query);
							break;
						}
						// check whether we need to update info
						if ($_owner) {
							$query = sprintf("UPDATE %sfaqlinkverifyrules SET url='%s', reason='%s', enabled='%s', locked='%s', dtUpdateDate = '%s' WHERE id=%d", SQLPREFIX, $db->escape_string($posts['url']), $db->escape_string($posts['reason']), $db->escape_string($posts['enabled']), $db->escape_string($posts['locked']), $db->escape_string(date('Y-m-d H:m:s')), $posts['id']);
							$db->query($query);
						}
						// check whethr we need to delete
						if ($_owner && ($row->locked == 'n') && ($posts['delete'] == 'y')) {
							$query = sprintf("DELETE FROM %sfaqlinkverifyrules WHERE id = %d", SQLPREFIX, $posts['id']);
							$db->query($query);
						}
					}
					
			
			
			}
		}
	}


	$query = sprintf("SELECT * FROM %sfaqlinkverifyrules WHERE type='%s' ORDER BY %s %s", SQLPREFIX, $db->escape_string($params['type']), $params['sortby'], $params['sortorder']);
	$result = $db->query($query);
	$pages = ceil($db->num_rows($result) / $entriesPerPage);
	$page = $params['page'] = max(1,min($pages,$params['page']));
	$query .= sprintf(" LIMIT %d,%d",($params['page'] - 1) * $entriesPerPage, $entriesPerPage);
	$result = $db->query($query);
	
?>
<form method="post" action="<?php print issueURL(); ?>">
<table class="linkconfig" id="configuration">
	<tr>
<?php
	foreach (array("id", "url", "reason", "owner") as $_key) {
		?>
		<th class="<?php print ($params['sortby'] == $_key ? 'selected' : 'selectable'); ?>">
		<a href="<?php print issueURL(array('sortby' => $_key, 'sortorder' => (($params['sortby'] == $_key) && ($params['sortorder'] == 'DESC') ? 'ASC' : 'DESC'))); ?>">
			<?php print $PMF_LANG['ad_linkcheck_config_th_'.$_key]; ?>
		</a></th>
	<?php
		}
	?>
		<th><?php print $PMF_LANG["ad_gen_delete"]; ?></th>
	</tr>

	
	<?php
		$id = 0;
		while ($row = $db->fetch_object($result)) {
			$_owner = ($row->owner == $user ? TRUE : FALSE);

		
	?>
	<tr>
		<!-- ID and Enable/Disable -->
		<input type="hidden" name="id[<?php print $id; ?>]" value="<?php print $row->id; ?>">
		<td><input type="checkbox" name="enabled[<?php print $id; ?>]" value="y" <?php print ($row->enabled == 'y' ? 'checked' : ''); ?> title="<?php print $PMF_LANG['ad_linkcheck_config_th_enabled']; ?>" <?php print ($_owner ? '' : 'disabled'); ?> >		
		#<?php print $row->id; ?></td>
		
		<!-- URL to match -->
		<td><input type="text" name="url[<?php print $id; ?>]"  value="<?php print htmlspecialchars($row->url); ?>"  <?php print ($_owner ? '' : 'disabled'); ?>  ></td>
		
		<!-- Reason to warn/ignore -->
		<td><input type="text" name="reason[<?php print $id; ?>]" value="<?php print htmlspecialchars($row->reason); ?>"  <?php print ($_owner ? '' : 'disabled'); ?>  ></td>
		
		<!-- Lock entry / chown entry -->
		<td>
		<?php
			if ($row->locked == 'y') {
				if ($_owner || $_admin) { ?>
					<input type="checkbox" name="locked[<?php print $id; ?>]" value="y" checked title="<?php print $PMF_LANG['ad_linkcheck_config_th_locked']; ?>">
				<?php } ?>
				<img src="images/locked.png" />
			<?php } else { ?>
				<input type="checkbox" name="<?php print ($_owner ? 'locked' : 'chown').'['.$id.']'; ?>" value="y" title="<?php print ($_owner ? $PMF_LANG['ad_linkcheck_config_th_locked'] : $PMF_LANG['ad_linkcheck_config_th_chown']); ?>">
				<img src="images/<?php print ($_owner ? 'locked.png' : 'chown.png'); ?>" />
			<?php } ?>
		<?php print $row->owner; ?></td>
		
		<?php if ($_owner && ($row->locked == 'n')) { ?>
		<!-- Delete Entry -->
		<td><input type="checkbox" value="y" name="delete[<?php print $id; ?>]" ></td>
		<?php } ?>
		
	</tr>
	<?php
		$id++;
		}
		
		for ($i = 0; $i < 3; $i++) {
			?>
	<tr>
		<input type="hidden" name="id[<?php print $id; ?>]" value="NEW">
		<td>NEW</td>
		<td><input type="text" name="url[<?php print $id; ?>]" value=""></td>
		<td><input type="text" name="reason[<?php print $id; ?>]" value=""></td>
		<td><?php print $user; ?></td>
		<td>&nbsp;</td>
		
	</tr>
	
	<?php
		$id++;
		}
		
		
		// Handle submission and page listing
	?>
	<tr id="lastrow">
		<td><input type="hidden" name="rowcount" value="<?php print $id; ?>"><input type="submit" value="<?php print $PMF_LANG["ad_gen_save"]; ?>" name="submit"></td>
		<td colspan="4"><?php print $PMF_LANG["ad_gen_page"].' '.$page.' '.$PMF_LANG["ad_gen_of"].' '.$pages; ?>
		<?php 
			if ($page > 1) { print sprintf(' | <a href="%s">%s</a>', issueURL(array('page' => $page - 1)), $PMF_LANG['ad_gen_lastpage']); }
			for ($i = 1; $i <= $pages; $i++) {
				print ($i == 1 ? ' | ' : ', ');
				if ($i != $page) {
					print sprintf('<a href="%s">%s</a>', issueURL(array('page' => $i)), $i);
				} else {
					print $i;
				}
			}

			if ($page < $pages) { print sprintf(' | <a href="%s">%s</a>', issueURL(array('page' => $page + 1)), $PMF_LANG['ad_gen_nextpage']); }
		
		?>
		
		</td>
	</tr>

	
	
</table>
</form>
<?php
/*
	diag_authentication.php
*/
/* ====================================================================
 *  Copyright (c)  2004-2015  Electric Sheep Fencing, LLC. All rights reserved.
 *
 *  Redistribution and use in source and binary forms, with or without modification,
 *  are permitted provided that the following conditions are met:
 *
 *  1. Redistributions of source code must retain the above copyright notice,
 *      this list of conditions and the following disclaimer.
 *
 *  2. Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in
 *      the documentation and/or other materials provided with the
 *      distribution.
 *
 *  3. All advertising materials mentioning features or use of this software
 *      must display the following acknowledgment:
 *      "This product includes software developed by the pfSense Project
 *       for use in the pfSense software distribution. (http://www.pfsense.org/).
 *
 *  4. The names "pfSense" and "pfSense Project" must not be used to
 *       endorse or promote products derived from this software without
 *       prior written permission. For written permission, please contact
 *       coreteam@pfsense.org.
 *
 *  5. Products derived from this software may not be called "pfSense"
 *      nor may "pfSense" appear in their names without prior written
 *      permission of the Electric Sheep Fencing, LLC.
 *
 *  6. Redistributions of any form whatsoever must retain the following
 *      acknowledgment:
 *
 *  "This product includes software developed by the pfSense Project
 *  for use in the pfSense software distribution (http://www.pfsense.org/).
 *
 *  THIS SOFTWARE IS PROVIDED BY THE pfSense PROJECT ``AS IS'' AND ANY
 *  EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *  IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 *  PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE pfSense PROJECT OR
 *  ITS CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 *  NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *  LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *  HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 *  STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *  ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 *  OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  ====================================================================
 *
 */

##|+PRIV
##|*IDENT=page-diagnostics-authentication
##|*NAME=Diagnostics: Authentication
##|*DESCR=Allow access to the 'Diagnostics: Authentication' page.
##|*MATCH=diag_authentication.php*
##|-PRIV

require("guiconfig.inc");
require_once("radius.inc");

if ($_POST) {
	$pconfig = $_POST;
	unset($input_errors);

	$authcfg = auth_get_authserver($_POST['authmode']);
	if (!$authcfg) {
		$input_errors[] = $_POST['authmode'] . " " . gettext("is not a valid authentication server");
	}

	if (empty($_POST['username']) || empty($_POST['password'])) {
		$input_errors[] = gettext("A username and password must be specified.");
	}

	if (!$input_errors) {
		$attributes = array();
		if (authenticate_user($_POST['username'], $_POST['password'], $authcfg, $attributes)) {
			$savemsg = gettext("User") . ": " . $_POST['username'] . " " . gettext("authenticated successfully.");
			$groups = getUserGroups($_POST['username'], $authcfg, $attributes);
			$savemsg .= "&nbsp;" . gettext("This user is a member of groups") . ": <br />";
			$savemsg .= "<ul>";
			foreach ($groups as $group) {
				$savemsg .= "<li>" . "{$group} " . "</li>";
			}
			$savemsg .= "</ul>";

		} else {
			$input_errors[] = gettext("Authentication failed.");
		}
	}
} else {
	if (isset($config['system']['webgui']['authmode'])) {
		$pconfig['authmode'] = $config['system']['webgui']['authmode'];
	} else {
		$pconfig['authmode'] = "Local Database";
	}
}

$pgtitle = array(gettext("Diagnostics"), gettext("Authentication"));
$shortcut_section = "authentication";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print('<div class="alert alert-success" role="alert">'. $savemsg.'</div>');
}

$form = new Form('Test');

$section = new Form_Section('Authentication Test');

foreach (auth_get_authserver_list() as $auth_server) {
	$serverlist[$auth_server['name']] = $auth_server['name'];
}

$section->addInput(new Form_Select(
	'authmode',
	'Authentication Server',
	$pconfig['authmode'],
	$serverlist
))->setHelp('Select the authentication server to test against');

$section->addInput(new Form_Input(
	'username',
	'Username',
	'text',
	$pconfig['username'],
	['placeholder' => 'Username']
));

$section->addInput(new Form_Input(
	'password',
	'Password',
	'password',
	$pconfig['password'],
	['placeholder' => 'Password']
));

$form->add($section);
print $form;

include("foot.inc");

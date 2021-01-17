<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

script('gpgmailer', 'personalSettings');
style('gpgmailer', 'personalSettings');    // adds a CSS file
?>

<div id="gpgmailer" class="section">
	<h2><?php p($l->t('GPG Public Keys')); ?></h2>
	<p class="settings-hint"><?php p($l->t('To enable encrypted emails, you must upload your public key below.')) ?></p>
	<label for="keydata"><?php p($l->t('Your GPG public key')); ?></label><br/>
	<textarea id="keydata" type="text"><?php if (isset($_['pubkey'])) p($_['pubkey']);?></textarea><br/>
	<a href="<?php p($_['server_pubkey_url'])?>"><button><?php p($l->t('Download Server Key')); ?></button></a>
</div>

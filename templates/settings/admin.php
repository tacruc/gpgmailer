<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

//script('myappid', 'admin');         // adds a JavaScript file
//style('survey_client', 'admin');    // adds a CSS file
?>

<div id="gpgmailer" class="section">
	<h2><?php p($l->t('Server GPG Keys')); ?></h2>


	<?php if (!empty($_['pubkey'])): ?>

		<p><textarea title="<?php p($l->t('Public Key')); ?>" class="server_pubkey" readonly="readonly"><?php p($_['pubkey']);?></textarea></p>
		<p><textarea title="<?php p($l->t('Public Key')); ?>" class="server_keyinfo" readonly="readonly"><?php p($_['keyinfo']);?></textarea></p>

	<?php endif; ?>

	<button><?php p($l->t('Download')); ?></button>

</div>

<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

//script('myappid', 'admin');         // adds a JavaScript file
//style('survey_client', 'admin');    // adds a CSS file
?>

<div id="gpgmailer" class="section">
	<h2><?php p($l->t('GPG Keys')); ?></h2>


	<p><textarea title="<?php p($l->t('Public Key')); ?>" class="personal_pubkey" ><?php p($_['pubkey']);?></textarea></p>


	<button><?php p($l->t('Download Server Key')); ?></button>

</div>

<?php
/** @var $l \OCP\IL10N */
/** @var $_ array */

//script('myappid', 'admin');         // adds a JavaScript file
//style('survey_client', 'admin');    // adds a CSS file
?>

<div id="gpgmailer" class="section">
	<h2><?php p($l->t('GPG Keys')); ?></h2>
	<form action=<?php p($_['post_url'])?> method="post" target="_blank">
		<p>
			<textarea
					title="<?php p($l->t('Public Key')); ?>"
					placeholder="<?php p($l->t('Insert your public key here'));?>"
					class="keydata"
					name="keydata"
					type="text"><?php p($_['pubkey']);?></textarea>
		</p>
		<input type="submit" value="Save"/>
	</form>
	<a href="<?php p($_['server_pubkey_url'])?>"><button><?php p($l->t('Download Server Key')); ?></button></a>
</div>
